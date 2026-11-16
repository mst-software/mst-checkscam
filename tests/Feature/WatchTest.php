<?php

use App\Models\User;
use App\Models\Watch;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create(['role' => 'user', 'status' => 1]);
});

test('guests are sent to google login', function (string $method, string $uri) {
    $this->call($method, $uri, ['value' => '0912345678'])
        ->assertRedirect(route('auth.google'));

    expect(Watch::count())->toBe(0);
})->with([
    'list' => ['GET', '/watchlist'],
    'add' => ['POST', '/watchlist'],
]);

test('the list page shows only the current user watches', function () {
    Watch::factory()->for($this->user)->create(['value' => '0911111111']);
    Watch::factory()->create(['value' => '0922222222']);

    $this->withoutVite()
        ->actingAs($this->user)
        ->get(route('watch.index'))
        ->assertOk()
        ->assertSee('0911111111')
        ->assertDontSee('0922222222');
});

test('phone formats are normalized to one watch', function () {
    foreach (['0912345678', '+84912345678', '84912345678', '0912 345 678'] as $input) {
        $this->actingAs($this->user)
            ->post(route('watch.store'), ['value' => $input])
            ->assertSessionHasNoErrors();
    }

    expect($this->user->watches()->get(['type', 'value'])->toArray())
        ->toBe([['type' => 'phone', 'value' => '0912345678']]);
});

test('bank accounts and facebook links are normalized', function (string $input, string $type, string $value) {
    $this->actingAs($this->user)
        ->post(route('watch.store'), ['value' => $input])
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('watches', [
        'user_id' => $this->user->id,
        'type' => $type,
        'value' => $value,
    ]);
})->with([
    'bank' => ['1903 4567 8901', 'bank', '190345678901'],
    'facebook url' => ['https://www.facebook.com/Le.Hoang.Anh2000', 'facebook', 'le.hoang.anh2000'],
]);

test('values that are not a phone, bank account or facebook link are rejected', function (string $input) {
    $this->actingAs($this->user)
        ->post(route('watch.store'), ['value' => $input])
        ->assertSessionHasErrors('value');

    expect(Watch::count())->toBe(0);
})->with([
    'empty' => [''],
    'name' => ['Nguyễn Văn A'],
    'too long' => [str_repeat('1', 256)],
]);

test('a user cannot watch more than the limit', function () {
    Watch::factory()->count(Watch::MAX_PER_USER)->for($this->user)->create();

    $this->actingAs($this->user)
        ->post(route('watch.store'), ['value' => '0987654321'])
        ->assertSessionHasErrors('value');

    expect($this->user->watches()->count())->toBe(Watch::MAX_PER_USER);
});

test('watching an existing value at the limit is not an error', function () {
    Watch::factory()->count(Watch::MAX_PER_USER - 1)->for($this->user)->create();
    Watch::factory()->for($this->user)->create(['value' => '0987654321']);

    $this->actingAs($this->user)
        ->post(route('watch.store'), ['value' => '+84987654321'])
        ->assertSessionHasNoErrors();

    expect($this->user->watches()->count())->toBe(Watch::MAX_PER_USER);
});

test('the same value can be watched by different users', function () {
    Watch::factory()->create(['value' => '0912345678']);

    $this->actingAs($this->user)
        ->post(route('watch.store'), ['value' => '0912345678'])
        ->assertSessionHasNoErrors();

    expect(Watch::where('value', '0912345678')->count())->toBe(2);
});

test('a user can remove their own watch', function () {
    $watch = Watch::factory()->for($this->user)->create();

    $this->actingAs($this->user)
        ->delete(route('watch.destroy', $watch))
        ->assertRedirect();

    $this->assertModelMissing($watch);
});

test('a user cannot remove another user watch', function () {
    $watch = Watch::factory()->create();

    $this->actingAs($this->user)
        ->delete(route('watch.destroy', $watch))
        ->assertForbidden();

    $this->assertModelExists($watch);
});
