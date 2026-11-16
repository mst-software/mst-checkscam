<?php

use App\Jobs\NotifyWatchersOfApprovedReport;
use App\Mail\WatchedTargetReportedMail;
use App\Models\Report;
use App\Models\User;
use App\Models\Watch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin', 'status' => 1]);
});

test('approving a report queues the watcher notification job', function () {
    Queue::fake();
    $report = Report::factory()->create(['status' => 'pending']);

    $this->actingAs($this->admin)
        ->post(route('admin.reports.approve', $report->id))
        ->assertRedirect();

    Queue::assertPushed(NotifyWatchersOfApprovedReport::class, fn ($job) => $job->report->is($report));
});

test('rejecting a report does not queue the watcher notification job', function () {
    Queue::fake();
    $report = Report::factory()->create(['status' => 'pending']);

    $this->actingAs($this->admin)
        ->post(route('admin.reports.reject', $report->id), ['rejection_reason' => 'Thiếu bằng chứng'])
        ->assertRedirect();

    Queue::assertNotPushed(NotifyWatchersOfApprovedReport::class);
});

test('approving through the edit form queues the job only on the transition to approved', function (string $from, bool $expected) {
    Queue::fake();
    $report = Report::factory()->create(['status' => $from, 'type' => 'account']);

    $this->actingAs($this->admin)
        ->put(route('admin.reports.update', $report->id), [
            'type' => 'account',
            'target_id' => $report->target_id,
            'description' => str_repeat('Mô tả hành vi lừa đảo. ', 4),
            'status' => 'approved',
        ])
        ->assertSessionHasNoErrors();

    $expected
        ? Queue::assertPushed(NotifyWatchersOfApprovedReport::class)
        : Queue::assertNotPushed(NotifyWatchersOfApprovedReport::class);
})->with([
    'pending to approved' => ['pending', true],
    'already approved' => ['approved', false],
]);

test('the job mails every watcher of the same normalized phone', function () {
    Mail::fake();
    $report = Report::factory()->create(['status' => 'approved', 'target_id' => '84912345678']);
    $watcher = Watch::factory()->create(['type' => 'phone', 'value' => '0912345678']);
    $otherWatcher = Watch::factory()->create(['type' => 'phone', 'value' => '0912345678']);
    $unrelated = Watch::factory()->create(['type' => 'phone', 'value' => '0999999999']);

    (new NotifyWatchersOfApprovedReport($report))->handle();

    Mail::assertQueued(WatchedTargetReportedMail::class, 2);
    Mail::assertQueued(WatchedTargetReportedMail::class, fn ($mail) => $mail->hasTo($watcher->user->email));
    Mail::assertQueued(WatchedTargetReportedMail::class, fn ($mail) => $mail->hasTo($otherWatcher->user->email));
    Mail::assertNotQueued(WatchedTargetReportedMail::class, fn ($mail) => $mail->hasTo($unrelated->user->email));
    expect($watcher->fresh()->last_notified_at)->not->toBeNull()
        ->and($unrelated->fresh()->last_notified_at)->toBeNull();
});

test('the job sends nothing when the report is no longer approved', function () {
    Mail::fake();
    $report = Report::factory()->create(['status' => 'rejected', 'target_id' => '0912345678']);
    Watch::factory()->create(['type' => 'phone', 'value' => '0912345678']);

    (new NotifyWatchersOfApprovedReport($report))->handle();

    Mail::assertNothingQueued();
});

test('the mail links to the report and never reveals the reporter or the full target', function () {
    $report = Report::factory()->create([
        'status' => 'approved',
        'target_id' => '+84 912 345 678',
        'target_name' => 'Nguyễn Văn Đạt',
        'reporter_name' => 'Trần Người Báo',
        'reporter_contact' => '0988777666',
    ]);
    $watch = Watch::factory()->create(['type' => 'phone', 'value' => '0912345678']);

    $mail = new WatchedTargetReportedMail($report, $watch);

    expect($mail->envelope()->subject)->toContain('091***678');
    $mail->assertSeeInHtml(route('scammer.show', $report->slug))
        ->assertSeeInHtml('091***678')
        ->assertSeeInHtml('Nguyễn Văn Đ.')
        ->assertDontSeeInHtml('0912345678')
        ->assertDontSeeInHtml('912 345 678')
        ->assertDontSeeInHtml('Trần Người Báo')
        ->assertDontSeeInHtml('0988777666');
});
