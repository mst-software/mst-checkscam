<?php

namespace App\Exceptions;

use App\Enums\ClaimStatus;
use DomainException;

/**
 * A claim action was refused (disallowed status transition or invalid amounts).
 * The message is Vietnamese and safe to flash to admins.
 */
class InsuranceClaimException extends DomainException
{
    public static function invalidTransition(ClaimStatus $from, ClaimStatus $to): self
    {
        return new self("Không thể chuyển yêu cầu từ \"{$from->label()}\" sang \"{$to->label()}\".");
    }

    public static function useMarkPaid(): self
    {
        return new self('Dùng thao tác ghi nhận chi trả để chuyển yêu cầu sang "Đã chi trả".');
    }

    public static function invalidApprovedAmount(): self
    {
        return new self('Số tiền duyệt phải lớn hơn 0 và không vượt quá số tiền yêu cầu.');
    }

    public static function invalidPaidAmount(): self
    {
        return new self('Số tiền chi trả phải lớn hơn 0 và không vượt quá số tiền đã duyệt.');
    }

    public static function invalidClaimedAmount(): self
    {
        return new self('Số tiền yêu cầu bồi thường phải lớn hơn 0.');
    }
}
