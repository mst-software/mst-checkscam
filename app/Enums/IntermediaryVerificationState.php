<?php

namespace App\Enums;

/**
 * Outcome of checking whether a contact/payment identifier is an official intermediary channel.
 */
enum IntermediaryVerificationState: string
{
    /** Official channel of a currently trusted partner. */
    case Verified = 'verified';

    /** Official channel of a partner that is expired, suspended or inactive. */
    case Lapsed = 'lapsed';

    /** Not an official channel of any partner. */
    case NotListed = 'not_listed';

    public function label(): string
    {
        return match ($this) {
            self::Verified => 'Kênh GDV chính thức',
            self::Lapsed => 'Kênh GDV đã ngừng hiệu lực',
            self::NotListed => 'Không phải kênh GDV chính thức',
        };
    }
}
