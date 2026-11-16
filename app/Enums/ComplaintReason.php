<?php

namespace App\Enums;

enum ComplaintReason: string
{
    case WrongInfo = 'wrong_info';
    case NotScam = 'not_scam';
    case RemoveRequest = 'remove_request';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::WrongInfo => 'Thông tin trong báo cáo bị sai',
            self::NotScam => 'Tôi không lừa đảo, bị tố cáo oan',
            self::RemoveRequest => 'Hai bên đã giải quyết, yêu cầu gỡ bài',
            self::Other => 'Lý do khác',
        };
    }
}
