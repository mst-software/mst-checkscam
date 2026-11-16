<?php

use App\Enums\LedgerEntryType;
use App\Exceptions\InsuranceLedgerException;
use App\Models\Insurance;
use App\Models\InsuranceLedgerEntry;
use App\Models\User;
use App\Services\InsuranceLedgerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->ledger = app(InsuranceLedgerService::class);
    $this->admin = User::factory()->create(['role' => 'admin']);
    $this->insurance = Insurance::factory()->create(['amount' => 10000000]);
});

test('a new partner starts with one opening deposit equal to its amount', function () {
    $entry = $this->insurance->ledgerEntries()->sole();

    expect($entry->type)->toBe(LedgerEntryType::Deposit)
        ->and((string) $entry->amount)->toBe('10000000.00')
        ->and((string) $entry->balance_after)->toBe('10000000.00')
        ->and($entry->note)->toBe('Số dư ban đầu');
});

test('entries keep a running balance and update the cached amount', function () {
    $this->ledger->record($this->insurance, LedgerEntryType::Deposit, '5000000', $this->admin, 'VCB FT1', 'Nạp thêm');
    $this->ledger->record($this->insurance, LedgerEntryType::Refund, -2500000.5, $this->admin);
    $last = $this->ledger->record($this->insurance, LedgerEntryType::Adjustment, '0.50', $this->admin, note: 'Làm tròn');

    expect((string) $last->balance_after)->toBe('12500000.00')
        ->and((string) $this->insurance->amount)->toBe('12500000.00')
        ->and((string) $this->insurance->fresh()->amount)->toBe('12500000.00')
        ->and($this->ledger->balanceFromLedger($this->insurance))->toBe('12500000.00')
        ->and($this->insurance->ledgerEntries()->pluck('balance_after')->map(fn ($v) => (string) $v)->all())
        ->toBe(['10000000.00', '15000000.00', '12499999.50', '12500000.00']);

    $deposit = $this->insurance->ledgerEntries()->where('reference', 'VCB FT1')->sole();
    expect($deposit->recorded_by)->toBe($this->admin->id)->and($deposit->note)->toBe('Nạp thêm');
});

test('a negative resulting balance is refused and nothing is written', function () {
    expect(fn () => $this->ledger->record($this->insurance, LedgerEntryType::Payout, '-10000000.01', $this->admin))
        ->toThrow(InsuranceLedgerException::class, 'Số dư quỹ không đủ');

    expect($this->insurance->ledgerEntries()->count())->toBe(1)
        ->and((string) $this->insurance->fresh()->amount)->toBe('10000000.00');
});

test('the balance may reach exactly zero', function () {
    $entry = $this->ledger->record($this->insurance, LedgerEntryType::Payout, '-10000000', $this->admin);

    expect((string) $entry->balance_after)->toBe('0.00');
});

test('zero, wrong-sign and malformed amounts are refused', function (LedgerEntryType $type, string|int|float $amount) {
    expect(fn () => $this->ledger->record($this->insurance, $type, $amount, $this->admin))
        ->toThrow(InsuranceLedgerException::class);

    expect($this->insurance->ledgerEntries()->count())->toBe(1);
})->with([
    'zero deposit' => [LedgerEntryType::Deposit, 0],
    'negative deposit' => [LedgerEntryType::Deposit, '-100'],
    'positive payout' => [LedgerEntryType::Payout, '100'],
    'positive refund' => [LedgerEntryType::Refund, 100],
    'zero adjustment' => [LedgerEntryType::Adjustment, '0.00'],
    'three decimals' => [LedgerEntryType::Deposit, '1.005'],
    'text' => [LedgerEntryType::Deposit, '1e5'],
    'float with fraction of a cent' => [LedgerEntryType::Deposit, 0.001],
]);

test('ledger entries cannot be updated or deleted', function () {
    $entry = $this->insurance->ledgerEntries()->sole();

    expect(fn () => $entry->update(['amount' => 1]))->toThrow(LogicException::class)
        ->and(fn () => $entry->delete())->toThrow(LogicException::class);

    expect((string) $entry->fresh()->amount)->toBe('10000000.00');
});

test('a stale model instance is re-read under lock so balances never use an old amount', function () {
    $stale = Insurance::find($this->insurance->id);

    $this->ledger->record($this->insurance, LedgerEntryType::Payout, '-6000000', $this->admin);

    // The stale copy still says 10,000,000; recording against it must use the locked current balance.
    expect(fn () => $this->ledger->record($stale, LedgerEntryType::Payout, '-6000000', $this->admin))
        ->toThrow(InsuranceLedgerException::class);

    $entry = $this->ledger->record($stale, LedgerEntryType::Payout, '-4000000', $this->admin);
    expect((string) $entry->balance_after)->toBe('0.00');
});

test('an entry recorded inside a rolled back outer transaction leaves no trace', function () {
    try {
        DB::transaction(function () {
            $this->ledger->record($this->insurance, LedgerEntryType::Deposit, '1000000', $this->admin);

            throw new RuntimeException('outer failure');
        });
    } catch (RuntimeException) {
    }

    expect(InsuranceLedgerEntry::where('insurance_id', $this->insurance->id)->count())->toBe(1)
        ->and((string) $this->insurance->fresh()->amount)->toBe('10000000.00');
});

test('cents conversion round-trips', function () {
    expect(InsuranceLedgerService::toCents('1234.5'))->toBe(123450)
        ->and(InsuranceLedgerService::toCents(-0.05))->toBe(-5)
        ->and(InsuranceLedgerService::toCents(7))->toBe(700)
        ->and(InsuranceLedgerService::fromCents(-5))->toBe('-0.05')
        ->and(InsuranceLedgerService::fromCents(123450))->toBe('1234.50');
});
