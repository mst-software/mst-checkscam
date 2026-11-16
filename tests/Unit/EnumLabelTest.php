<?php

use App\Enums\ComplaintReason;
use App\Enums\ComplaintStatus;
use App\Enums\ScamType;

it('gives every case a vietnamese label', function (string $enum) {
    foreach ($enum::cases() as $case) {
        expect($case->label())->toBeString()->not->toBeEmpty();
    }
})->with([
    ScamType::class,
    ComplaintReason::class,
    ComplaintStatus::class,
]);

it('keeps the stored values stable', function () {
    expect(array_column(ScamType::cases(), 'value'))->toBe([
        'fake_shop', 'investment', 'romance', 'job_offer', 'impersonation',
        'loan', 'lottery_gift', 'account_takeover', 'other',
    ])
        ->and(array_column(ComplaintReason::cases(), 'value'))->toBe(['wrong_info', 'not_scam', 'remove_request', 'other'])
        ->and(array_column(ComplaintStatus::cases(), 'value'))->toBe(['pending', 'resolved', 'dismissed']);
});

it('labels statuses in vietnamese', function () {
    expect(ComplaintStatus::Pending->label())->toBe('Chờ xử lý')
        ->and(ComplaintStatus::Dismissed->label())->toBe('Đã bác bỏ');
});
