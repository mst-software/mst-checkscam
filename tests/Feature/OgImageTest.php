<?php

use App\Helpers\OgImageHelper;
use App\Models\Report;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

test('an approved report returns a 1200x630 png share card', function () {
    $report = Report::factory()->create(['status' => 'approved']);

    $response = $this->get(route('og.report', $report->slug));

    $response->assertOk()->assertHeader('Content-Type', 'image/png');
    [$width, $height] = getimagesizefromstring($response->getContent());
    expect([$width, $height])->toBe([OgImageHelper::WIDTH, OgImageHelper::HEIGHT]);
});

test('missing or unapproved reports return 404', function (?string $status) {
    $slug = 'khong-ton-tai';
    if ($status) {
        $slug = Report::factory()->create(['status' => $status])->slug;
    }

    $this->get('/og/'.$slug.'.png')->assertNotFound();
})->with([
    'missing' => [null],
    'pending' => ['pending'],
    'rejected' => ['rejected'],
]);

test('the og route is not shadowed by the report catch-all route', function () {
    $matched = app('router')->getRoutes()->match(Request::create('/og/abc-123.png'));

    expect($matched->getName())->toBe('og.report')
        ->and(route('og.report', 'abc-123'))->toEndWith('/og/abc-123.png');
});

test('the card cache is keyed on report updates', function () {
    $report = Report::factory()->create(['status' => 'approved']);
    $this->get(route('og.report', $report->slug))->assertOk();
    $firstKey = OgImageHelper::cacheKey($report, 1, 0);
    expect(Cache::has($firstKey))->toBeTrue();

    $this->travel(1)->minutes();
    $report->update(['target_name' => 'Tên mới']);
    $secondKey = OgImageHelper::cacheKey($report->fresh(), 1, 0);

    expect($secondKey)->not->toBe($firstKey)
        ->and(Cache::has($secondKey))->toBeFalse();

    $this->get(route('og.report', $report->slug))->assertOk();
    expect(Cache::has($secondKey))->toBeTrue();
});

test('the report page uses the share card as og image and shows share and watch actions', function () {
    $report = Report::factory()->create(['status' => 'approved', 'target_id' => '0912345678']);

    $this->withoutVite()
        ->get(route('scammer.show', $report->slug))
        ->assertOk()
        ->assertSee('<meta property="og:image" content="'.route('og.report', $report->slug).'"', false)
        ->assertSee('https://www.facebook.com/sharer/sharer.php?u='.urlencode(route('scammer.show', $report->slug)), false)
        ->assertSee('Đăng nhập để theo dõi');
});
