<?php

use App\Enums\ChannelType;
use App\Enums\ClaimStatus;
use App\Enums\LedgerEntryType;
use App\Models\Insurance;
use App\Models\InsuranceChannel;
use App\Models\InsuranceClaim;
use App\Models\InsuranceLedgerEntry;
use App\Models\User;
use App\Services\InsuranceClaimService;
use App\Services\InsuranceLedgerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

beforeEach(function () {
    Mail::fake();
    $this->withoutVite();
    $this->partner = Insurance::factory()->trusted()->create(['full_name' => 'GDV Quang', 'amount' => 10000000]);
});

function profileUrl(Insurance $partner): string
{
    return route('insurances.frontend.show', $partner->slug);
}

test('a trusted partner shows the verified badge, deposit and reconciliation line', function () {
    $this->get(profileUrl($this->partner))
        ->assertOk()
        ->assertSee('Đã xác thực')
        ->assertSee('10.000.000 đ')
        ->assertSee($this->partner->expired_at->format('d/m/Y'))
        ->assertSee('Số dư hiện tại khớp sổ quỹ')
        ->assertDontSee('data-lapsed-notice', false);
});

test('a suspended or expired partner is never shown as verified', function (Closure $makePartner, string $label) {
    $partner = $makePartner();

    $this->get(profileUrl($partner))
        ->assertOk()
        ->assertSee($label)
        ->assertSee('data-lapsed-notice', false)
        ->assertDontSee('Đã xác thực')
        ->assertDontSee('Liên hệ thành viên');
})->with([
    'suspended' => [fn () => Insurance::factory()->trusted()->suspended()->create(), 'Tạm đình chỉ'],
    'expired' => [fn () => Insurance::factory()->expired()->create(), 'Đã hết hạn'],
]);

test('the reconciliation line is omitted when the balance does not match the ledger', function () {
    $this->partner->forceFill(['amount' => 9000000])->save();

    $this->get(profileUrl($this->partner))
        ->assertOk()
        ->assertDontSee('Số dư hiện tại khớp sổ quỹ');
});

test('official channels are grouped by type with their verification date', function () {
    InsuranceChannel::factory()->for($this->partner)->ofType(ChannelType::Telegram, '@gdv_quang')->create(['verified_at' => '2026-03-02 10:00:00']);
    InsuranceChannel::factory()->for($this->partner)->ofType(ChannelType::Phone, '0912345678')->create();
    InsuranceChannel::factory()->for($this->partner)->ofType(ChannelType::Phone, '0987654321')->unofficial()->create();

    $this->get(profileUrl($this->partner))
        ->assertOk()
        ->assertSee('Kênh liên hệ chính thức')
        ->assertSeeInOrder([ChannelType::Phone->label(), '0912345678', ChannelType::Telegram->label(), '@gdv_quang'])
        ->assertSee('Xác minh ngày 02/03/2026')
        ->assertDontSee('0987654321');
});

test('the claim history shows only decided claims with masked names and public summaries', function () {
    InsuranceClaim::factory()->for($this->partner)->approved()->create([
        'claimant_name' => 'Nguyễn Văn Approved',
        'amount_claimed' => 3000000,
        'approved_amount' => 2000000,
        'public_summary' => 'Tóm tắt công khai đã duyệt',
    ]);
    InsuranceClaim::factory()->for($this->partner)->rejected()->create([
        'claimant_name' => 'Lê Thị Rejected',
        'public_summary' => null,
        'decision_note' => 'GHI CHÚ NỘI BỘ',
    ]);
    InsuranceClaim::factory()->for($this->partner)->submitted()->create(['claimant_name' => 'Phạm Pending', 'public_summary' => 'KHÔNG ĐƯỢC HIỆN']);
    InsuranceClaim::factory()->for($this->partner)->reviewing()->create(['claimant_name' => 'Hồ Reviewing']);

    $approved = InsuranceClaim::where('claimant_name', 'Nguyễn Văn Approved')->sole();

    $response = $this->get(profileUrl($this->partner))->assertOk();

    $response->assertSee('Lịch sử bồi thường & quỹ', false)
        ->assertSee($approved->maskedClaimantName())
        ->assertDontSee('Nguyễn Văn Approved')
        ->assertDontSee('Lê Thị Rejected')
        ->assertDontSee('Phạm Pending')
        ->assertDontSee('KHÔNG ĐƯỢC HIỆN')
        ->assertDontSee('GHI CHÚ NỘI BỘ')
        ->assertDontSee($approved->contact_email)
        ->assertDontSee($approved->contact_phone)
        ->assertDontSee($approved->ip_address)
        ->assertSee('Tóm tắt công khai đã duyệt')
        ->assertSee('3.000.000 đ')
        ->assertSee('2.000.000 đ');

    $html = $response->getContent();
    expect(substr_count($html, 'data-public-claim'))->toBe(2)
        ->and(claimStat($html, 'total'))->toBe('4')
        ->and(claimStat($html, 'approved'))->toBe('1')
        ->and(claimStat($html, 'rejected'))->toBe('1')
        ->and(claimStat($html, 'in_progress'))->toBe('2');
});

function claimStat(string $html, string $key): string
{
    preg_match('/data-claim-stat="'.$key.'">\s*([^<]+?)\s*</', $html, $matches);

    return $matches[1] ?? '';
}

test('paid claims show the payout and the ledger without internal notes or references', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $claims = app(InsuranceClaimService::class);

    $claim = $claims->submit($this->partner, [
        'claimant_name' => 'Đỗ Văn Paid',
        'contact_email' => 'paid@example.com',
        'contact_phone' => '0901234567',
        'amount_claimed' => '4000000',
        'description' => 'Mô tả nội bộ của người yêu cầu.',
    ], '198.51.100.9');
    $claims->transition($claim, ClaimStatus::Reviewing, $admin);
    $claims->transition($claim, ClaimStatus::Approved, $admin, 'Ghi chú sự kiện', ['approved_amount' => '3000000', 'public_summary' => 'Đã chi trả cho người mua.']);
    $claims->markPaid($claim, $admin, '2500000', 'VCB-REF-SECRET');
    app(InsuranceLedgerService::class)->record($this->partner, LedgerEntryType::Deposit, '1000000', $admin, 'DEPOSIT-REF', 'Ghi chú nạp quỹ nội bộ');

    $html = $this->get(profileUrl($this->partner))
        ->assertOk()
        ->assertSee('Đã chi trả cho người mua.')
        ->assertSee('2.500.000 đ')
        ->assertSee(LedgerEntryType::Payout->label())
        ->assertSee(LedgerEntryType::Deposit->label())
        ->assertSee('Số dư hiện tại khớp sổ quỹ')
        ->assertDontSee('VCB-REF-SECRET')
        ->assertDontSee('DEPOSIT-REF')
        ->assertDontSee('Ghi chú nạp quỹ nội bộ')
        ->assertDontSee('Chi trả yêu cầu bồi thường #')
        ->assertDontSee('Ghi chú sự kiện')
        ->assertDontSee('Mô tả nội bộ của người yêu cầu.')
        ->assertDontSee('paid@example.com')
        ->assertDontSee('0901234567')
        ->assertDontSee('198.51.100.9')
        ->getContent();

    expect(claimStat($html, 'total_paid'))->toBe('2.500.000 đ')
        ->and(substr_count($html, 'data-ledger-entry'))->toBe(3);
});

test('history lists are limited to the latest ten rows', function () {
    InsuranceClaim::factory()->count(12)->for($this->partner)->rejected()->create();
    foreach (range(1, 12) as $i) {
        app(InsuranceLedgerService::class)->record($this->partner, LedgerEntryType::Deposit, '1000');
    }

    $html = $this->get(profileUrl($this->partner))->assertOk()->getContent();

    expect(substr_count($html, 'data-public-claim'))->toBe(10)
        ->and(substr_count($html, 'data-ledger-entry'))->toBe(10)
        ->and(claimStat($html, 'total'))->toBe('12');
});

test('the profile runs a bounded number of queries regardless of history size', function () {
    $countQueries = function (): int {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->get(profileUrl($this->partner))->assertOk();
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    };

    InsuranceClaim::factory()->for($this->partner)->paid()->create();
    $countQueries(); // warm up settings and other per-process caches
    $small = $countQueries();

    InsuranceClaim::factory()->count(9)->for($this->partner)->paid()->create();
    InsuranceClaim::factory()->count(5)->for($this->partner)->approved()->create();
    InsuranceChannel::factory()->count(5)->for($this->partner)->create();
    $large = $countQueries();

    expect($large)->toBe($small)->toBeLessThanOrEqual(15);
});

test('empty history shows the empty states', function () {
    InsuranceLedgerEntry::query()->where('insurance_id', $this->partner->id)->delete();

    $this->get(profileUrl($this->partner))
        ->assertOk()
        ->assertSee('Chưa có yêu cầu bồi thường nào')
        ->assertSee('Chưa có biến động quỹ')
        ->assertDontSee('Số dư hiện tại khớp sổ quỹ');
});

test('the profile links to the prefilled claim form and the verify page', function () {
    $this->get(profileUrl($this->partner))
        ->assertOk()
        ->assertSee(route('insurance-claims.create', ['partner' => $this->partner->slug]), false)
        ->assertSee(route('intermediary.verify'));
});

test('the partner list never shows a suspended partner as trusted', function () {
    $suspended = Insurance::factory()->trusted()->suspended()->create(['full_name' => 'GDV Bị Đình Chỉ']);

    $html = $this->get(route('insurances.frontend.index'))
        ->assertOk()
        ->assertSee('GDV Bị Đình Chỉ')
        ->assertSee('Tạm đình chỉ')
        ->assertSee(route('intermediary.verify'))
        ->getContent();

    $suspendedCard = str($html)->after(profileUrl($suspended))->before('</a>')->toString();
    $trustedCard = str($html)->after(profileUrl($this->partner))->before('</a>')->toString();

    expect($suspendedCard)->not->toContain('data-trusted-badge')->toContain('data-status-badge')
        ->and($trustedCard)->toContain('data-trusted-badge')
        ->and($html)->toMatch('/Thành viên đã xác thực.*?>\s*1\s*</s');
});
