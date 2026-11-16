<?php

use App\Http\Controllers\SitemapController;
use App\Models\Insurance;
use App\Models\Post;
use App\Models\Report;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(function () {
    Cache::forget(SitemapController::CACHE_KEY);
});

test('the sitemap is valid xml listing pages, posts, insurances and approved reports by slug only', function () {
    $approved = Report::factory()->create([
        'status' => 'approved',
        'target_id' => '0912345678',
        'target_name' => 'Nguyen Van Lua',
    ]);
    $pending = Report::factory()->create(['status' => 'pending']);
    $post = Post::factory()->create();
    $insurance = Insurance::factory()->create(['status' => 1]);
    $lockedInsurance = Insurance::factory()->create(['status' => 0]);

    $response = $this->get('/sitemap.xml')->assertOk();

    expect($response->headers->get('Content-Type'))->toStartWith('application/xml');

    $xml = simplexml_load_string($response->getContent());
    expect($xml)->not->toBeFalse();

    $locs = [];
    foreach ($xml->url as $url) {
        $locs[] = rtrim((string) $url->loc, '/');
    }

    expect($locs)->toContain(
        url('/'),
        url('/warnings'),
        url('/dieu-khoan'),
        url('/moderation-policy'),
        route('posts.frontend.show', $post->slug),
        route('insurances.frontend.show', $insurance->slug),
        route('scammer.show', $approved->slug),
    )->not->toContain(
        route('scammer.show', $pending->slug),
        route('insurances.frontend.show', $lockedInsurance->slug),
    );

    expect($response->getContent())
        ->not->toContain('0912345678')
        ->not->toContain('Nguyen Van Lua');
});

test('the sitemap is cached for an hour', function () {
    $this->get('/sitemap.xml')->assertOk();
    $report = Report::factory()->create(['status' => 'approved']);

    $this->get('/sitemap.xml')->assertOk()->assertDontSee(route('scammer.show', $report->slug), false);

    expect(Cache::has(SitemapController::CACHE_KEY))->toBeTrue();
});

test('robots.txt blocks private areas and points to the sitemap on the app url', function () {
    $response = $this->get('/robots.txt')->assertOk();

    expect($response->headers->get('Content-Type'))->toStartWith('text/plain')
        ->and($response->getContent())
        ->toContain("User-agent: *\n")
        ->toContain("Disallow: /admin\n")
        ->toContain("Disallow: /search\n")
        ->toContain("Disallow: /watchlist\n")
        ->toContain("Disallow: /api/\n")
        ->toContain('Sitemap: '.url('/sitemap.xml'));
});

test('the public sitemap generator endpoint is gone', function () {
    $this->get('/generate-sitemap')->assertNotFound();

    expect(file_exists(public_path('sitemap.xml')))->toBeFalse();
});
