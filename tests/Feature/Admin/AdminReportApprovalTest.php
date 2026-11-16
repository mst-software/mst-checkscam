<?php

use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    Queue::fake();
    $this->moderator = User::factory()->create(['role' => 'moderator', 'status' => 1]);
});

function reportUpdatePayload(Report $report, string $status): array
{
    return [
        'type' => $report->type,
        'target_id' => $report->target_id,
        'target_name' => $report->target_name,
        'description' => str_repeat('Mô tả chi tiết vụ việc lừa đảo. ', 3),
        'status' => $status,
    ];
}

test('approving a pending report records the approval time', function () {
    Carbon::setTestNow('2026-11-16 10:00:00');
    $report = Report::factory()->create(['status' => 'pending']);

    expect($report->approved_at)->toBeNull();

    $this->actingAs($this->moderator)
        ->post(route('admin.reports.approve', $report->id))
        ->assertRedirect();

    expect($report->fresh()->approved_at?->toDateTimeString())->toBe('2026-11-16 10:00:00');
});

test('rejecting a report leaves the approval time empty', function () {
    $report = Report::factory()->create(['status' => 'pending']);

    $this->actingAs($this->moderator)
        ->post(route('admin.reports.reject', $report->id), ['rejection_reason' => 'Thiếu bằng chứng'])
        ->assertRedirect();

    expect($report->fresh()->approved_at)->toBeNull();
});

test('updating a report to approved sets the approval time once', function () {
    Carbon::setTestNow('2026-11-16 10:00:00');
    $report = Report::factory()->create(['status' => 'pending']);

    $this->actingAs($this->moderator)
        ->put(route('admin.reports.update', $report->id), reportUpdatePayload($report, 'approved'))
        ->assertRedirect();

    expect($report->fresh()->approved_at?->toDateTimeString())->toBe('2026-11-16 10:00:00');

    Carbon::setTestNow('2026-11-20 08:00:00');

    $this->actingAs($this->moderator)
        ->put(route('admin.reports.update', $report->id), reportUpdatePayload($report, 'approved'))
        ->assertRedirect();

    expect($report->fresh()->approved_at?->toDateTimeString())->toBe('2026-11-16 10:00:00');
});

test('the migration backfills approved reports from their last update only', function () {
    $approved = Report::factory()->create(['status' => 'approved']);
    $pending = Report::factory()->create(['status' => 'pending']);
    DB::table('reports')->where('id', $approved->id)->update(['approved_at' => null, 'updated_at' => '2026-10-01 09:30:00']);

    $migration = require database_path('migrations/2026_11_16_180341_add_approved_at_to_reports_table.php');
    $migration->backfillApprovedAt();

    expect($approved->fresh()->approved_at?->toDateTimeString())->toBe('2026-10-01 09:30:00')
        ->and($pending->fresh()->approved_at)->toBeNull();
});
