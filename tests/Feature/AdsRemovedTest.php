<?php

use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

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

test('the admin banner routes and menu are gone', function () {
    expect(collect(Route::getRoutes()->getRoutesByName())->keys()->filter(fn (string $name) => str_contains($name, 'banner')))->toBeEmpty();

    $admin = User::factory()->create(['role' => 'admin', 'status' => 1]);
    $this->actingAs($admin);

    $this->get('/admin/banners')->assertNotFound();
    $this->get('/admin/banners/create')->assertNotFound();
    $this->get('/admin/banners/1/edit')->assertNotFound();
    $this->post('/admin/banners')->assertNotFound();
    $this->post('/admin/banners/1/toggle')->assertNotFound();
    $this->post('/admin/banners/bulk-delete')->assertNotFound();

    expect($this->get(route('admin.dashboard'))->assertOk()->getContent())->not->toContain('/admin/banners');
});
