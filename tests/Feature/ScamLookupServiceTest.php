<?php

use App\Models\Comment;
use App\Models\Insurance;
use App\Models\Report;
use App\Services\ScamLookupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->registerMysqlCompatibleSqliteFunctions();
    $this->service = app(ScamLookupService::class);
});

test('phone variants with +84, 84 and 0 prefixes match the same report', function (string $input) {
    $report = Report::factory()->create(['status' => 'approved', 'target_id' => '0912.345.678']);

    $result = $this->service->lookup($input);

    expect($result->type)->toBe('phone')
        ->and($result->formattedQuery)->toBe('0912345678')
        ->and($result->totalReports)->toBe(1)
        ->and($result->reports->pluck('id')->all())->toBe([$report->id]);
})->with(['+84912345678', '84912345678', '0912345678', '0912 345 678']);

test('only approved reports are returned', function () {
    Report::factory()->create(['status' => 'pending', 'target_id' => '0912345678']);
    Report::factory()->create(['status' => 'rejected', 'target_id' => '0912345678']);

    expect($this->service->lookup('0912345678')->totalReports)->toBe(0);
});

test('bank account numbers match regardless of separators', function () {
    $report = Report::factory()->create(['status' => 'approved', 'target_id' => '1903-4567-8901-23']);

    $result = $this->service->lookup('19034567890123');

    expect($result->type)->toBe('bank')
        ->and($result->reports->pluck('id')->all())->toBe([$report->id]);
});

test('facebook profile urls match by username', function () {
    $report = Report::factory()->create([
        'status' => 'approved',
        'type' => 'website',
        'target_id' => 'facebook.com/le.hoang.anh2000',
    ]);

    $result = $this->service->lookup('https://www.facebook.com/le.hoang.anh2000?ref=abc');

    expect($result->type)->toBe('facebook')
        ->and($result->formattedQuery)->toBe('le.hoang.anh2000')
        ->and($result->reports->pluck('id')->all())->toBe([$report->id]);
});

test('uuid queries match the report slug exactly', function () {
    $uuid = (string) Str::uuid();
    $report = Report::factory()->create(['status' => 'approved', 'slug' => $uuid]);
    Report::factory()->create(['status' => 'approved']);

    $result = $this->service->lookup($uuid);

    expect($result->type)->toBe('uuid')
        ->and($result->reports->pluck('id')->all())->toBe([$report->id]);
});

test('names match case and whitespace insensitively', function () {
    $report = Report::factory()->create(['status' => 'approved', 'target_name' => 'Nguyễn  Văn   Đạt']);
    Report::factory()->create(['status' => 'approved', 'target_name' => 'Trần Thị Thuỷ']);

    $result = $this->service->lookup('  NGUYỄN văn đạt ');

    expect($result->type)->toBe('name')
        ->and($result->reports->pluck('id')->all())->toBe([$report->id]);
});

test('lookup counts all matches but returns a limited newest-first list with comment counts', function () {
    $reports = collect(range(1, 4))->map(fn (int $daysAgo) => Report::factory()->create([
        'status' => 'approved',
        'target_id' => '0938111222',
        'created_at' => now()->subDays($daysAgo),
    ]));
    Comment::factory()->count(2)->create(['report_id' => $reports->first()->id]);

    $result = $this->service->lookup('0938111222', reportLimit: 2);

    expect($result->totalReports)->toBe(4)
        ->and($result->reports->pluck('id')->all())->toBe([$reports[0]->id, $reports[1]->id])
        ->and($result->reports->first()->comments_count)->toBe(2)
        ->and($result->risk)->toBe(ScamLookupService::RISK_HIGH);
});

test('lookup returns matching active insurance partners only', function () {
    $active = Insurance::factory()->create(['full_name' => 'Lê Hoàng Anh', 'status' => 1]);
    Insurance::factory()->create(['full_name' => 'Lê Hoàng Anh', 'status' => 0]);

    $result = $this->service->lookup('lê hoàng anh');

    expect($result->insurances->pluck('id')->all())->toBe([$active->id]);
});

test('public array masks names and never exposes reporter or raw target data', function () {
    Report::factory()->create([
        'status' => 'approved',
        'target_id' => '0912345678',
        'target_name' => 'Phạm Tuấn Ngọc',
        'reporter_name' => 'Secret Reporter',
        'reporter_contact' => 'reporter@example.com',
        'slug' => 'abc-slug',
    ]);

    $payload = $this->service->lookup('0912345678')->toPublicArray();
    $json = json_encode($payload, JSON_UNESCAPED_UNICODE);

    expect($payload['reports'][0])->toMatchArray([
        'slug' => 'abc-slug',
        'url' => route('scammer.show', 'abc-slug'),
        'target_name_masked' => 'Phạm Tuấn N.',
        'comments_count' => 0,
    ])
        ->and($json)->not->toContain('Secret Reporter')
        ->not->toContain('reporter@example.com')
        ->not->toContain('Phạm Tuấn Ngọc');
});
