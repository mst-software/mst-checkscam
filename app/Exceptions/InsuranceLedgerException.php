<?php

namespace App\Exceptions;

use DomainException;

/**
 * A ledger entry was refused (invalid amount, wrong sign for its type, or a negative resulting balance).
 * The message is Vietnamese and safe to flash to admins.
 */
class InsuranceLedgerException extends DomainException
{
    public static function invalidAmount(): self
    {
        return new self('Số tiền không hợp lệ (tối đa 2 chữ số thập phân).');
    }

    public static function wrongSign(string $typeLabel): self
    {
        return new self("Số tiền không đúng chiều cho loại giao dịch \"{$typeLabel}\".");
    }

    public static function insufficientBalance(string $balance): self
    {
        return new self('Số dư quỹ không đủ (hiện còn '.number_format((float) $balance, 0, ',', '.').' đ).');
    }
}
