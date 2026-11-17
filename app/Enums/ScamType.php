<?php

namespace App\Enums;

enum ScamType: string
{
    case FakeShop = 'fake_shop';
    case Investment = 'investment';
    case Romance = 'romance';
    case JobOffer = 'job_offer';
    case Impersonation = 'impersonation';
    case Loan = 'loan';
    case LotteryGift = 'lottery_gift';
    case AccountTakeover = 'account_takeover';
    case AccountBack = 'account_back';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::FakeShop => 'Bán hàng online giả mạo',
            self::Investment => 'Đầu tư, tiền ảo, đa cấp',
            self::Romance => 'Lừa tình cảm',
            self::JobOffer => 'Việc nhẹ lương cao, cộng tác viên',
            self::Impersonation => 'Giả danh công an, ngân hàng, cơ quan',
            self::Loan => 'Cho vay, hỗ trợ vay vốn',
            self::LotteryGift => 'Trúng thưởng, nhận quà',
            self::AccountTakeover => 'Chiếm đoạt tài khoản, mã OTP',
            self::AccountBack => 'Bán tài khoản rồi lấy lại (back acc)',
            self::Other => 'Hình thức khác',
        };
    }
}
