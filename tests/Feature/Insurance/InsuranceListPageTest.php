<?php

use App\Models\Insurance;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
});

test('the public list, its search and its stats leave out expired partners', function () {
    Insurance::factory()->trusted()->create(['full_name' => 'GDV Còn Hạn', 'amount' => 10000000]);
    Insurance::factory()->trusted()->create(['full_name' => 'GDV Không Thời Hạn', 'amount' => 5000000, 'expired_at' => null]);
    Insurance::factory()->trusted()->create(['full_name' => 'GDV Hết Hạn Hôm Qua', 'amount' => 70000000, 'expired_at' => now()->subDay()]);
    Insurance::factory()->trusted()->create(['full_name' => 'GDV Hết Hạn Hôm Nay', 'amount' => 70000000, 'expired_at' => now()->toDateString()]);
    Insurance::factory()->trusted()->create(['full_name' => 'GDV Hết Hạn Ngày Mai', 'amount' => 1000000, 'expired_at' => now()->addDay()->toDateString()]);

    $html = $this->get(route('insurances.frontend.index'))
        ->assertOk()
        ->assertSee('GDV Còn Hạn')
        ->assertSee('GDV Không Thời Hạn')
        ->assertSee('GDV Hết Hạn Ngày Mai')
        ->assertDontSee('GDV Hết Hạn Hôm Qua')
        ->assertDontSee('GDV Hết Hạn Hôm Nay')
        ->assertSee('16.000.000 đ')
        ->getContent();

    expect($html)->toMatch('/Thành viên đã xác thực.*?>\s*3\s*</s');

    $this->get(route('insurances.frontend.index', ['search' => 'Hết Hạn']))
        ->assertOk()
        ->assertSee('GDV Hết Hạn Ngày Mai')
        ->assertDontSee('GDV Hết Hạn Hôm Qua')
        ->assertDontSee('GDV Hết Hạn Hôm Nay');
});

test('the home partner section leaves out expired partners', function () {
    Insurance::factory()->trusted()->create(['full_name' => 'GDV Còn Hạn', 'amount' => 1000000]);
    Insurance::factory()->trusted()->expired()->create(['full_name' => 'GDV Đã Hết Hạn', 'amount' => 900000000]);

    $html = $this->get('/')->assertOk()->getContent();
    $section = (string) str($html)->after('aria-label="Trung gian đã ký quỹ"')->before('</section>');

    expect($section)->toContain('GDV Còn Hạn')->not->toContain('GDV Đã Hết Hạn');
});

test('an expired partner keeps its public profile page with the expired status', function () {
    $expired = Insurance::factory()->trusted()->expired()->create(['full_name' => 'GDV Đã Hết Hạn']);

    $this->get(route('insurances.frontend.show', $expired->slug))
        ->assertOk()
        ->assertSee('GDV Đã Hết Hạn')
        ->assertSee('Hết hạn');
});

test('the search suggestions only call current members trusted', function () {
    Insurance::factory()->trusted()->create(['full_name' => 'Gợi Ý Còn Hạn']);
    Insurance::factory()->trusted()->create(['full_name' => 'Gợi Ý Hết Hạn', 'expired_at' => now()->subDay()]);

    $labels = collect($this->getJson(route('search.autocomplete', ['q' => 'Gợi Ý']))->assertOk()->json())->pluck('label');

    expect($labels)->toContain('Uy tín: Gợi Ý Còn Hạn')
        ->not->toContain('Uy tín: Gợi Ý Hết Hạn');
});
