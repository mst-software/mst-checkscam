<?php

namespace App\Enums;

/**
 * Kind of movement on an insurance partner's deposit ledger.
 * Amounts are signed: deposit is positive, payout and refund are negative, adjustment is either.
 */
enum LedgerEntryType: string
{
    case Deposit = 'deposit';
    case Payout = 'payout';
    case Refund = 'refund';
    case Adjustment = 'adjustment';

    public function label(): string
    {
        return match ($this) {
            self::Deposit => 'Nạp quỹ',
            self::Payout => 'Chi trả bồi thường',
            self::Refund => 'Hoàn quỹ cho đối tác',
            self::Adjustment => 'Điều chỉnh',
        };
    }

    /**
     * Whether a signed amount (in cents) has the sign this entry type requires.
     */
    public function acceptsAmount(int $cents): bool
    {
        return match ($this) {
            self::Deposit => $cents > 0,
            self::Payout, self::Refund => $cents < 0,
            self::Adjustment => $cents !== 0,
        };
    }
}
