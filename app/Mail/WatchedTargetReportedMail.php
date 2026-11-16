<?php

namespace App\Mail;

use App\Helpers\StringHelper;
use App\Models\Report;
use App\Models\Watch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WatchedTargetReportedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Report $report, public Watch $watch) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Cảnh báo: '.$this->maskedTarget().' vừa có báo cáo lừa đảo mới',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.watched-target-reported',
            with: [
                'maskedTarget' => $this->maskedTarget(),
                'maskedName' => StringHelper::mask_name($this->report->target_name),
                'typeLabel' => $this->watch->typeLabel(),
                'reportUrl' => route('scammer.show', $this->report->slug),
                'manageUrl' => route('watch.index'),
            ],
        );
    }

    private function maskedTarget(): string
    {
        // Phone and bank values are masked from the normalized form so "+84 912..." reads as "091***".
        if ($this->watch->type === 'facebook') {
            return StringHelper::mask_id($this->report->target_id, $this->report->type);
        }

        return StringHelper::mask_id($this->watch->value);
    }
}
