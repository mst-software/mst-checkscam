<?php

namespace App\Services;

use App\Enums\LedgerEntryType;
use App\Exceptions\InsuranceLedgerException;
use App\Models\Insurance;
use App\Models\InsuranceLedgerEntry;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Records admin-confirmed movements on an insurance partner's deposit.
 * The ledger is the source of truth; `insurances.amount` is kept equal to the last balance_after.
 * Money is handled outside the system: this only writes down what an admin confirmed.
 */
class InsuranceLedgerService
{
    /** decimal(15,2) upper bound, in cents. */
    private const MAX_CENTS = 999_999_999_999_999;

    /**
     * Append one entry and update the cached balance, atomically and with the partner row locked.
     *
     * The amount is SIGNED as stored: deposit > 0, payout < 0 (legacy), refund < 0 (money returned to the
     * partner), adjustment != 0. Max two decimals.
     *
     * Example: $ledger->record($insurance, LedgerEntryType::Deposit, '5000000', $admin, 'VCB FT123', 'Nạp thêm quỹ');
     *
     * @throws InsuranceLedgerException when the amount is invalid, has the wrong sign, or the balance would go negative.
     */
    public function record(
        Insurance $insurance,
        LedgerEntryType $type,
        string|int|float $amount,
        ?User $by = null,
        ?string $reference = null,
        ?string $note = null,
    ): InsuranceLedgerEntry {
        $cents = self::toCents($amount);

        if (! $type->acceptsAmount($cents)) {
            throw InsuranceLedgerException::wrongSign($type->label());
        }

        $entry = DB::transaction(function () use ($insurance, $type, $cents, $by, $reference, $note) {
            $locked = Insurance::query()->lockForUpdate()->findOrFail($insurance->getKey());
            $balance = self::toCents((string) $locked->amount) + $cents;

            if ($balance < 0) {
                throw InsuranceLedgerException::insufficientBalance((string) $locked->amount);
            }

            if ($balance > self::MAX_CENTS) {
                throw InsuranceLedgerException::invalidAmount();
            }

            $entry = InsuranceLedgerEntry::create([
                'insurance_id' => $locked->id,
                'type' => $type,
                'amount' => self::fromCents($cents),
                'balance_after' => self::fromCents($balance),
                'reference' => $reference,
                'note' => $note,
                'recorded_by' => $by?->id,
            ]);

            $locked->forceFill(['amount' => self::fromCents($balance)])->save();

            return $entry;
        });

        $insurance->forceFill(['amount' => $entry->balance_after])->syncOriginalAttribute('amount');

        return $entry;
    }

    /**
     * Balance recomputed from the ledger (sum of amounts), for reconciliation with `insurances.amount`.
     */
    public function balanceFromLedger(Insurance $insurance): string
    {
        $cents = $insurance->ledgerEntries()->pluck('amount')
            ->sum(fn (string $amount) => self::toCents($amount));

        return self::fromCents($cents);
    }

    /**
     * Parse a money amount into integer cents.
     *
     * @throws InsuranceLedgerException
     */
    public static function toCents(string|int|float $amount): int
    {
        if (is_float($amount)) {
            if (! is_finite($amount) || abs(round($amount * 100) - $amount * 100) > 1e-6) {
                throw InsuranceLedgerException::invalidAmount();
            }
            $amount = number_format($amount, 2, '.', '');
        }

        $amount = trim((string) $amount);

        if (! preg_match('/^(-)?(\d{1,13})(?:\.(\d{1,2}))?$/', $amount, $matches)) {
            throw InsuranceLedgerException::invalidAmount();
        }

        $cents = (int) $matches[2] * 100 + (int) str_pad($matches[3] ?? '0', 2, '0');

        return $matches[1] === '-' ? -$cents : $cents;
    }

    public static function fromCents(int $cents): string
    {
        $sign = $cents < 0 ? '-' : '';
        $cents = abs($cents);

        return $sign.intdiv($cents, 100).'.'.str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }
}
