<?php

namespace App\Jobs;

use App\Mail\WatchedTargetReportedMail;
use App\Models\Report;
use App\Models\Watch;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class NotifyWatchersOfApprovedReport implements ShouldQueue
{
    use Queueable;

    public function __construct(public Report $report) {}

    public function handle(): void
    {
        if (! $this->report->isApproved()) {
            return;
        }

        $target = Watch::normalizeTarget($this->report->target_id);

        if ($target === null) {
            return;
        }

        [$type, $value] = $target;

        Watch::query()
            ->matchingTarget($type, $value)
            ->with('user')
            ->chunkById(200, function (Collection $watches): void {
                foreach ($watches as $watch) {
                    if (! $watch->user?->email) {
                        continue;
                    }

                    // The mailable is queued, so each recipient is retried independently.
                    Mail::to($watch->user->email)->send(new WatchedTargetReportedMail($this->report, $watch));
                }

                Watch::query()->whereKey($watches->modelKeys())->update(['last_notified_at' => now()]);
            });
    }
}
