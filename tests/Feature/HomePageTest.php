<?php

use App\Enums\ScamType;
use App\Helpers\ConfigHelper;
use App\Helpers\StatsHelper;
use App\Models\Banner;
use App\Models\Comment;
use App\Models\Insurance;
use App\Models\Report;
use App\Models\SearchLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
});

function homeApprovedReport(array $attributes = []): Report
{
    return Report::factory()->create(array_merge([
        'status' => 'approved',
        'type' => 'account',
        'target_id' => '0912.345.678',
        'target_name' => 'Nguyễn Văn Tèo',
        'created_at' => now()->subDay(),
    ], $attributes));
}

test('home renders hero and the trust sections in order', function () {
    $report = homeApprovedReport();
    Comment::factory()->count(2)->create(['report_id' => $report->id]);
    Insurance::factory()->create(['full_name' => 'Trần Bảo Hiểm', 'status' => 1, 'expired_at' => now()->addMonth()]);

    $html = $this->get('/')
        ->assertOk()
        ->assertSee('Dữ liệu cộng đồng, được kiểm duyệt')
        ->assertSee('Tra cứu SĐT, số tài khoản, Facebook hoặc link trước khi chuyển tiền.')
        ->assertSee('id="searchInput"', false)
        ->assertSee('action="'.route('search.index').'"', false)
        ->assertSee('method="GET"', false)
        ->assertSee('role="combobox"', false)
        ->assertSee('for="searchInput"', false)
        ->assertSee(json_encode(route('search.autocomplete')), false)
        ->assertDontSee('CẢNH BÁO')
        ->assertSeeInOrder([
            'id="hero-title"',
            'id="searchInput"',
            'Tra cứu miễn phí, không cần đăng ký',
            'Mọi tố cáo đều qua kiểm duyệt',
            'Không công khai danh tính người báo cáo',
            'Cảnh báo mới nhất',
            'Lối tắt',
            'Cách Scamproof hoạt động',
            'Chúng tôi kiểm duyệt như thế nào',
            'Tìm kiếm nhiều trong tuần',
            'Bình luận mới',
            'Trung gian đã ký quỹ',
            'Cam kết của chúng tôi',
            'Cảnh giác mạo danh Scamproof',
            'Câu hỏi thường gặp',
        ], false)
        ->assertSee('Trần Bảo Hiểm')
        ->assertSee('Tham gia cộng đồng Scamproof')
        ->assertSee('href="'.route('moderation.policy').'"', false)
        ->assertSee('091***678')
        ->assertDontSee('0912.345.678')
        ->assertDontSee('Nguyễn Văn Tèo')
        ->getContent();

    $heading = str($html)->after('id="hero-title"')->before('</h1>');

    expect((string) $heading)->toContain('Kiểm tra &amp; tố cáo', 'scam.')
        ->and((string) $heading)->not->toContain('<br');
});

/**
 * Approved reports with distinct masked ids, newest first: index 0 was approved $index + 1 hours ago.
 *
 * @return list<Report>
 */
function homeApprovedReports(int $count): array
{
    return collect(range(0, $count - 1))
        ->map(fn (int $index): Report => homeApprovedReport([
            'target_id' => '09'.str_pad((string) ($index + 1), 8, '0', STR_PAD_LEFT),
            'target_name' => 'Đối Tượng Số'.($index + 1),
            'slug' => 'doi-tuong-'.($index + 1),
            'approved_at' => now()->subHours($index + 1),
        ]))
        ->all();
}

function heroFeedHtml(string $html): string
{
    return (string) str($html)->after('data-hero-feed')->before('</section>');
}

test('hero feed shows the five newest approved reports in order with masked data only', function () {
    $reports = homeApprovedReports(6);
    Report::factory()->create(['status' => 'pending', 'slug' => 'cho-duyet-1', 'approved_at' => now()]);

    $feed = heroFeedHtml($this->get('/')->assertOk()->getContent());

    expect(substr_count($feed, 'data-hero-feed-row'))->toBe(5);

    preg_match_all('/href="([^"]*)"/', $feed, $matches);

    expect($matches[1])->toBe([
        ...array_map(fn (Report $report): string => route('scammer.show', $report->slug), array_slice($reports, 0, 5)),
        route('warnings.index'),
    ]);

    expect($feed)->toContain('Xem tất cả cảnh báo', 'Tài khoản', '090***001', 'Đối Tượng S.')
        ->not->toContain('0900000001', 'Đối Tượng Số1', 'doi-tuong-6', 'cho-duyet-1');
});

test('hero feed shows type, scam type and relative time from approved_at', function () {
    homeApprovedReport([
        'scam_type' => ScamType::Investment,
        'approved_at' => now()->subMinutes(5),
        'created_at' => now()->subDays(20),
    ]);
    homeApprovedReport([
        'type' => 'website',
        'target_id' => 'https://lua-dao.example',
        'scam_type' => null,
        'category' => 'Lừa đảo nạp thẻ game',
        'approved_at' => now()->subHours(2),
    ]);

    $feed = heroFeedHtml($this->get('/')->assertOk()->getContent());

    expect($feed)->toContain('data-hero-feed-updated', 'Cập nhật', '5 phút trước', '2 giờ trước')
        ->toContain(ScamType::Investment->label(), 'Lừa đảo nạp thẻ game', 'Website')
        ->not->toContain('20 ngày trước');

    expect(strpos($feed, '5 phút trước'))->toBeLessThan(strpos($feed, '2 giờ trước'));
});

test('hero feed shows the empty state and no update pill without approved reports', function () {
    Report::factory()->create(['status' => 'pending']);

    $feed = heroFeedHtml($this->get('/')->assertOk()->getContent());

    expect($feed)->toContain('Chưa có cảnh báo nào được duyệt', 'href="'.route('warnings.index').'"')
        ->not->toContain('data-hero-feed-updated', 'Cập nhật', 'data-hero-feed-row');
});

test('hero feed and the lower table share one reports query', function () {
    homeApprovedReports(8);

    DB::enableQueryLog();
    $this->get('/')->assertOk();

    $feedQueries = collect(DB::getQueryLog())
        ->pluck('query')
        ->filter(fn (string $query): bool => str_contains($query, 'from `reports`') && str_contains($query, 'order by `approved_at` desc'));

    expect($feedQueries)->toHaveCount(1)
        ->and($feedQueries->first())->toContain('limit 15');
});

test('lower table is hidden when the hero already shows every approved report', function () {
    homeApprovedReports(5);

    $this->get('/')
        ->assertOk()
        ->assertDontSee('Thêm cảnh báo gần đây')
        ->assertDontSee('data-latest-reports', false);
});

test('lower table lists only the reports after the hero five, up to ten more', function () {
    $reports = homeApprovedReports(16);

    $html = $this->get('/')->assertOk()->assertSee('Thêm cảnh báo gần đây')->getContent();
    $table = (string) str($html)->after('data-latest-reports')->before('</ul>');

    preg_match_all('/href="([^"]*)"/', $table, $matches);

    expect($matches[1])->toBe(
        array_map(fn (Report $report): string => route('scammer.show', $report->slug), array_slice($reports, 5, 10)),
    );
});

test('lower table shows scam type, evidence count and approval date', function () {
    homeApprovedReports(5);
    homeApprovedReport([
        'scam_type' => ScamType::Investment,
        'evidence_images' => ['a.jpg', 'b.jpg', 'c.jpg'],
        'approved_at' => '2026-11-10 08:00:00',
    ]);
    homeApprovedReport([
        'target_id' => '1903456789012',
        'scam_type' => null,
        'category' => 'Lừa đảo nạp thẻ game',
        'evidence_images' => [],
        'approved_at' => '2026-11-08 08:00:00',
    ]);

    $html = $this->get('/')->assertOk()->getContent();
    $table = (string) str($html)->after('data-latest-reports')->before('</ul>');

    expect($table)->toContain(ScamType::Investment->label(), 'Lừa đảo nạp thẻ game', '10/11/2026', '08/11/2026')
        ->toContain('data-evidence-count="3"', '3 ảnh')
        ->not->toContain('data-evidence-count="0"');

    expect(strpos($table, '10/11/2026'))->toBeLessThan(strpos($table, '08/11/2026'));
});

test('partner cards show deposit, validity, services and profile link without complaint counts', function () {
    $insurance = Insurance::factory()->create([
        'full_name' => 'Võ Thị Hồng',
        'status' => 1,
        'amount' => 500000000,
        'expired_at' => '2027-12-31',
        'insurance_date' => '2026-01-15',
        'services' => [['title' => 'Trung gian giao dịch'], ['title' => 'Nhận cọc']],
    ]);
    Insurance::factory()->create(['full_name' => 'Không Thời Hạn', 'status' => 1, 'amount' => 400000000, 'expired_at' => null]);

    $html = $this->get('/')->assertOk()->getContent();
    $section = (string) str($html)->after('aria-label="Trung gian đã ký quỹ"')->before('</section>');

    expect($section)->toContain('Võ Thị Hồng', 'Đã xác thực', 'Quỹ bảo hiểm', '500.000.000 đ', 'Hiệu lực đến', '31/12/2027')
        ->toContain('Dịch vụ', 'Trung gian giao dịch, Nhận cọc', 'Xác thực từ 15/01/2026')
        ->toContain('Không thời hạn')
        ->toContain('href="'.route('insurances.frontend.show', $insurance->slug).'"', 'Xem hồ sơ')
        ->not->toContain('khiếu nại');

    expect(substr_count($section, 'data-partner-card'))->toBe(2);
});

test('official channels are built only from configured values', function () {
    ConfigHelper::setConfig('telegram_link', 'https://t.me/kenh_that');
    ConfigHelper::setConfig('hotline', '0900.111.222');
    ConfigHelper::setConfig('zalo_link', '');
    ConfigHelper::setConfig('support_email', 'hotro@example.test');

    $html = $this->get('/')->assertOk()->getContent();
    $box = (string) str($html)->after('aria-label="Cảnh giác mạo danh Scamproof"')->before('</section>');

    expect($box)->toContain('không bao giờ', 'mã OTP')
        ->toContain(parse_url(config('app.url'), PHP_URL_HOST), 't.me/kenh_that', '0900.111.222', 'hotro@example.test')
        ->not->toContain('zalo');

    expect(substr_count($box, 'data-official-channel'))->toBe(4);

    expect($html)->not->toContain('aria-label="Đơn vị vận hành"')
        ->not->toContain('Vận hành bởi MST Software');
});

test('commitments describe reporter privacy without claiming absolute anonymity', function () {
    $html = $this->get('/')->assertOk()->getContent();
    $section = (string) str($html)->after('aria-label="Cam kết của chúng tôi"')->before('</section>');

    expect($section)->toContain(
        'Không công khai người báo cáo',
        'chỉ quản trị viên xem được để xác minh',
        'Quyền phản hồi cho cả hai bên',
        'href="'.route('complaints.create').'"',
        'Miễn phí cho cộng đồng',
        'Minh bạch chính sách',
        'href="'.url('/dieu-khoan').'"',
        'href="'.route('moderation.policy').'"',
    )->not->toContain('tuyệt đối', 'bên thứ ba');
});

test('the home_top banner is rendered at the end, after the faq', function () {
    Banner::factory()->create([
        'title' => 'Banner Cuối Trang',
        'image_path' => 'https://example.test/home-top.png',
        'position' => 'home_top',
        'type' => 'horizontal',
    ]);

    $this->get('/')
        ->assertOk()
        ->assertSeeInOrder(['Câu hỏi thường gặp', 'https://example.test/home-top.png', '<footer'], false)
        ->assertDontSee('Quảng cáo');
});

test('home html never prints raw target identifiers', function () {
    homeApprovedReport([
        'target_id' => '0912345678',
        'target_name' => 'Phạm Thị Bí Mật',
        'slug' => 'pham-thi-b-abc12345',
    ]);
    homeApprovedReport([
        'type' => 'website',
        'target_id' => 'facebook.com/le.hoang.anh2000',
        'target_name' => 'Lê Hoàng Anh',
        'slug' => 'le-hoang-a-xyz98765',
    ]);

    $this->get('/')
        ->assertOk()
        ->assertSee('091***678')
        ->assertDontSee('0912345678')
        ->assertDontSee('Phạm Thị Bí Mật')
        ->assertDontSee('le.hoang.anh2000');
});

test('home hero heading carries the scanner line effect', function () {
    $html = $this->get('/')->assertOk()->getContent();

    expect($html)->toMatch('/<h1\s+id="hero-title"\s+class="[^"]*\bscanner-title\b[^"]*"/');
});

test('home hero shows hint chips that only change the placeholder', function () {
    $html = $this->get('/')->assertOk()->getContent();

    $chips = str($html)->after('aria-label="Loại thông tin cần kiểm tra"')->before('</div>');

    expect((string) $chips)->toContain('Thử tra cứu:', 'SĐT', 'Số tài khoản', 'Facebook', 'Website')
        ->and(substr_count($chips, 'data-search-placeholder='))->toBe(4)
        ->and(substr_count($chips, 'type="button"'))->toBe(4);

    expect($html)->toContain('aria-describedby="search-hint"', 'id="search-hint"');
});

test('home hero shortcuts link to real pages', function () {
    $html = $this->get('/')->assertOk()->getContent();

    $nav = str($html)->after('aria-label="Lối tắt"')->before('</nav>');

    preg_match_all('/href="([^"]*)"/', $nav, $matches);

    expect($matches[1])->toBe([
        route('reports'),
        route('insurances.frontend.index'),
        route('intermediary.verify'),
        route('newfeed.index'),
        url('/api-checkscam'),
    ])->not->toContain('#');

    expect((string) $nav)->toContain('Tố cáo scam', 'Bảo hiểm CS', 'Kiểm tra GDV', 'Chợ buôn bán', 'Bot check');

    expect($html)->not->toContain('aria-label="Dữ liệu trên hệ thống"');
});

test('search results keep the compact search bar without the home hero', function () {
    homeApprovedReport();

    $this->get(route('search.index', ['q' => '0912345678']))
        ->assertOk()
        ->assertSee('id="searchInput"', false)
        ->assertSee('value="0912345678"', false)
        ->assertSee('action="'.route('search.index').'"', false)
        ->assertSee(json_encode(route('search.autocomplete')), false)
        ->assertDontSee('id="hero-title"', false)
        ->assertDontSee('Thử tra cứu:')
        ->assertDontSee('aria-label="Dữ liệu trên hệ thống"', false)
        ->assertDontSee('aria-label="Lối tắt"', false)
        ->assertDontSee('data-hero-feed', false);
});

test('scammer page shows only the compact search bar', function () {
    $report = homeApprovedReport();

    $this->get(route('scammer.show', $report->slug))
        ->assertOk()
        ->assertSee('id="searchInput"', false)
        ->assertSee('action="'.route('search.index').'"', false)
        ->assertDontSee('id="hero-title"', false)
        ->assertDontSee('Thử tra cứu:')
        ->assertDontSee('aria-label="Lối tắt"', false)
        ->assertDontSee('data-hero-feed', false);
});

test('weekly top searches only list reported targets searched in the last 7 days', function () {
    homeApprovedReport();
    SearchLog::create(['search_query' => '0912.345.678', 'is_found' => true, 'ip_address' => '10.0.0.1']);
    SearchLog::create(['search_query' => 'random keyword', 'is_found' => false, 'ip_address' => '10.0.0.2']);

    $topSearches = StatsHelper::getTopWeeklySearches();

    expect($topSearches)->toHaveCount(1)
        ->and($topSearches->first()->search_count)->toBe(1);
});

test('weekly top searches group phone variants of the same target', function () {
    $report = homeApprovedReport();
    homeApprovedReport(['target_id' => '1903456789012', 'target_name' => 'Trần Văn B', 'created_at' => now()->subDays(2)]);
    foreach (['0912345678', '+84912345678', '0912 345 678', '1903456789012'] as $index => $query) {
        SearchLog::create(['search_query' => $query, 'is_found' => true, 'ip_address' => '10.0.0.'.$index]);
    }

    $topSearches = StatsHelper::getTopWeeklySearches();

    expect($topSearches)->toHaveCount(2)
        ->and($topSearches->first()->slug)->toBe($report->slug)
        ->and($topSearches->first()->search_count)->toBe(3)
        ->and($topSearches->last()->search_count)->toBe(1);
});

test('search for a reported phone shows the verdict with masked data only', function () {
    homeApprovedReport();

    $this->get(route('search.index', ['q' => '0912345678']))
        ->assertOk()
        ->assertSee('Kết quả kiểm tra · Số điện thoại')
        ->assertSee('Có tố cáo')
        ->assertSee('Tố cáo liên quan')
        ->assertSee('091***678')
        ->assertSee('Nguyễn Văn T.')
        ->assertDontSee('0912.345.678')
        ->assertDontSee('Nguyễn Văn Tèo');
});

test('search with three or more reports is flagged as many reports', function () {
    homeApprovedReport();
    homeApprovedReport();
    homeApprovedReport();

    $this->get(route('search.index', ['q' => '0912345678']))
        ->assertOk()
        ->assertSee('Nhiều tố cáo');
});

test('search without matches shows the empty state and report cta', function () {
    $this->get(route('search.index', ['q' => '0999000111']))
        ->assertOk()
        ->assertSee('Chưa có tố cáo')
        ->assertSee('Chưa có tố cáo trong hệ thống cho thông tin này.')
        ->assertSee('Chưa có dữ liệu về thông tin này')
        ->assertSee(route('reports'), false)
        ->assertDontSee('Tố cáo liên quan');
});

test('autocomplete endpoint still returns json suggestions', function () {
    homeApprovedReport();

    $this->getJson(route('search.autocomplete', ['q' => '0912']))
        ->assertOk()
        ->assertJsonFragment(['type' => 'account', 'target_name' => 'Nguyễn Văn T.']);
});

test('load more comments returns only the masked comment items partial', function () {
    $report = homeApprovedReport();
    Comment::factory()->count(10)->create(['report_id' => $report->id]);

    $response = $this->get('/?page=2', ['X-Requested-With' => 'XMLHttpRequest']);

    $response->assertOk()
        ->assertSee('data-comment-item', false)
        ->assertSee('091***678')
        ->assertDontSee('0912.345.678')
        ->assertDontSee('<html', false);

    expect(substr_count($response->getContent(), 'data-comment-item'))->toBe(2);
});
