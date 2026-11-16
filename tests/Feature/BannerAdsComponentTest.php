<?php

use App\Models\Banner;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function homeTopBanner(array $attributes = []): Banner
{
    return Banner::factory()->create(array_merge([
        'title' => 'Flash Sale Banner',
        'image_path' => 'https://example.test/banner.png',
        'redirect_url' => 'https://partner.example.test/promo',
        'position' => 'home_top',
        'type' => 'horizontal',
        'sort_order' => 0,
    ], $attributes));
}

test('banner ads render a lazy, sponsored slot without label or border that hides itself on image errors', function () {
    homeTopBanner();

    $this->blade('<x-banner-ads position="home_top" />')
        ->assertSee('data-ad-container', false)
        ->assertSee('data-ad-slot', false)
        ->assertDontSee('Quảng cáo')
        ->assertSee('href="https://partner.example.test/promo"', false)
        ->assertSee('target="_blank" rel="sponsored noopener"', false)
        ->assertSee('src="https://example.test/banner.png"', false)
        ->assertSee('loading="lazy"', false)
        ->assertSee('onerror="', false)
        ->assertSee('aspect-[8/1]', false)
        ->assertDontSee('bg-surface-muted', false)
        ->assertDontSee('border-line', false)
        ->assertDontSee('dark:bg-white', false);
});

test('square banners keep a square box', function () {
    homeTopBanner(['position' => 'home_sidebar', 'type' => 'square']);

    $this->blade('<x-banner-ads position="home_sidebar" />')
        ->assertSee('aspect-square', false)
        ->assertSee('object-contain', false);
});

test('banners without a usable link render the image without an anchor', function (?string $redirectUrl) {
    homeTopBanner(['redirect_url' => $redirectUrl]);

    $this->blade('<x-banner-ads position="home_top" />')
        ->assertSee('src="https://example.test/banner.png"', false)
        ->assertDontSee('<a ', false)
        ->assertDontSee('javascript:', false);
})->with([
    'placeholder hash' => '#',
    'no link' => null,
    'script url' => 'javascript:alert(1)',
]);

test('uploaded banner images are served from the uploads folder', function () {
    homeTopBanner(['image_path' => 'banners/ad.webp']);

    $this->blade('<x-banner-ads position="home_top" />')
        ->assertSee('src="'.asset('uploads/banners/ad.webp').'"', false);
});

test('nothing is rendered when no banner is active for the position', function () {
    homeTopBanner(['status' => false]);

    expect(trim((string) $this->blade('<x-banner-ads position="home_top" />')))->toBe('');
});
