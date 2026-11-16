<?php

use App\Enums\ChannelType;
use App\Enums\ClaimStatus;
use App\Enums\ComplaintReason;
use App\Enums\ComplaintStatus;
use App\Enums\InsuranceStatusEventType;
use App\Enums\IntermediaryVerificationState;
use App\Enums\LedgerEntryType;
use App\Enums\ScamType;

it('gives every case a vietnamese label', function (string $enum) {
    foreach ($enum::cases() as $case) {
        expect($case->label())->toBeString()->not->toBeEmpty();
    }
})->with([
    ScamType::class,
    ComplaintReason::class,
    ComplaintStatus::class,
    ChannelType::class,
    LedgerEntryType::class,
    ClaimStatus::class,
    InsuranceStatusEventType::class,
    IntermediaryVerificationState::class,
]);

it('keeps the stored values stable', function () {
    expect(array_column(ScamType::cases(), 'value'))->toBe([
        'fake_shop', 'investment', 'romance', 'job_offer', 'impersonation',
        'loan', 'lottery_gift', 'account_takeover', 'other',
    ])
        ->and(array_column(ComplaintReason::cases(), 'value'))->toBe(['wrong_info', 'not_scam', 'remove_request', 'other'])
        ->and(array_column(ComplaintStatus::cases(), 'value'))->toBe(['pending', 'resolved', 'dismissed'])
        ->and(array_column(ChannelType::cases(), 'value'))->toBe(['phone', 'facebook', 'telegram', 'zalo', 'bank_account', 'website'])
        ->and(array_column(LedgerEntryType::cases(), 'value'))->toBe(['deposit', 'payout', 'refund', 'adjustment'])
        ->and(array_column(ClaimStatus::cases(), 'value'))->toBe(['submitted', 'reviewing', 'need_info', 'approved', 'rejected', 'paid']);
});

it('labels statuses in vietnamese', function () {
    expect(ComplaintStatus::Pending->label())->toBe('Chờ xử lý')
        ->and(ComplaintStatus::Dismissed->label())->toBe('Đã bác bỏ');
});

it('defines the claim workflow transitions', function () {
    expect(ClaimStatus::Submitted->allowedTransitions())->toBe([ClaimStatus::Reviewing, ClaimStatus::Rejected])
        ->and(ClaimStatus::Reviewing->allowedTransitions())->toBe([ClaimStatus::NeedInfo, ClaimStatus::Approved, ClaimStatus::Rejected])
        ->and(ClaimStatus::NeedInfo->allowedTransitions())->toBe([ClaimStatus::Reviewing, ClaimStatus::Rejected])
        ->and(ClaimStatus::Approved->allowedTransitions())->toBe([ClaimStatus::Paid])
        ->and(ClaimStatus::Rejected->isFinal())->toBeTrue()
        ->and(ClaimStatus::Paid->isFinal())->toBeTrue()
        ->and(ClaimStatus::Approved->isFinal())->toBeFalse();
});

it('checks ledger amount signs per entry type', function () {
    expect(LedgerEntryType::Deposit->acceptsAmount(1))->toBeTrue()
        ->and(LedgerEntryType::Deposit->acceptsAmount(-1))->toBeFalse()
        ->and(LedgerEntryType::Payout->acceptsAmount(-1))->toBeTrue()
        ->and(LedgerEntryType::Refund->acceptsAmount(1))->toBeFalse()
        ->and(LedgerEntryType::Adjustment->acceptsAmount(-1))->toBeTrue()
        ->and(LedgerEntryType::Adjustment->acceptsAmount(0))->toBeFalse();
});
