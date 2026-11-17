<?php

use App\Enums\ChannelType;
use App\Models\Insurance;
use App\Models\InsuranceChannel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->partner = Insurance::factory()->trusted()->withChannels()->create();
    InsuranceChannel::factory()->for($this->partner)->ofType(ChannelType::Phone, '0912345678')->create();
});

test('the public and admin claim routes are gone', function () {
    expect(collect(Route::getRoutes()->getRoutesByName())->keys()->filter(fn (string $name) => str_contains($name, 'claim')))->toBeEmpty();

    $this->get('/insurance-claims/create')->assertNotFound();
    $this->get('/insurance-claims/create?partner='.$this->partner->slug)->assertNotFound();

    $admin = User::factory()->create(['role' => 'admin']);
    $this->actingAs($admin)->get('/admin/insurance-claims')->assertNotFound();
    $this->actingAs($admin)->get('/admin/insurance-claims/1')->assertNotFound();
});

test('no public page mentions compensation or guarantees', function (Closure $url) {
    $html = mb_strtolower($this->get($url($this->partner))->assertOk()->getContent());

    expect($html)->not->toContain('bồi thường')
        ->not->toContain('bảo lãnh')
        ->not->toContain('đền bù')
        ->not->toContain('insurance-claims');
})->with([
    'partner profile' => [fn (Insurance $partner) => route('insurances.frontend.show', $partner->slug)],
    'partner list' => [fn () => route('insurances.frontend.index')],
    'verify page' => [fn () => route('intermediary.verify')],
    'verify result' => [fn () => route('intermediary.verify', ['q' => '0912345678'])],
    'home with footer' => [fn () => route('home')],
]);

test('the admin partner page, dashboard and sidebar have no claim ui', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    foreach ([route('admin.insurances.show', $this->partner), route('admin.dashboard')] as $url) {
        $html = mb_strtolower($this->actingAs($admin)->get($url)->assertOk()->getContent());

        expect($html)->not->toContain('bồi thường')
            ->not->toContain('bảo lãnh')
            ->not->toContain('insurance-claims')
            ->not->toContain('open-claims-badge');
    }
});
