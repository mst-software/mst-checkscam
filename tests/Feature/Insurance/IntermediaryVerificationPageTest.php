<?php

use App\Enums\ChannelType;
use App\Http\Controllers\IntermediaryVerificationController;
use App\Models\Insurance;
use App\Models\InsuranceChannel;
use App\Models\Report;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    RateLimiter::clear('verify-intermediary:127.0.0.1');
});

function verifyPartner(Insurance $insurance, string $phone = '0912345678'): Insurance
{
    InsuranceChannel::factory()->for($insurance)->ofType(ChannelType::Phone, $phone)->create();

    return $insurance;
}

/**
 * @return list<string>
 */
function verifyRobotsMeta(string $html): array
{
    preg_match_all('/<meta\s+name="robots"\s+content="([^"]*)"/i', $html, $matches);

    return $matches[1];
}

test('guests can open the verify page without a query', function () {
    $html = $this->get(route('intermediary.verify'))
        ->assertOk()
        ->assertSee('Kiểm tra giao dịch viên')
        ->assertSee('SĐT, @telegram, link Facebook/Zalo hoặc số tài khoản của người bạn sắp giao dịch')
        ->assertSee('Cảnh giác mạo danh Scamproof')
        ->assertDontSee('data-verify-state', false)
        ->getContent();

    expect($html)->toContain('<title>Kiểm tra giao dịch viên - Scamproof</title>')
        ->and(verifyRobotsMeta($html))->not->toContain('noindex, follow');
});

test('an official channel of a trusted partner is shown as verified', function () {
    $partner = verifyPartner(Insurance::factory()->trusted()->create([
        'full_name' => 'GDV Hùng',
        'amount' => 25000000,
        'expired_at' => now()->addYear()->startOfDay(),
        'verified_at' => '2026-01-15',
    ]));

    $html = $this->get(route('intermediary.verify', ['q' => '+84 912 345 678']))
        ->assertOk()
        ->assertSee('data-verify-state="verified"', false)
        ->assertSee('Kênh GDV chính thức')
        ->assertSee('GDV Hùng')
        ->assertSee('Đã xác thực')
        ->assertSee('25.000.000 đ')
        ->assertSee($partner->expired_at->format('d/m/Y'))
        ->assertSee('15/01/2026')
        ->assertSee(ChannelType::Phone->label())
        ->assertSee(route('insurances.frontend.show', $partner->slug))
        ->assertSee(route('insurance-claims.create', ['partner' => $partner->slug]), false)
        ->getContent();

    $resultSection = str($html)->after('data-verify-state')->before('</section>')->lower()->toString();

    expect($resultSection)->not->toContain('an toàn')->not->toContain('safe');
});

test('a channel of a suspended partner is shown as lapsed with its reason', function () {
    $partner = verifyPartner(Insurance::factory()->trusted()->suspended()->create(['full_name' => 'GDV Lan']));

    $this->get(route('intermediary.verify', ['q' => '0912345678']))
        ->assertOk()
        ->assertSee('data-verify-state="lapsed"', false)
        ->assertSee('Từng là GDV của Scamproof nhưng hiện không còn hiệu lực')
        ->assertSee('GDV đang bị tạm đình chỉ')
        ->assertSee('Tạm đình chỉ')
        ->assertSee(route('insurances.frontend.show', $partner->slug))
        ->assertDontSee('Đã xác thực');
});

test('an inactive partner is lapsed without a link to its hidden profile', function () {
    $partner = verifyPartner(Insurance::factory()->create(['status' => 0]));

    $this->get(route('intermediary.verify', ['q' => '0912345678']))
        ->assertOk()
        ->assertSee('data-verify-state="lapsed"', false)
        ->assertSee('GDV đã ngừng hoạt động')
        ->assertDontSee(route('insurances.frontend.show', $partner->slug));
});

test('an unknown channel is not listed and points to the report search', function () {
    $this->get(route('intermediary.verify', ['q' => '0987654321']))
        ->assertOk()
        ->assertSee('data-verify-state="not_listed"', false)
        ->assertSee('Không phải kênh GDV chính thức của Scamproof.')
        ->assertSee('Chỉ giao dịch qua trung gian đã xác thực; kiểm tra thêm bằng')
        ->assertSee('ô tra cứu tố cáo')
        ->assertDontSee('data-reports-warning', false);
});

test('approved reports on the same identifier are always warned about', function (bool $listed) {
    if ($listed) {
        verifyPartner(Insurance::factory()->trusted()->create());
    }
    Report::factory()->count(2)->create(['status' => 'approved', 'type' => 'account', 'target_id' => '0912345678']);

    $this->get(route('intermediary.verify', ['q' => '0912345678']))
        ->assertOk()
        ->assertSee('data-reports-warning', false)
        ->assertSee('Có 2 tố cáo đã duyệt liên quan đến thông tin này.')
        ->assertSee(route('search.index', ['q' => '0912345678']), false);
})->with(['verified channel' => true, 'unknown channel' => false]);

test('result pages are noindex with a single robots meta', function () {
    $html = $this->get(route('intermediary.verify', ['q' => '0912345678']))->assertOk()->getContent();

    expect(verifyRobotsMeta($html))->toBe(['noindex, follow']);
});

test('raw input is escaped in the page', function () {
    $this->get(route('intermediary.verify', ['q' => '<script>alert(1)</script>']))
        ->assertOk()
        ->assertDontSee('<script>alert(1)</script>', false)
        ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
});

test('checks are throttled per ip', function () {
    // The limiter window is one minute: freeze time so a minute rollover cannot reset it mid-test.
    $this->freezeTime();

    for ($i = 0; $i < IntermediaryVerificationController::MAX_CHECKS_PER_MINUTE; $i++) {
        $this->get(route('intermediary.verify', ['q' => '0987654321']))->assertOk();
    }

    $this->get(route('intermediary.verify', ['q' => '0987654321']))
        ->assertStatus(429)
        ->assertHeader('Retry-After')
        ->assertSee('Bạn đã kiểm tra quá nhiều lần trong một phút.')
        ->assertDontSee('data-verify-state', false);

    // Opening the empty page does not count and still works.
    $this->get(route('intermediary.verify'))->assertOk();
});

test('the verify page is linked from the header and listed in the sitemap', function () {
    $this->get(route('home'))->assertOk()->assertSee(route('intermediary.verify'));

    $this->get(route('sitemap'))->assertOk()->assertSee(url('/verify-intermediary'));
});
