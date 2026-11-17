<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
});

dataset('public pages', [
    'home' => '/',
    'insurances' => '/bao-hiem-cs',
    'posts' => '/bai-viet',
    'newfeed' => '/newfeed',
    'report form' => '/to-cao-lua-dao',
]);

test('public pages render the shared header navigation', function (string $uri) {
    $response = $this->get($uri);

    $response->assertOk()
        ->assertSeeInOrder(['Tra cứu', 'Tố cáo', 'Bảo hiểm CS', 'Khu mua bán', 'Bài viết', 'API'])
        ->assertSee('data-theme-toggle', false)
        ->assertSee('id="mobile-menu"', false);
})->with('public pages');

test('guests see the google login link in the header', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee(route('auth.google'), false)
        ->assertSee('Đăng nhập');
});

test('signed-in google users see their account menu with logout', function () {
    $user = User::factory()->create(['role' => 'user', 'full_name' => 'Nguyen Van A']);

    $this->actingAs($user)
        ->get('/')
        ->assertOk()
        ->assertSee('Nguyen Van A')
        ->assertSee(route('auth.logout'), false)
        ->assertDontSee(route('auth.google'), false);
});

test('layout no longer loads the prebuilt tailwind stylesheet', function () {
    $this->get('/')
        ->assertOk()
        ->assertDontSee('/css/tailwind.css', false);
});

test('layout uses the scamproof logo image and favicon', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('/img/scamproof-logo-tight.png', false)
        ->assertSee('/favicon.svg', false)
        ->assertSee('aria-label="Scamproof"', false)
        ->assertDontSee('Cảnh Giác');
});
