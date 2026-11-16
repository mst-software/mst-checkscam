<?php

namespace App\Enums;

/**
 * Audit trail entry for an insurance partner's standing (independent of the money ledger).
 */
enum InsuranceStatusEventType: string
{
    case Suspended = 'suspended';
    case Reinstated = 'reinstated';
    case ExpiredNotice = 'expired_notice';

    public function label(): string
    {
        return match ($this) {
            self::Suspended => 'Tạm đình chỉ',
            self::Reinstated => 'Khôi phục hoạt động',
            self::ExpiredNotice => 'Hết hạn bảo hiểm',
        };
    }
}
