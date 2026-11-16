<?php

namespace App\Enums;

/**
 * Workflow of a compensation claim against an insurance partner's deposit.
 *
 * submitted -> reviewing | rejected
 * reviewing -> need_info | approved | rejected
 * need_info -> reviewing | rejected
 * approved  -> paid
 * rejected, paid: final
 */
enum ClaimStatus: string
{
    case Submitted = 'submitted';
    case Reviewing = 'reviewing';
    case NeedInfo = 'need_info';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Paid = 'paid';

    public function label(): string
    {
        return match ($this) {
            self::Submitted => 'Đã tiếp nhận',
            self::Reviewing => 'Đang xem xét',
            self::NeedInfo => 'Cần bổ sung thông tin',
            self::Approved => 'Đã duyệt bồi thường',
            self::Rejected => 'Từ chối',
            self::Paid => 'Đã chi trả',
        };
    }

    /**
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Submitted => [self::Reviewing, self::Rejected],
            self::Reviewing => [self::NeedInfo, self::Approved, self::Rejected],
            self::NeedInfo => [self::Reviewing, self::Rejected],
            self::Approved => [self::Paid],
            self::Rejected, self::Paid => [],
        };
    }

    public function canTransitionTo(self $to): bool
    {
        return in_array($to, $this->allowedTransitions(), true);
    }

    public function isFinal(): bool
    {
        return $this->allowedTransitions() === [];
    }

    /**
     * Statuses the claimant is emailed about.
     */
    public function notifiesClaimant(): bool
    {
        return $this !== self::Reviewing;
    }
}
