<?php

use App\Models\ApiKey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->registerMysqlCompatibleSqliteFunctions();
    $this->admin = User::factory()->create(['role' => 'admin', 'status' => 1]);
});

test('issuing a key stores only its sha256 hash and shows the plaintext once', function () {
    $response = $this->actingAs($this->admin)->post(route('admin.api-keys.store'), [
        'name' => 'Shop ABC',
        'rate_limit_per_minute' => 60,
    ]);

    $response->assertRedirect(route('admin.api-keys.index'));
    $plainKey = session('plain_api_key');
    $apiKey = ApiKey::sole();

    expect($plainKey)->toStartWith(ApiKey::PLAINTEXT_PREFIX)
        ->and($apiKey->key_hash)->toBe(hash('sha256', $plainKey))
        ->and($apiKey->key_prefix)->toBe(substr($plainKey, 0, 12))
        ->and($apiKey->rate_limit_per_minute)->toBe(60)
        ->and($apiKey->user_id)->toBe($this->admin->id)
        ->and(json_encode(ApiKey::query()->get()->toArray()))->not->toContain($plainKey)
        ->and($apiKey->toArray())->not->toHaveKey('key_hash');

    $this->followRedirects($response)->assertOk()->assertSee($plainKey);
    $this->get(route('admin.api-keys.index'))->assertOk()->assertDontSee($plainKey);
});

test('the issued plaintext key authenticates against the api', function () {
    [, $plainKey] = ApiKey::issue('Partner');

    $this->getJson('/api/v1/check?q=0912345678', ['Authorization' => 'Bearer '.$plainKey])->assertOk();
});

test('revoking a key deactivates it', function () {
    $apiKey = ApiKey::factory()->create();

    $this->actingAs($this->admin)
        ->patch(route('admin.api-keys.revoke', $apiKey))
        ->assertRedirect();

    expect($apiKey->fresh()->is_active)->toBeFalse();
});

test('store validates the input', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.api-keys.store'), ['name' => '', 'rate_limit_per_minute' => 0])
        ->assertSessionHasErrors(['name', 'rate_limit_per_minute']);

    expect(ApiKey::count())->toBe(0);
});

test('only admins can manage api keys', function () {
    $moderator = User::factory()->create(['role' => 'moderator', 'status' => 1]);
    $apiKey = ApiKey::factory()->create();

    $this->actingAs($moderator)->get(route('admin.api-keys.index'))->assertForbidden();
    $this->actingAs($moderator)->post(route('admin.api-keys.store'), ['name' => 'x', 'rate_limit_per_minute' => 5])->assertForbidden();
    $this->actingAs($moderator)->patch(route('admin.api-keys.revoke', $apiKey))->assertForbidden();

    expect($apiKey->fresh()->is_active)->toBeTrue();
});

test('guests are redirected to the admin login', function () {
    $this->get(route('admin.api-keys.index'))->assertRedirect();
});

test('admins can open the create form', function () {
    $this->actingAs($this->admin)->get(route('admin.api-keys.create'))->assertOk()->assertSee('Tạo API key mới');
});
