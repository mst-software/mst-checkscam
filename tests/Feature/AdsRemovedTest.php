<?php

use App\Models\Post;
use App\Models\Report;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
});

test('public pages render without ad markup', function (Closure $url) {
    $report = Report::factory()->create(['status' => 'approved', 'type' => 'account', 'target_id' => '0912345678']);
    $post = Post::factory()->create();

    $html = $this->get($url($report, $post))->assertOk()->getContent();

    expect($html)->not->toContain('data-ad-container')
        ->not->toContain('data-ad-slot')
        ->not->toContain('banner-ads')
        ->not->toContain('rel="sponsored');
})->with([
    'home' => [fn () => route('home')],
    'search results' => [fn () => route('search.index', ['q' => '0912345678'])],
    'post detail' => [fn (Report $report, Post $post) => route('posts.frontend.show', $post->slug)],
    'report page' => [fn (Report $report) => route('scammer.show', $report->slug)],
]);
