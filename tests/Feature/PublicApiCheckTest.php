<?php

use App\Enums\ChannelType;
use App\Models\ApiKey;
use App\Models\Insurance;
use App\Models\InsuranceChannel;
use App\Models\Report;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;

uses(RefreshDatabase::class);

const TEST_API_KEY = 'mst_test_key_0123456789abcdefghijklmnopqrstuv';

beforeEach(function () {
    $this->registerMysqlCompatibleSqliteFunctions();
    $this->apiKey = ApiKey::factory()->withPlainKey(TEST_API_KEY)->create(['rate_limit_per_minute' => 30]);
    RateLimiter::clear('api-key:'.$this->apiKey->id);
});

test('requests without a key are rejected with a json 401', function () {
    $this->getJson('/api/v1/check?q=0912345678')
        ->assertUnauthorized()
        ->assertJsonPath('error.code', 'unauthenticated');
});

test('unknown and revoked keys are rejected', function () {
    $this->getJson('/api/v1/check?q=0912345678', ['Authorization' => 'Bearer mst_wrong'])
        ->assertUnauthorized();

    $this->apiKey->update(['is_active' => false]);

    $this->getJson('/api/v1/check?q=0912345678', ['Authorization' => 'Bearer '.TEST_API_KEY])
        ->assertUnauthorized();
});

test('a valid bearer key returns the masked lookup payload and records usage', function () {
    Report::factory()->create([
        'status' => 'approved',
        'type' => 'account',
        'target_id' => '0912345678',
        'target_name' => 'Nguyễn Văn Đạt',
        'reporter_name' => 'Hidden Reporter',
        'slug' => 'nguyen-van-dat-1',
        'created_at' => now()->subDays(2),
    ]);

    $response = $this->getJson('/api/v1/check?q=%2B84912345678', ['Authorization' => 'Bearer '.TEST_API_KEY]);

    $response->assertOk()
        ->assertHeader('X-RateLimit-Limit', 30)
        ->assertExactJsonStructure([
            'query' => ['type', 'value'],
            'risk',
            'total_reports',
            'reports' => [['slug', 'url', 'target_name_masked', 'type', 'reported_at', 'comments_count']],
            'insurance',
            'verified_intermediary',
        ])
        ->assertJson([
            'verified_intermediary' => false,
            'query' => ['type' => 'phone', 'value' => '0912345678'],
            'risk' => 'medium',
            'total_reports' => 1,
            'reports' => [[
                'slug' => 'nguyen-van-dat-1',
                'url' => route('scammer.show', 'nguyen-van-dat-1'),
                'target_name_masked' => 'Nguyễn Văn Đ.',
                'type' => 'account',
                'comments_count' => 0,
            ]],
        ])
        ->assertDontSee('Hidden Reporter');

    expect($this->apiKey->fresh()->last_used_at)->not->toBeNull();
});

test('the x-api-key header is accepted', function () {
    $this->getJson('/api/v1/check?q=0912345678', ['X-Api-Key' => TEST_API_KEY])
        ->assertOk()
        ->assertJson(['risk' => 'low', 'total_reports' => 0, 'reports' => [], 'insurance' => []]);
});

test('an official channel of a trusted partner is flagged as a verified intermediary', function () {
    $partner = Insurance::factory()->trusted()->create(['full_name' => 'GDV Hùng']);
    InsuranceChannel::factory()->for($partner)->ofType(ChannelType::Telegram, '@gdv_hung')->create();

    $this->getJson('/api/v1/check?q=%40gdv_hung', ['X-Api-Key' => TEST_API_KEY])
        ->assertOk()
        ->assertJsonPath('verified_intermediary', true)
        ->assertJsonPath('insurance.0.name', 'GDV Hùng')
        ->assertJsonPath('total_reports', 0);
});

test('a missing or too short query returns a json 422', function (string $url) {
    $this->getJson($url, ['X-Api-Key' => TEST_API_KEY])
        ->assertUnprocessable()
        ->assertJsonPath('error.code', 'validation_failed')
        ->assertJsonStructure(['error' => ['message', 'fields' => ['q']]]);
})->with(['/api/v1/check', '/api/v1/check?q=a']);

test('each key is limited to its own requests per minute', function () {
    $this->apiKey->update(['rate_limit_per_minute' => 2]);
    $otherKey = 'mst_other_key_0123456789abcdefghijklmnopqrstu';
    ApiKey::factory()->withPlainKey($otherKey)->create();

    $headers = ['X-Api-Key' => TEST_API_KEY];
    $this->getJson('/api/v1/check?q=0912345678', $headers)->assertOk();
    $this->getJson('/api/v1/check?q=0912345678', $headers)->assertOk()->assertHeader('X-RateLimit-Remaining', 0);

    $this->getJson('/api/v1/check?q=0912345678', $headers)
        ->assertTooManyRequests()
        ->assertJsonPath('error.code', 'rate_limited')
        ->assertHeader('Retry-After');

    $this->getJson('/api/v1/check?q=0912345678', ['X-Api-Key' => $otherKey])->assertOk();
});
