<?php

use App\Models\Insurance;
use App\Models\Report;
use App\Models\SearchLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    if (DB::getDriverName() === 'sqlite') {
        // The search page also renders StatsHelper::getTopWeeklyReports(), which uses MySQL-only GROUP_CONCAT ... SEPARATOR.
        $this->markTestSkipped('Needs MySQL: run with DB_CONNECTION=mysql DB_DATABASE=<scratch db>.');
    }

    $this->registerMysqlCompatibleSqliteFunctions();
    $this->withoutVite();
});

test('phone search returns approved matching reports newest first and counts the search once', function () {
    $older = Report::factory()->create([
        'status' => 'approved',
        'target_id' => '0912 345 678',
        'search_count' => 0,
        'created_at' => now()->subDays(5),
    ]);
    $newer = Report::factory()->create([
        'status' => 'approved',
        'target_id' => '0912345678',
        'search_count' => 0,
        'created_at' => now()->subDay(),
    ]);
    Report::factory()->create(['status' => 'pending', 'target_id' => '0912345678']);
    Report::factory()->create(['status' => 'approved', 'target_id' => '0987654321']);

    $response = $this->get('/search?q=%2B84912345678');

    $response->assertOk()
        ->assertViewHas('type', 'phone')
        ->assertViewHas('isFound', true)
        ->assertViewHas('results', fn ($results) => $results->pluck('id')->all() === [$newer->id, $older->id]);

    $this->get('/search?q=%2B84912345678');

    expect($older->fresh()->search_count)->toBe(1)
        ->and($newer->fresh()->search_count)->toBe(1)
        ->and(SearchLog::where('search_query', '+84912345678')->count())->toBe(1);
});

test('name search matches case and whitespace insensitively', function () {
    $report = Report::factory()->create([
        'status' => 'approved',
        'target_name' => 'Nguyễn   Văn Đạt',
        'target_id' => '123456789',
    ]);

    $this->get('/search?q='.urlencode('nguyễn văn đạt'))
        ->assertOk()
        ->assertViewHas('type', 'name')
        ->assertViewHas('results', fn ($results) => $results->pluck('id')->all() === [$report->id]);
});

test('search lists matching insurance partners', function () {
    $insurance = Insurance::factory()->create(['full_name' => 'Trần Thị Mai', 'status' => 1]);

    $this->get('/search?q='.urlencode('Trần Thị Mai'))
        ->assertOk()
        ->assertViewHas('isFound', true)
        ->assertViewHas('matchedInsurances', fn ($items) => $items->pluck('id')->all() === [$insurance->id]);
});

test('search with no match reports not found', function () {
    $this->get('/search?q=0900000000')
        ->assertOk()
        ->assertViewHas('isFound', false)
        ->assertViewHas('results', fn ($results) => $results->total() === 0);
});
