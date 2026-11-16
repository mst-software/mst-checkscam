<?php

use App\Enums\ChannelType;
use App\Helpers\StringHelper;
use App\Models\InsuranceChannel;

it('normalizes phone numbers to the 0 prefix digits', function (string $input) {
    expect(InsuranceChannel::normalize(ChannelType::Phone, $input))->toBe('0912345678');
})->with(['0912345678', '+84912345678', '84912345678', '0912.345.678', '0912 345 678', '+84 912-345-678']);

it('normalizes bank accounts to lowercase alphanumerics', function (string $input, string $expected) {
    expect(InsuranceChannel::normalize(ChannelType::BankAccount, $input))->toBe($expected);
})->with([
    ['1903 4567 8901 23', '19034567890123'],
    ['1903.4567.8901.23', '19034567890123'],
    ['1903-4567-8901-23', '19034567890123'],
    ['VCB 0011ABC', 'vcb0011abc'],
]);

it('normalizes facebook profiles to the lowercase username or uid', function (string $input, string $expected) {
    expect(InsuranceChannel::normalize(ChannelType::Facebook, $input))->toBe($expected);
})->with([
    ['https://www.facebook.com/Le.Hoang.Anh2000?ref=abc', 'le.hoang.anh2000'],
    ['facebook.com/le.hoang.anh2000/', 'le.hoang.anh2000'],
    ['https://m.facebook.com/le.hoang.anh2000', 'le.hoang.anh2000'],
    ['https://www.facebook.com/profile.php?id=100012345678', '100012345678'],
    ['fb.com/Le.Hoang.Anh2000', 'le.hoang.anh2000'],
    ['Le.Hoang.Anh2000', 'le.hoang.anh2000'],
]);

it('normalizes telegram handles without @ or host', function (string $input) {
    expect(InsuranceChannel::normalize(ChannelType::Telegram, $input))->toBe('gdv_hung');
})->with(['@GDV_Hung', 'gdv_hung', 'https://t.me/gdv_hung', 't.me/GDV_Hung?start=1', 'https://telegram.me/gdv_hung/']);

it('normalizes zalo links to a phone number or lowercase handle', function (string $input, string $expected) {
    expect(InsuranceChannel::normalize(ChannelType::Zalo, $input))->toBe($expected);
})->with([
    ['https://zalo.me/0912345678', '0912345678'],
    ['zalo.me/84912345678', '0912345678'],
    ['0912 345 678', '0912345678'],
    ['https://zalo.me/GdvHung', 'gdvhung'],
]);

it('normalizes websites to host and path', function (string $input) {
    expect(InsuranceChannel::normalize(ChannelType::Website, $input))->toBe('gdvhung.vn/shop');
})->with(['https://www.GdvHung.vn/shop/', 'http://gdvhung.vn/shop?utm=1', 'gdvhung.vn/shop']);

it('detects which channel types an input can be', function (string $input, array $types, string $value) {
    [$detectedTypes, $detectedValue] = StringHelper::detectChannelQuery($input);

    expect($detectedTypes)->toBe($types)->and($detectedValue)->toBe($value);
})->with([
    'phone' => ['+84 912 345 678', [ChannelType::Phone, ChannelType::Zalo, ChannelType::BankAccount], '0912345678'],
    'bank' => ['1903 4567 8901 23', [ChannelType::BankAccount], '19034567890123'],
    'facebook url' => ['https://facebook.com/le.hoang', [ChannelType::Facebook], 'le.hoang'],
    'telegram @' => ['@Gdv_Hung', [ChannelType::Telegram], 'gdv_hung'],
    'telegram url' => ['https://t.me/gdv_hung', [ChannelType::Telegram], 'gdv_hung'],
    'zalo url' => ['zalo.me/0912345678', [ChannelType::Zalo], '0912345678'],
    'website' => ['https://gdvhung.vn/', [ChannelType::Website], 'gdvhung.vn'],
    'bare handle' => ['gdvhung', [ChannelType::Facebook, ChannelType::Telegram, ChannelType::Zalo], 'gdvhung'],
    'dotted handle' => ['le.hoang', [ChannelType::Website, ChannelType::Facebook, ChannelType::Telegram, ChannelType::Zalo], 'le.hoang'],
    'person name' => ['Nguyễn Văn Hùng', [], ''],
    'empty' => ['  ', [], ''],
]);

it('extracts channels from the legacy json and skips unknown shapes', function () {
    $channels = InsuranceChannel::fromLegacyJson(
        [
            ['platform' => 'Facebook', 'link' => 'https://facebook.com/gdv.hung'],
            ['platform' => 'Zalo', 'link' => 'https://zalo.me/0912345678'],
            ['platform' => 'Telegram', 'link' => 'https://t.me/gdv_hung'],
            ['platform' => 'SĐT', 'link' => '0987.654.321'],
            ['platform' => 'Khác', 'link' => 'liên hệ qua group'],
            ['platform' => 'Facebook', 'link' => ''],
            'not-an-array',
            ['link' => ['nested']],
        ],
        [
            ['bank' => 'VCB', 'number' => '0011 0022 33', 'name' => 'NGUYEN VAN HUNG'],
            ['bank' => 'VCB', 'number' => '0011.0022.33', 'name' => 'duplicate'],
            ['bank' => 'ACB'],
        ],
    );

    expect(collect($channels)->map(fn (array $channel) => $channel['type']->value.':'.$channel['normalized_value'])->all())->toBe([
        'facebook:gdv.hung',
        'zalo:0912345678',
        'telegram:gdv_hung',
        'phone:0987654321',
        'bank_account:0011002233',
    ]);

    expect(InsuranceChannel::fromLegacyJson(null, 'garbage'))->toBe([]);
});
