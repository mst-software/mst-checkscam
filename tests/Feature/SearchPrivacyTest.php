<?php

use App\Models\Insurance;
use App\Models\Report;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;

uses(RefreshDatabase::class);

beforeEach(function () {
    RateLimiter::clear('search-autocomplete:127.0.0.1');
});

function privacyReport(array $attributes = []): Report
{
    return Report::factory()->create(array_merge([
        'status' => 'approved',
        'type' => 'account',
        'target_id' => '0987.654.321',
        'target_name' => 'Lê Thị Hoa',
    ], $attributes));
}

test('autocomplete returns masked identifiers with a stable shape', function () {
    $report = privacyReport();

    $this->getJson(route('search.autocomplete', ['q' => '098765']))
        ->assertOk()
        ->assertExactJson([[
            'label' => '098***321 - Lê Thị H.',
            'display' => '098***321',
            'query' => '098765',
            'url' => route('scammer.show', $report->slug),
            'type' => 'account',
            'target_name' => 'Lê Thị H.',
        ]]);
});

test('autocomplete never returns full identifiers or full names', function (string $targetId, string $typed) {
    privacyReport(['target_id' => $targetId, 'target_name' => 'Phạm Quang Dũng']);

    $response = $this->getJson(route('search.autocomplete', ['q' => $typed]))->assertOk();
    $json = $response->getContent();

    expect($response->json())->toHaveCount(1)
        ->and($json)->not->toContain($targetId)
        ->and($json)->not->toContain(preg_replace('/\D/', '', $targetId))
        ->and($json)->not->toContain('Dũng');
})->with([
    'dotted phone, digits typed' => ['0912.888.777', '0912888'],
    'spaced bank account' => ['1903 4567 8901 23', '19034567'],
    'international phone typed' => ['0977111222', '+84977'],
]);

test('autocomplete treats like wildcards literally', function (string $typed) {
    privacyReport();
    privacyReport(['target_id' => '0911222333', 'target_name' => 'Võ Văn Nam']);

    $this->getJson(route('search.autocomplete', ['q' => $typed]))
        ->assertOk()
        ->assertExactJson([]);
})->with(['%%%', '___', 'a%b']);

test('autocomplete ignores queries shorter than three characters', function () {
    privacyReport();

    $this->getJson(route('search.autocomplete', ['q' => '09']))
        ->assertOk()
        ->assertExactJson([]);
});

test('autocomplete skips pending reports', function () {
    privacyReport(['status' => 'pending']);

    $this->getJson(route('search.autocomplete', ['q' => '0987']))
        ->assertOk()
        ->assertExactJson([]);
});

test('autocomplete suggests public insurance partners by name', function () {
    Insurance::factory()->create(['full_name' => 'Trần Bảo Hiểm', 'status' => 1, 'expired_at' => now()->addMonth()]);

    $this->getJson(route('search.autocomplete', ['q' => 'Bảo Hiểm']))
        ->assertOk()
        ->assertJsonFragment([
            'display' => 'Trần Bảo Hiểm',
            'query' => 'Trần Bảo Hiểm',
            'url' => null,
            'type' => 'insurance',
        ]);
});

test('autocomplete is throttled to 30 requests per minute per ip', function () {
    privacyReport();

    for ($i = 0; $i < 30; $i++) {
        $this->getJson(route('search.autocomplete', ['q' => '0987'.$i]))->assertOk();
    }

    $this->getJson(route('search.autocomplete', ['q' => '0987']))
        ->assertTooManyRequests()
        ->assertHeader('Retry-After')
        ->assertExactJson([]);
});

function searchPageHead(string $html): string
{
    preg_match('/<head>(.*?)<\/head>/s', $html, $matches);

    return $matches[1] ?? '';
}

test('search result pages are noindex and never echo the raw query into the head', function (string $raw, string $digits) {
    $this->withoutVite();

    $html = $this->get(route('search.index', ['q' => $raw]))->assertOk()->getContent();
    $head = searchPageHead($html);
    preg_match_all('/<meta\s+name="robots"\s+content="([^"]*)"/i', $head, $robots);

    expect($head)->not->toBe('')
        ->not->toContain($raw)
        ->not->toContain(e($raw))
        ->not->toContain($digits)
        ->and($robots[1])->toBe(['noindex, follow']);

    // The visible search box keeps what the visitor typed.
    expect($html)->toContain('value="'.e($raw).'"');
})->with([
    'local phone' => ['0912345678', '2345'],
    'international phone' => ['+84 912-345-678', '2345'],
    'dotted bank account' => ['1903.6655.7788.99', '6655'],
    'name followed by a phone' => ['Nguyen Van Lua 0987654321', '7654'],
    'phone followed by a name' => ['0987654321 Lua', '7654'],
    'facebook profile url' => ['https://www.facebook.com/profile.php?id=100012345678', '0012345'],
]);

test('search result titles show the masked phone number', function () {
    $this->withoutVite();

    $html = $this->get(route('search.index', ['q' => '0912345678']))->assertOk()->getContent();

    expect(searchPageHead($html))->toContain('<title>Kết quả tra cứu 091***678 - Scamproof</title>');
});
