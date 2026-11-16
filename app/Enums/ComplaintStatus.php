<?php

namespace App\Enums;

enum ComplaintStatus: string
{
    case Pending = 'pending';
    case Resolved = 'resolved';
    case Dismissed = 'dismissed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Chờ xử lý',
            self::Resolved => 'Đã xử lý',
            self::Dismissed => 'Đã bác bỏ',
        };
    }
}
