<?php

use App\Enums\ChannelType;
use App\Enums\IntermediaryVerificationState;
use App\Models\Insurance;
use App\Models\InsuranceChannel;
use App\Models\Report;
use App\Services\IntermediaryVerification;
use App\Services\IntermediaryVerifier;
use App\Services\ScamLookupService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->registerMysqlCompatibleSqliteFunctions();
    $this->verifier = app(IntermediaryVerifier::class);
});

function partnerWithChannel(Insurance $insurance, ChannelType $type, string $value, bool $official = true): Insurance
{
    InsuranceChannel::factory()->for($insurance)->ofType($type, $value)->create(['is_official' => $official]);

    return $insurance;
}

test('an official channel of a trusted partner is verified', function (ChannelType $type, string $stored, string $input) {
    $partner = partnerWithChannel(Insurance::factory()->trusted()->create(['full_name' => 'GDV Hùng']), $type, $stored);

    $result = $this->verifier->verify($input);

    expect($result->state)->toBe(IntermediaryVerificationState::Verified)
        ->and($result->isVerified())->toBeTrue()
        ->and($result->insurance->id)->toBe($partner->id)
        ->and($result->channelType())->toBe($type)
        ->and($result->message())->toContain('GDV Hùng')
        ->and($result->partnerSummary())->toMatchArray([
            'id' => $partner->id,
            'name' => 'GDV Hùng',
            'slug' => $partner->slug,
            'balance' => (string) $partner->amount,
        ]);
})->with([
    'phone with +84' => [ChannelType::Phone, '0912.345.678', '+84 912 345 678'],
    'zalo by phone' => [ChannelType::Zalo, 'https://zalo.me/0912345678', '0912345678'],
    'bank with spaces' => [ChannelType::BankAccount, '1903 4567 8901 23', '19034567890123'],
    'facebook url' => [ChannelType::Facebook, 'https://facebook.com/GDV.Hung', 'https://www.facebook.com/gdv.hung?ref=x'],
    'telegram @' => [ChannelType::Telegram, 'https://t.me/gdv_hung', '@GDV_Hung'],
    'website' => [ChannelType::Website, 'https://gdvhung.vn', 'gdvhung.vn'],
]);

test('a facebook username does not verify a telegram channel with the same handle', function () {
    partnerWithChannel(Insurance::factory()->trusted()->create(), ChannelType::Telegram, '@gdv_hung');

    expect($this->verifier->verify('https://facebook.com/gdv_hung')->state)->toBe(IntermediaryVerificationState::NotListed);
});

test('a channel of an expired, suspended or inactive partner is lapsed', function (Closure $makePartner, string $reason, string $wording) {
    $partner = partnerWithChannel($makePartner(), ChannelType::Phone, '0912345678');

    $result = $this->verifier->verify('0912345678');

    expect($result->state)->toBe(IntermediaryVerificationState::Lapsed)
        ->and($result->isVerified())->toBeFalse()
        ->and($result->lapsedReason)->toBe($reason)
        ->and($result->insurance->id)->toBe($partner->id)
        ->and($result->message())->toContain($wording)
        ->and($result->message())->toContain('không phải kênh GDV chính thức');
})->with([
    'expired' => [fn () => Insurance::factory()->trusted()->expired()->create(), IntermediaryVerification::LAPSED_EXPIRED, 'hết hạn'],
    'suspended' => [fn () => Insurance::factory()->trusted()->suspended()->create(), IntermediaryVerification::LAPSED_SUSPENDED, 'tạm đình chỉ'],
    'inactive' => [fn () => Insurance::factory()->trusted()->create(['status' => 0]), IntermediaryVerification::LAPSED_INACTIVE, 'ngừng hoạt động'],
]);

test('a trusted partner wins over a lapsed one sharing the channel', function () {
    partnerWithChannel(Insurance::factory()->trusted()->suspended()->create(), ChannelType::Phone, '0912345678');
    $trusted = partnerWithChannel(Insurance::factory()->trusted()->create(), ChannelType::Phone, '0912345678');

    $result = $this->verifier->verify('0912345678');

    expect($result->isVerified())->toBeTrue()->and($result->insurance->id)->toBe($trusted->id);
});

test('unknown, unofficial and non-channel inputs are not listed', function (string $input) {
    partnerWithChannel(Insurance::factory()->trusted()->create(), ChannelType::Phone, '0987654321', official: false);

    $result = $this->verifier->verify($input);

    expect($result->state)->toBe(IntermediaryVerificationState::NotListed)
        ->and($result->insurance)->toBeNull()
        ->and($result->partnerSummary())->toBeNull()
        ->and($result->message())->toBe('Không phải kênh GDV chính thức của Scamproof.');
})->with(['0912000111', '0987654321', 'Nguyễn Văn Hùng', '']);

test('reported count counts approved scam reports on the same identifier', function () {
    Report::factory()->count(2)->create(['status' => 'approved', 'target_id' => '0912.345.678']);
    Report::factory()->create(['status' => 'pending', 'target_id' => '0912345678']);

    $result = $this->verifier->verify('+84912345678');

    expect($result->reportedCount)->toBe(2)
        ->and($result->reportsWarning())->toBe('Có 2 tố cáo đã duyệt liên quan đến thông tin này.')
        ->and($this->verifier->verify('0900000000')->reportsWarning())->toBeNull();
});

test('no wording ever calls a result safe', function () {
    partnerWithChannel(Insurance::factory()->trusted()->create(), ChannelType::Phone, '0911111111');
    partnerWithChannel(Insurance::factory()->trusted()->suspended()->create(), ChannelType::Phone, '0922222222');
    Report::factory()->create(['status' => 'approved', 'target_id' => '0933333333']);

    $texts = collect(['0911111111', '0922222222', '0933333333'])
        ->map(fn (string $input) => $this->verifier->verify($input))
        ->flatMap(fn (IntermediaryVerification $result) => [$result->message(), $result->reportsWarning(), $result->state->label(), json_encode($result->toPublicArray(), JSON_UNESCAPED_UNICODE)])
        ->filter()
        ->implode(' ');

    expect(mb_strtolower($texts))->not->toContain('an toàn')->not->toContain('safe');
});

test('lookup matches insurance partners through official channels and exposes the verification', function () {
    $partner = partnerWithChannel(Insurance::factory()->trusted()->create(['full_name' => 'Trần Minh']), ChannelType::BankAccount, '1903 4567 8901 23');
    partnerWithChannel(Insurance::factory()->trusted()->create(), ChannelType::BankAccount, '1903 4567 8901 23', official: false);

    $result = app(ScamLookupService::class)->lookup('19034567890123');

    expect($result->insurances->pluck('id')->all())->toBe([$partner->id])
        ->and($result->isVerifiedIntermediary())->toBeTrue()
        ->and($result->toPublicArray()['verified_intermediary'])->toBeTrue();
});

test('the legacy json no longer matches partners without a channel row', function () {
    Insurance::factory()->trusted()->create([
        'payment_accounts' => [['bank' => 'VCB', 'number' => '5556667778', 'name' => 'X']],
    ]);

    expect(app(ScamLookupService::class)->matchingInsurances('5556667778'))->toBeEmpty();
});
