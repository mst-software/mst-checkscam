<?php

use App\Enums\ClaimStatus;
use App\Enums\LedgerEntryType;
use App\Exceptions\InsuranceClaimException;
use App\Exceptions\InsuranceLedgerException;
use App\Mail\InsuranceClaimStatusChanged;
use App\Models\Insurance;
use App\Models\InsuranceClaim;
use App\Models\User;
use App\Services\InsuranceClaimService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

beforeEach(function () {
    Mail::fake();
    $this->claims = app(InsuranceClaimService::class);
    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->insurance = Insurance::factory()->trusted()->create(['amount' => 5000000]);
});

function submitClaim(array $overrides = []): InsuranceClaim
{
    return test()->claims->submit(test()->insurance, array_merge([
        'claimant_name' => 'Nguyễn Văn Bình',
        'contact_email' => 'binh@example.com',
        'contact_phone' => '0912345678',
        'amount_claimed' => '3000000',
        'description' => 'Chuyển tiền qua GDV nhưng không nhận được tài khoản.',
        'evidence_paths' => ['claims/a.jpg'],
    ], $overrides), '203.0.113.5');
}

test('submitting creates a claim, its first event and queues a mail to the claimant', function () {
    $claim = submitClaim();

    expect($claim->status)->toBe(ClaimStatus::Submitted)
        ->and((string) $claim->amount_claimed)->toBe('3000000.00')
        ->and($claim->ip_address)->toBe('203.0.113.5')
        ->and($claim->evidence_paths)->toBe(['claims/a.jpg'])
        ->and($claim->events()->sole()->only(['from_status', 'to_status']))
        ->toBe(['from_status' => null, 'to_status' => ClaimStatus::Submitted]);

    Mail::assertQueued(InsuranceClaimStatusChanged::class, fn ($mail) => $mail->hasTo('binh@example.com')
        && $mail->status === ClaimStatus::Submitted);
});

test('a non-positive claimed amount is refused', function () {
    expect(fn () => submitClaim(['amount_claimed' => 0]))->toThrow(InsuranceClaimException::class);
    expect(InsuranceClaim::count())->toBe(0);
});

test('every allowed transition succeeds and is recorded', function (ClaimStatus $from, ClaimStatus $to) {
    $claim = InsuranceClaim::factory()->for($this->insurance)->create(['status' => $from]);
    $extra = $to === ClaimStatus::Approved ? ['approved_amount' => $claim->amount_claimed] : [];

    $this->claims->transition($claim, $to, $this->admin, 'ghi chú nội bộ', $extra);

    expect($claim->status)->toBe($to)
        ->and($claim->events()->sole()->only(['from_status', 'to_status', 'note', 'actor_id']))
        ->toBe(['from_status' => $from, 'to_status' => $to, 'note' => 'ghi chú nội bộ', 'actor_id' => $this->admin->id]);
})->with([
    [ClaimStatus::Submitted, ClaimStatus::Reviewing],
    [ClaimStatus::Submitted, ClaimStatus::Rejected],
    [ClaimStatus::Reviewing, ClaimStatus::NeedInfo],
    [ClaimStatus::Reviewing, ClaimStatus::Approved],
    [ClaimStatus::Reviewing, ClaimStatus::Rejected],
    [ClaimStatus::NeedInfo, ClaimStatus::Reviewing],
    [ClaimStatus::NeedInfo, ClaimStatus::Rejected],
]);

test('disallowed transitions are refused without side effects', function (ClaimStatus $from, ClaimStatus $to) {
    $claim = InsuranceClaim::factory()->for($this->insurance)->create(['status' => $from, 'approved_amount' => 1000]);

    expect(fn () => $this->claims->transition($claim, $to, $this->admin, null, ['approved_amount' => 1000]))
        ->toThrow(InsuranceClaimException::class);

    expect($claim->fresh()->status)->toBe($from)->and($claim->events()->count())->toBe(0);
    Mail::assertNothingQueued();
})->with([
    [ClaimStatus::Submitted, ClaimStatus::Approved],
    [ClaimStatus::Submitted, ClaimStatus::NeedInfo],
    [ClaimStatus::NeedInfo, ClaimStatus::Approved],
    [ClaimStatus::Approved, ClaimStatus::Rejected],
    [ClaimStatus::Approved, ClaimStatus::Reviewing],
    [ClaimStatus::Rejected, ClaimStatus::Reviewing],
    [ClaimStatus::Paid, ClaimStatus::Rejected],
    [ClaimStatus::Reviewing, ClaimStatus::Paid],
]);

test('approving requires an approved amount within the claimed amount', function (array $extra) {
    $claim = InsuranceClaim::factory()->for($this->insurance)->reviewing()->create(['amount_claimed' => 3000000]);

    expect(fn () => $this->claims->transition($claim, ClaimStatus::Approved, $this->admin, null, $extra))
        ->toThrow(InsuranceClaimException::class);

    expect($claim->fresh()->status)->toBe(ClaimStatus::Reviewing);
})->with([
    'missing' => [[]],
    'zero' => [['approved_amount' => 0]],
    'above claimed' => [['approved_amount' => '3000000.01']],
]);

test('approving records the decision, amount and public summary', function () {
    $claim = InsuranceClaim::factory()->for($this->insurance)->reviewing()->create(['amount_claimed' => 3000000]);

    $this->claims->transition($claim, ClaimStatus::Approved, $this->admin, 'đủ bằng chứng', [
        'approved_amount' => '2500000',
        'public_summary' => 'Người mua không nhận được hàng.',
        'decision_note' => 'Đã gọi xác minh GDV',
    ]);

    expect((string) $claim->approved_amount)->toBe('2500000.00')
        ->and($claim->decided_by)->toBe($this->admin->id)
        ->and($claim->decided_at)->not->toBeNull()
        ->and($claim->public_summary)->toBe('Người mua không nhận được hàng.')
        ->and($claim->decision_note)->toBe('Đã gọi xác minh GDV');
});

test('marking paid writes a payout entry and lowers the balance atomically', function () {
    $claim = InsuranceClaim::factory()->for($this->insurance)->approved()->create(['amount_claimed' => 3000000, 'approved_amount' => 3000000]);

    $this->claims->markPaid($claim, $this->admin, '3000000', 'VCB FT999');

    $entry = $claim->payoutEntry;
    expect($claim->status)->toBe(ClaimStatus::Paid)
        ->and($claim->paid_at)->not->toBeNull()
        ->and($entry->type)->toBe(LedgerEntryType::Payout)
        ->and((string) $entry->amount)->toBe('-3000000.00')
        ->and((string) $entry->balance_after)->toBe('2000000.00')
        ->and($entry->reference)->toBe('VCB FT999')
        ->and($entry->recorded_by)->toBe($this->admin->id)
        ->and((string) $this->insurance->fresh()->amount)->toBe('2000000.00')
        ->and($claim->events()->sole()->to_status)->toBe(ClaimStatus::Paid);

    Mail::assertQueued(InsuranceClaimStatusChanged::class, fn ($mail) => $mail->status === ClaimStatus::Paid);
});

test('marking paid with an insufficient balance fails cleanly', function () {
    $claim = InsuranceClaim::factory()->for($this->insurance)->approved()->create(['amount_claimed' => 6000000, 'approved_amount' => 6000000]);

    expect(fn () => $this->claims->markPaid($claim, $this->admin, '6000000'))
        ->toThrow(InsuranceLedgerException::class);

    $fresh = $claim->fresh();
    expect($fresh->status)->toBe(ClaimStatus::Approved)
        ->and($fresh->paid_at)->toBeNull()
        ->and($fresh->events()->count())->toBe(0)
        ->and($this->insurance->ledgerEntries()->count())->toBe(1)
        ->and((string) $this->insurance->fresh()->amount)->toBe('5000000.00');
    Mail::assertNothingQueued();
});

test('marking paid requires an approved claim and an amount within the approved amount', function () {
    $reviewing = InsuranceClaim::factory()->for($this->insurance)->reviewing()->create();
    expect(fn () => $this->claims->markPaid($reviewing, $this->admin, '1000'))->toThrow(InsuranceClaimException::class);

    $approved = InsuranceClaim::factory()->for($this->insurance)->approved()->create(['amount_claimed' => 1000000, 'approved_amount' => 1000000]);
    expect(fn () => $this->claims->markPaid($approved, $this->admin, '1000000.01'))->toThrow(InsuranceClaimException::class);

    expect($this->insurance->ledgerEntries()->count())->toBe(1);
});

test('claimants are mailed on need info, approved and rejected but not on reviewing', function () {
    $claim = submitClaim();
    $this->claims->transition($claim, ClaimStatus::Reviewing, $this->admin);
    $this->claims->transition($claim, ClaimStatus::NeedInfo, $this->admin);
    $this->claims->transition($claim, ClaimStatus::Reviewing, $this->admin);
    $this->claims->transition($claim, ClaimStatus::Approved, $this->admin, null, ['approved_amount' => 1000000]);

    $other = submitClaim(['contact_email' => 'other@example.com']);
    $this->claims->transition($other, ClaimStatus::Rejected, $this->admin);

    $statuses = collect(Mail::queued(InsuranceClaimStatusChanged::class))->map(fn ($mail) => $mail->status)->all();
    expect($statuses)->toBe([
        ClaimStatus::Submitted, ClaimStatus::NeedInfo, ClaimStatus::Approved, ClaimStatus::Submitted, ClaimStatus::Rejected,
    ]);
});

test('the claim mail never contains internal notes or admin data', function () {
    $this->admin->update(['full_name' => 'Admin Bí Mật']);
    $claim = InsuranceClaim::factory()->for($this->insurance)->reviewing()->create();
    $this->claims->transition($claim, ClaimStatus::Rejected, $this->admin, 'GHI CHÚ SỰ KIỆN NỘI BỘ', [
        'decision_note' => 'GHI CHÚ QUYẾT ĐỊNH NỘI BỘ',
    ]);

    $mail = collect(Mail::queued(InsuranceClaimStatusChanged::class))->sole();
    $html = $mail->render();

    expect($html)->toContain('Từ chối')
        ->not->toContain('GHI CHÚ SỰ KIỆN NỘI BỘ')
        ->not->toContain('GHI CHÚ QUYẾT ĐỊNH NỘI BỘ')
        ->not->toContain('Admin Bí Mật')
        ->not->toContain($this->admin->email)
        ->and($mail)->toBeInstanceOf(ShouldQueue::class);
});

test('public claimant name is masked', function () {
    $claim = InsuranceClaim::factory()->make(['claimant_name' => 'Nguyễn Văn Bình']);

    expect($claim->maskedClaimantName())->toBe('Nguyễn Văn B******');
});
