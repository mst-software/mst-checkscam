<?php

namespace App\Mail;

use App\Enums\ClaimStatus;
use App\Models\InsuranceClaim;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Tells the claimant their claim reached a new status. Contains only claimant-facing data:
 * never the decision_note, event notes or anything about the admin who acted.
 */
class InsuranceClaimStatusChanged extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public InsuranceClaim $claim, public ClaimStatus $status) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Yêu cầu bồi thường #'.$this->claim->id.': '.$this->status->label(),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.insurance-claim-status-changed',
            with: [
                'claimId' => $this->claim->id,
                'claimantName' => $this->claim->claimant_name,
                'partnerName' => $this->claim->insurance?->full_name,
                'statusLabel' => $this->status->label(),
                'statusMessage' => $this->statusMessage(),
                'amountClaimed' => $this->formatMoney($this->claim->amount_claimed),
                'approvedAmount' => $this->claim->approved_amount !== null ? $this->formatMoney($this->claim->approved_amount) : null,
            ],
        );
    }

    private function statusMessage(): string
    {
        return match ($this->status) {
            ClaimStatus::Submitted => 'Scamproof đã tiếp nhận yêu cầu bồi thường của bạn. Quản trị viên sẽ xem xét bằng chứng và cập nhật kết quả qua email này.',
            ClaimStatus::NeedInfo => 'Quản trị viên cần thêm thông tin để xử lý yêu cầu của bạn và sẽ liên hệ qua email hoặc số điện thoại bạn đã cung cấp.',
            ClaimStatus::Approved => 'Yêu cầu bồi thường của bạn đã được duyệt. Quản trị viên sẽ liên hệ để thực hiện chi trả.',
            ClaimStatus::Rejected => 'Rất tiếc, yêu cầu bồi thường của bạn không được chấp nhận sau khi xem xét.',
            ClaimStatus::Paid => 'Khoản bồi thường cho yêu cầu của bạn đã được ghi nhận là đã chi trả.',
            ClaimStatus::Reviewing => 'Yêu cầu bồi thường của bạn đang được xem xét.',
        };
    }

    private function formatMoney(mixed $amount): string
    {
        return number_format((float) $amount, 0, ',', '.').' đ';
    }
}
