<?php

use App\Enums\ChannelType;
use App\Models\Insurance;
use App\Models\InsuranceChannel;
use App\Models\Report;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->partner = Insurance::factory()->trusted()->create([
        'full_name' => 'GDV Quang',
        'amount' => 10000000,
        'verified_at' => '2025-03-12',
        'insurance_date' => '2025-03-01',
        'avatar' => 'https://cdn.example.test/avatars/quang.jpg',
        'payment_accounts' => [['bank' => 'Vietcombank', 'number' => '0123456789', 'name' => 'NGUYEN VAN QUANG']],
    ]);
});

function profileUrl(Insurance $partner): string
{
    return route('insurances.frontend.show', $partner->slug);
}

/**
 * The <head> of a rendered page.
 */
function profileHead(string $html): string
{
    return str($html)->after('<head>')->before('</head>')->toString();
}

test('a trusted partner shows the verified badge, gdv number and key facts', function () {
    $number = $this->partner->gdv_number;

    $this->get(profileUrl($this->partner))
        ->assertOk()
        ->assertHeader('Cache-Control')
        ->assertSee('Giao dịch viên đã xác thực')
        ->assertSee("Giao dịch viên số {$number}")
        ->assertSee('data-copy="GDV số '.$number.'"', false)
        ->assertSeeInOrder(['Ký quỹ', '10.000.000 đ', 'Xác thực từ', '12/03/2025', 'Hiệu lực đến', $this->partner->expired_at->format('d/m/Y')])
        ->assertSee('Trạng thái được kiểm tra lúc')
        ->assertSee(now()->format('d/m/Y'))
        ->assertDontSee('data-lapsed-notice', false);

    expect($this->get(profileUrl($this->partner))->headers->get('Cache-Control'))->toContain('no-store');
});

test('a partner without expiry shows "Không thời hạn"', function () {
    $this->partner->update(['expired_at' => null]);

    $this->get(profileUrl($this->partner))->assertOk()->assertSee('Không thời hạn');
});

test('the checklist only lists checks that are true from the data', function () {
    InsuranceChannel::factory()->for($this->partner)->ofType(ChannelType::Phone, '0912345678')->create();

    $this->get(profileUrl($this->partner))
        ->assertOk()
        ->assertSee('Scamproof đã kiểm tra')
        ->assertSee('Danh tính đã được quản trị viên xác thực')
        ->assertSee('Kênh liên hệ chính thức đã được ghi nhận')
        ->assertSee('1 kênh')
        ->assertSee('Số dư ký quỹ khớp sổ quỹ')
        ->assertSee('Chưa có tố cáo được duyệt nhắm vào các kênh chính thức');

    $this->partner->forceFill(['verified_at' => null, 'amount' => 9000000])->save();

    $this->get(profileUrl($this->partner))
        ->assertOk()
        ->assertDontSee('Danh tính đã được quản trị viên xác thực')
        ->assertDontSee('khớp sổ quỹ')
        ->assertSee('Kênh liên hệ chính thức đã được ghi nhận');
});

test('approved reports on an official channel show a red notice linking to the search', function () {
    InsuranceChannel::factory()->for($this->partner)->ofType(ChannelType::Phone, '0912345678')->create();
    InsuranceChannel::factory()->for($this->partner)->ofType(ChannelType::Telegram, '@gdv_quang')->create();
    Report::factory()->count(2)->create(['status' => 'approved', 'type' => 'account', 'target_id' => '0912.345.678']);
    Report::factory()->create(['status' => 'pending', 'type' => 'account', 'target_id' => '0912345678']);

    $this->get(profileUrl($this->partner))
        ->assertOk()
        ->assertSee('data-reports-notice', false)
        ->assertSee('Có 2 tố cáo đã được duyệt liên quan đến kênh của thành viên này')
        ->assertSee(route('search.index', ['q' => '0912345678']), false)
        ->assertDontSee('Chưa có tố cáo được duyệt nhắm vào các kênh chính thức');
});

test('official channels are grouped by type with verify links and copy buttons', function () {
    InsuranceChannel::factory()->for($this->partner)->ofType(ChannelType::Telegram, '@gdv_quang')->create(['verified_at' => '2026-03-02 10:00:00']);
    InsuranceChannel::factory()->for($this->partner)->ofType(ChannelType::Phone, '0912345678')->create();
    InsuranceChannel::factory()->for($this->partner)->ofType(ChannelType::Phone, '0987654321')->unofficial()->create();
    InsuranceChannel::factory()->for($this->partner)->ofType(ChannelType::BankAccount, '0123456789')->create();

    $this->get(profileUrl($this->partner))
        ->assertOk()
        ->assertSee('Kênh liên hệ chính thức')
        ->assertSeeInOrder([ChannelType::Phone->label(), '0912345678', ChannelType::Telegram->label(), '@gdv_quang'])
        ->assertSee('Xác minh ngày 02/03/2026')
        ->assertSee(route('intermediary.verify', ['q' => '@gdv_quang']), false)
        ->assertSee(route('intermediary.verify', ['q' => '0912345678']), false)
        ->assertSee('data-copy="@gdv_quang"', false)
        ->assertSeeInOrder(['Tài khoản thanh toán', 'Vietcombank', '0123456789', 'NGUYEN VAN QUANG'])
        ->assertSee(route('intermediary.verify', ['q' => '0123456789']), false)
        ->assertDontSee('0987654321');
});

test('the anti-impersonation callout shows the official profile address', function () {
    $url = profileUrl($this->partner);

    $this->get($url)
        ->assertOk()
        ->assertSee('Chỉ giao dịch qua các kênh liệt kê trên trang này. Mọi tài khoản, số điện thoại hoặc link khác đều không phải')
        ->assertSee(preg_replace('~^https?://~', '', $url))
        ->assertSee('data-copy="'.$url.'"', false);
});

test('the share box links to facebook and telegram with the encoded profile url', function () {
    $url = profileUrl($this->partner);
    $this->get($url)
        ->assertOk()
        ->assertSee('Chia sẻ hồ sơ xác thực')
        ->assertSee('https://www.facebook.com/sharer/sharer.php?u='.urlencode($url), false)
        ->assertSee('https://t.me/share/url?url='.urlencode($url), false)
        ->assertSee('data-share="zalo"', false)
        ->assertSee("Giao dịch viên số {$this->partner->gdv_number} đã xác thực bởi Scamproof · Xem hồ sơ: {$url}");
});

test('the head carries title, canonical, robots, open graph, twitter and json-ld tags', function () {
    InsuranceChannel::factory()->for($this->partner)->ofType(ChannelType::Phone, '0912345678')->create();
    InsuranceChannel::factory()->for($this->partner)->ofType(ChannelType::BankAccount, '0123456789')->create();
    $url = profileUrl($this->partner);

    $head = profileHead($this->get($url)->assertOk()->getContent());

    expect($head)
        ->toContain("<title>GDV Quang - Giao dịch viên số {$this->partner->gdv_number} đã xác thực | Scamproof</title>")
        ->toContain('<link rel="canonical" href="'.$url.'">')
        ->toContain('<meta property="og:type" content="profile">')
        ->toContain('<meta property="og:url" content="'.$url.'">')
        ->toContain('<meta property="og:image" content="https://cdn.example.test/avatars/quang.jpg">')
        ->toContain('<meta property="og:image:alt" content="Ảnh đại diện của GDV Quang">')
        ->toContain('<meta name="twitter:card" content="summary_large_image">')
        ->toContain('<meta name="twitter:image" content="https://cdn.example.test/avatars/quang.jpg">')
        ->toContain('"@type":"ProfilePage"')
        ->toContain('"@type":"Person"')
        ->toContain('"identifier":"GDV số '.$this->partner->gdv_number.'"')
        ->not->toContain('0912345678')
        ->not->toContain('0123456789');

    expect(substr_count($head, 'name="robots"'))->toBe(1)
        ->and($head)->toContain('<meta name="robots" content="index, follow">')
        ->and(substr_count($head, 'property="og:image"'))->toBe(1);
});

test('a partner without avatar uses the brand logo as absolute share image', function () {
    $this->partner->update(['avatar' => null]);

    $head = profileHead($this->get(profileUrl($this->partner))->assertOk()->getContent());

    expect($head)
        ->toContain('<meta property="og:image" content="'.asset('img/scamproof-logo.png').'">')
        ->toContain('<meta property="og:image:width" content="900">')
        ->toContain('<meta property="og:image:height" content="300">');
});

test('a suspended or expired partner shows a neutral status without the reason and is noindex', function (Closure $makePartner, string $label) {
    $partner = $makePartner();

    $response = $this->get(profileUrl($partner))
        ->assertOk()
        ->assertSee($label)
        ->assertSee('data-lapsed-notice', false)
        ->assertDontSee('Giao dịch viên đã xác thực')
        ->assertDontSee('Scamproof đã kiểm tra')
        ->assertDontSee('Chia sẻ hồ sơ xác thực')
        ->assertDontSee('LÝ DO NỘI BỘ BÍ MẬT');

    $head = profileHead($response->getContent());

    expect($head)->toContain('<meta name="robots" content="noindex, follow">')
        ->toContain($partner->full_name." - Hồ sơ giao dịch viên số {$partner->gdv_number} | Scamproof")
        ->and(substr_count($head, 'name="robots"'))->toBe(1);
})->with([
    'suspended' => [fn () => Insurance::factory()->trusted()->suspended('LÝ DO NỘI BỘ BÍ MẬT')->create(), 'Tạm đình chỉ'],
    'expired' => [fn () => Insurance::factory()->expired()->create(), 'Hết hạn'],
]);

test('an inactive partner has no public profile', function () {
    $this->partner->update(['status' => 0]);

    $this->get(profileUrl($this->partner))->assertNotFound();
});

test('the profile runs a bounded number of queries regardless of channel count', function () {
    $countQueries = function (): int {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->get(profileUrl($this->partner))->assertOk();
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    };

    InsuranceChannel::factory()->for($this->partner)->create();
    $countQueries(); // warm up settings and other per-process caches
    $small = $countQueries();

    InsuranceChannel::factory()->count(6)->for($this->partner)->create();
    InsuranceChannel::factory()->for($this->partner)->ofType(ChannelType::BankAccount, '0123456789')->create();
    $large = $countQueries();

    expect($large)->toBe($small)->toBeLessThanOrEqual(12);
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
