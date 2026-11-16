<?php

use App\Enums\ChannelType;
use App\Models\Insurance;
use App\Models\InsuranceChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('channels are backfilled from the legacy json idempotently', function () {
    $insurance = Insurance::factory()->create([
        'contact_info' => [
            ['platform' => 'Telegram', 'link' => 'https://t.me/GDV_Hung'],
            ['platform' => '???', 'link' => 'không rõ'],
        ],
        'payment_accounts' => [['bank' => 'VCB', 'number' => '0011 0022 33', 'name' => 'X']],
    ]);
    DB::table('insurances')->insert([
        'full_name' => 'Hỏng JSON', 'amount' => 0, 'insurance_date' => now(), 'slug' => 'hong-json',
        'contact_info' => '"just a string"', 'payment_accounts' => null,
    ]);

    $migration = require database_path('migrations/2026_11_16_184722_create_insurance_channels_table.php');
    $migration->backfillChannels();
    $migration->backfillChannels();

    expect(InsuranceChannel::count())->toBe(2)
        ->and($insurance->channels()->orderBy('id')->get()->map(fn ($c) => [$c->type, $c->normalized_value, $c->is_official])->all())
        ->toBe([[ChannelType::Telegram, 'gdv_hung', true], [ChannelType::BankAccount, '0011002233', true]]);
});

test('opening balances are backfilled once for partners without ledger history', function () {
    $insurance = Insurance::factory()->create(['amount' => 7000000]);
    $insurance->ledgerEntries()->getQuery()->toBase()->delete();
    $empty = Insurance::factory()->create(['amount' => 0]);

    $migration = require database_path('migrations/2026_11_16_184727_create_insurance_ledger_entries_table.php');
    $migration->backfillOpeningBalances();
    $migration->backfillOpeningBalances();

    $entry = $insurance->ledgerEntries()->sole();
    expect((string) $entry->amount)->toBe('7000000.00')
        ->and((string) $entry->balance_after)->toBe('7000000.00')
        ->and($entry->note)->toBe('Số dư ban đầu')
        ->and($empty->ledgerEntries()->count())->toBe(0);
});

test('verified_at is backfilled from the insurance date', function () {
    $insurance = Insurance::factory()->create(['insurance_date' => '2025-05-01', 'verified_at' => null]);

    $migration = require database_path('migrations/2026_11_16_184720_add_trust_fields_to_insurances_table.php');
    $migration->backfillVerifiedAt();

    expect($insurance->fresh()->verified_at->toDateString())->toBe('2025-05-01');
});
