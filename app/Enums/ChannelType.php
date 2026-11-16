<?php

namespace App\Enums;

/**
 * Kind of contact or payment channel an insurance partner (intermediary) officially uses.
 */
enum ChannelType: string
{
    case Phone = 'phone';
    case Facebook = 'facebook';
    case Telegram = 'telegram';
    case Zalo = 'zalo';
    case BankAccount = 'bank_account';
    case Website = 'website';

    public function label(): string
    {
        return match ($this) {
            self::Phone => 'Số điện thoại',
            self::Facebook => 'Facebook',
            self::Telegram => 'Telegram',
            self::Zalo => 'Zalo',
            self::BankAccount => 'Số tài khoản',
            self::Website => 'Website',
        };
    }
}
