<?php

namespace App\Mail;

use App\Helpers\StringHelper;
use App\Models\Report;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Tells the reporter whether their report was approved or rejected.
 */
class ReportModeratedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Report $report) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->report->isApproved()
                ? 'Tố cáo của bạn đã được duyệt'
                : 'Tố cáo của bạn chưa được duyệt',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.report-moderated',
            with: [
                'isApproved' => $this->report->isApproved(),
                'maskedTarget' => StringHelper::mask_id($this->report->target_id, $this->report->type),
                'rejectionReason' => $this->report->rejection_reason,
                'reportUrl' => $this->report->isApproved() ? route('scammer.show', $this->report->slug) : null,
                'formUrl' => route('reports'),
            ],
        );
    }
}
