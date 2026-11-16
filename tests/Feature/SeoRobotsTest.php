<?php

use App\Models\Report;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
});

/**
 * @return list<string>
 */
function robotsMetaContents(string $html): array
{
    preg_match_all('/<meta\s+name="robots"\s+content="([^"]*)"/i', $html, $matches);

    return $matches[1];
}

test('filtered warnings page outputs a single noindex robots meta', function () {
    Report::factory()->create(['status' => 'approved', 'type' => 'account']);

    $html = $this->get(route('warnings.index', ['type' => 'bank']))->assertOk()->getContent();

    expect(robotsMetaContents($html))->toBe(['noindex, follow']);
});

test('search page without a query outputs a single noindex robots meta', function (string $url) {
    $html = $this->get($url)->assertOk()->getContent();

    expect(robotsMetaContents($html))->toBe(['noindex, follow']);
})->with([
    'no parameter' => '/search',
    'empty q' => '/search?q=',
]);

test('pages without their own robots use the site setting once', function () {
    Setting::setValue('site_index', 'index, nofollow');
    Cache::flush();
    Report::factory()->create(['status' => 'approved', 'type' => 'account']);

    $html = $this->get(route('warnings.index'))->assertOk()->getContent();

    expect(robotsMetaContents($html))->toBe(['index, nofollow']);
});
