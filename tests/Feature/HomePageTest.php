<?php

use App\Helpers\StatsHelper;
use App\Models\Comment;
use App\Models\Insurance;
use App\Models\Report;
use App\Models\SearchLog;
use Illuminate\Foundation\Testing\RefreshDatabase;

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

test('home renders hero, real stats and sections', function () {
    $report = homeApprovedReport();
    Comment::factory()->count(2)->create(['report_id' => $report->id]);
    Insurance::factory()->create(['full_name' => 'Trần Bảo Hiểm', 'status' => 1, 'expired_at' => now()->addMonth()]);

    $this->get('/')
        ->assertOk()
        ->assertSee('KIỂM TRA &amp; TỐ CÁO SCAM.', false)
        ->assertSee('Hệ thống dữ liệu lớn nhất Việt Nam giúp bạn kiểm tra độ tín nhiệm của đối tác')
        ->assertSee('id="searchInput"', false)
        ->assertSee('action="'.route('search.index').'"', false)
        ->assertSee(json_encode(route('search.autocomplete')), false)
        ->assertSeeInOrder(['1', 'Kẻ lừa đảo', '2', 'Bình luận'])
        ->assertSee(now()->format('d/m/Y').' CÓ 1 CẢNH BÁO')
        ->assertSeeInOrder(['KIỂM TRA', 'id="searchInput"', 'Lối tắt', 'CẢNH BÁO', 'Cảnh báo mới nhất', 'Bình luận mới', 'Đối tác tin cậy'], false)
        ->assertSee('Cảnh báo mới nhất')
        ->assertSee('Tìm kiếm nhiều trong tuần')
        ->assertSee('Bình luận mới')
        ->assertSee('Đối tác tin cậy')
        ->assertSee('Trần Bảo Hiểm')
        ->assertSee('Tham gia cộng đồng Scamproof')
        ->assertSee('091***678')
        ->assertDontSee('0912.345.678')
        ->assertDontSee('Nguyễn Văn Tèo');
});

test('home hero action cards link to real pages', function () {
    $html = $this->get('/')->assertOk()->getContent();

    $nav = str($html)->after('aria-label="Lối tắt"')->before('</nav>');

    preg_match_all('/href="([^"]*)"/', $nav, $matches);

    expect($matches[1])->toBe([
        route('reports'),
        route('insurances.frontend.index'),
        route('newfeed.index'),
        url('/api-checkscam'),
    ])->not->toContain('#');

    expect((string) $nav)->toContain('Tố cáo Scam', 'Bảo hiểm CS', 'Chợ buôn bán', 'Bot Check');
});

test('search results keep the compact search bar without the home hero', function () {
    homeApprovedReport();

    $this->get(route('search.index', ['q' => '0912345678']))
        ->assertOk()
        ->assertSee('id="searchInput"', false)
        ->assertSee('value="0912345678"', false)
        ->assertSee(json_encode(route('search.autocomplete')), false)
        ->assertDontSee('KIỂM TRA &amp; TỐ CÁO SCAM.', false)
        ->assertDontSee('aria-label="Lối tắt"', false);
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
