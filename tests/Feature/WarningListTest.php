<?php

use App\Models\Comment;
use App\Models\Report;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
});

afterEach(function () {
    Model::preventLazyLoading(false);
});

function warningReport(array $attributes = []): Report
{
    return Report::factory()->create(array_merge([
        'type' => 'account',
        'target_id' => '1903456789',
        'target_name' => 'Trần Thị Thuỷ',
        'target_bank' => 'Techcombank',
        'status' => 'approved',
        'view_count' => 10,
        'slug' => 'warning-'.Str::lower(Str::random(10)),
        'created_at' => now()->subDay(),
    ], $attributes));
}

test('the warnings page is served above the catch-all report route', function () {
    warningReport(['slug' => 'warnings-shadow']);

    $routes = collect(Route::getRoutes()->getRoutes())->map(fn ($route) => $route->getName())->values();

    expect($routes->search('warnings.index'))->toBeLessThan($routes->search('scammer.show'));

    $this->get('/warnings')
        ->assertOk()
        ->assertViewIs('warnings.index')
        ->assertSee('Danh sách cảnh báo');
});

test('the header navigation links to the warnings page', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee(route('warnings.index'), false)
        ->assertSee('Cảnh báo');
});

test('only approved reports are listed', function () {
    $approved = warningReport();
    $pending = warningReport(['status' => 'pending']);
    $rejected = warningReport(['status' => 'rejected']);

    $this->get(route('warnings.index'))
        ->assertOk()
        ->assertSee($approved->slug)
        ->assertDontSee($pending->slug)
        ->assertDontSee($rejected->slug)
        ->assertSee('Tìm thấy');

    expect($this->get(route('warnings.index'))->viewData('reports')->total())->toBe(1);
});

test('reports are filtered by derived type', function (string $type, string $expectedSlug) {
    warningReport(['slug' => 'bank-report', 'target_id' => '1903456789', 'target_bank' => 'Techcombank']);
    warningReport(['slug' => 'phone-report', 'target_id' => '0938111222', 'target_bank' => null]);
    warningReport(['slug' => 'facebook-report', 'type' => 'website', 'target_id' => 'facebook.com/le.hoang.anh2000', 'target_bank' => null]);

    $reports = $this->get(route('warnings.index', ['type' => $type]))->assertOk()->viewData('reports');

    expect($reports->pluck('slug')->all())->toBe([$expectedSlug]);
})->with([
    'bank' => ['bank', 'bank-report'],
    'phone' => ['phone', 'phone-report'],
    'facebook' => ['facebook', 'facebook-report'],
]);

test('type all and unknown filter values list everything', function (array $query) {
    warningReport(['target_bank' => 'Techcombank']);
    warningReport(['target_id' => '0938111222', 'target_bank' => null]);

    $response = $this->get(route('warnings.index', $query))->assertOk();

    expect($response->viewData('reports')->total())->toBe(2)
        ->and($response->viewData('filters'))->toMatchArray(['type' => 'all', 'sort' => 'newest', 'period' => null, 'bank' => '']);
})->with([
    'all' => [['type' => 'all']],
    'garbage' => [['type' => 'foo', 'sort' => 'bar', 'period' => '13', 'bank' => 'Không tồn tại']],
]);

test('reports are filtered by bank', function () {
    warningReport(['slug' => 'vcb-report', 'target_bank' => 'Vietcombank']);
    warningReport(['slug' => 'mb-report', 'target_bank' => 'MBBank']);

    $response = $this->get(route('warnings.index', ['bank' => 'Vietcombank']))->assertOk();

    expect($response->viewData('reports')->pluck('slug')->all())->toBe(['vcb-report']);
    $response->assertSee('<option value="Vietcombank" selected', false);
});

test('reports are filtered by date range', function (int $days, array $expected) {
    warningReport(['slug' => 'recent', 'created_at' => now()->subDays(3)]);
    warningReport(['slug' => 'month', 'created_at' => now()->subDays(20)]);
    warningReport(['slug' => 'quarter', 'created_at' => now()->subDays(60)]);
    warningReport(['slug' => 'old', 'created_at' => now()->subDays(200)]);

    $reports = $this->get(route('warnings.index', ['period' => $days]))->assertOk()->viewData('reports');

    expect($reports->pluck('slug')->all())->toBe($expected);
})->with([
    '7 days' => [7, ['recent']],
    '30 days' => [30, ['recent', 'month']],
    '90 days' => [90, ['recent', 'month', 'quarter']],
]);

test('reports are newest first by default', function () {
    warningReport(['slug' => 'older', 'created_at' => now()->subDays(5)]);
    warningReport(['slug' => 'newer', 'created_at' => now()->subHour()]);

    $this->get(route('warnings.index'))->assertOk()->assertSeeInOrder(['newer', 'older']);
});

test('reports can be sorted by views and by comments', function () {
    $quiet = warningReport(['slug' => 'quiet', 'view_count' => 5, 'created_at' => now()->subHour()]);
    $viewed = warningReport(['slug' => 'viewed', 'view_count' => 900, 'created_at' => now()->subDays(3)]);
    $discussed = warningReport(['slug' => 'discussed', 'view_count' => 50, 'created_at' => now()->subDays(5)]);
    Comment::factory()->count(3)->create(['report_id' => $discussed->id]);

    $byViews = $this->get(route('warnings.index', ['sort' => 'views']))->viewData('reports');
    $byComments = $this->get(route('warnings.index', ['sort' => 'comments']))->viewData('reports');

    expect($byViews->pluck('slug')->all())->toBe(['viewed', 'discussed', 'quiet'])
        ->and($byComments->pluck('slug')->first())->toBe('discussed')
        ->and($byComments->first()->comments_count)->toBe(3);
});

test('pagination shows 20 per page and keeps the query string', function () {
    foreach (range(1, 25) as $i) {
        warningReport(['created_at' => now()->subMinutes($i)]);
    }

    $first = $this->get(route('warnings.index', ['type' => 'bank', 'sort' => 'newest']))->assertOk();

    expect($first->viewData('reports')->count())->toBe(20);
    $first->assertSee('type=bank&amp;sort=newest&amp;page=2', false);

    $second = $this->get(route('warnings.index', ['type' => 'bank', 'sort' => 'newest', 'page' => 2]))->assertOk();

    expect($second->viewData('reports')->count())->toBe(5);
});

test('phone keyword matches every vietnamese phone format', function (string $keyword) {
    warningReport(['slug' => 'local-format', 'target_id' => '0912345678', 'target_bank' => null]);
    warningReport(['slug' => 'intl-format', 'target_id' => '+84 912 345 678', 'target_bank' => null]);
    warningReport(['slug' => 'other-phone', 'target_id' => '0987654321', 'target_bank' => null]);

    $slugs = $this->get(route('warnings.index', ['q' => $keyword]))->assertOk()->viewData('reports')->pluck('slug');

    expect($slugs->sort()->values()->all())->toBe(['intl-format', 'local-format']);
})->with(['0912345678', '+84912345678', '84912345678', '0912.345.678']);

test('keyword matches bank accounts, facebook links and names', function (string $keyword, string $expectedSlug) {
    warningReport(['slug' => 'bank-hit', 'target_id' => '5723-1030-06', 'target_name' => 'Lê Minh Khang', 'target_bank' => 'MBBank']);
    warningReport(['slug' => 'fb-hit', 'type' => 'website', 'target_id' => 'https://facebook.com/le.hoang.anh2000', 'target_name' => 'Lê Hoàng Anh', 'target_bank' => null]);

    $slugs = $this->get(route('warnings.index', ['q' => $keyword]))->assertOk()->viewData('reports')->pluck('slug');

    expect($slugs->all())->toBe([$expectedSlug]);
})->with([
    'bank digits' => ['5723103006', 'bank-hit'],
    'facebook url' => ['https://www.facebook.com/le.hoang.anh2000', 'fb-hit'],
    'partial name' => ['  minh   KHANG ', 'bank-hit'],
]);

test('only masked target data is rendered', function () {
    warningReport(['target_id' => '1903456789', 'target_name' => 'Trần Thị Thuỷ']);
    warningReport(['type' => 'website', 'target_id' => 'facebook.com/le.hoang.anh2000', 'target_name' => 'Lê Hoàng Anh', 'target_bank' => null]);

    $this->get(route('warnings.index'))
        ->assertOk()
        ->assertDontSee('1903456789')
        ->assertDontSee('Trần Thị Thuỷ')
        ->assertDontSee('le.hoang.anh2000')
        ->assertDontSee('Lê Hoàng Anh')
        ->assertSee('190***789')
        ->assertSee('Trần Thị T.')
        ->assertSee('Lê Hoàng A.');
});

test('an empty result shows the empty state with calls to action', function () {
    warningReport(['target_bank' => 'Techcombank']);

    $this->get(route('warnings.index', ['type' => 'facebook']))
        ->assertOk()
        ->assertSee('Không có cảnh báo nào phù hợp')
        ->assertSee('Xoá bộ lọc')
        ->assertSee(route('reports'), false)
        ->assertSee('Tìm thấy');
});

test('filtered pages are noindex with a canonical to the unfiltered list', function () {
    warningReport();

    $this->get(route('warnings.index', ['type' => 'bank']))
        ->assertOk()
        ->assertSee('<link rel="canonical" href="'.route('warnings.index').'"', false)
        ->assertSee('<meta name="robots" content="noindex, follow">', false);
});

test('a page past the last one is not found', function () {
    warningReport();

    $this->get(route('warnings.index', ['page' => 2]))->assertNotFound();
    $this->get(route('warnings.index', ['type' => 'bank', 'page' => 99]))->assertNotFound();
});

test('an empty list is noindex', function () {
    $this->get(route('warnings.index'))
        ->assertOk()
        ->assertSee('<meta name="robots" content="noindex, follow">', false);
});

test('the unfiltered list stays indexable', function () {
    warningReport();

    $this->get(route('warnings.index'))
        ->assertOk()
        ->assertSee('<link rel="canonical" href="'.route('warnings.index').'"', false)
        ->assertDontSee('noindex', false);
});

test('the list runs a constant number of queries without lazy loading', function () {
    Model::preventLazyLoading();

    $countQueries = function (): int {
        $count = 0;
        DB::listen(function () use (&$count) {
            $count++;
        });
        $this->get(route('warnings.index'))->assertOk();

        return $count;
    };

    $few = warningReport();
    Comment::factory()->create(['report_id' => $few->id]);
    $this->get(route('warnings.index'))->assertOk(); // warm the settings cache
    $baseline = $countQueries();

    foreach (range(1, 12) as $i) {
        $report = warningReport(['target_bank' => "Bank {$i}"]);
        Comment::factory()->count(2)->create(['report_id' => $report->id]);
    }

    expect($countQueries())->toBe($baseline)
        ->and($baseline)->toBeLessThanOrEqual(3);
});
