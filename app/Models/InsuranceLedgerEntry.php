<?php

namespace App\Models;

use App\Enums\LedgerEntryType;
use Database\Factories\InsuranceLedgerEntryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * One immutable movement on an insurance partner's deposit, as confirmed by an admin.
 * The system never moves money; it only records what admins did outside it.
 *
 * Append-only: updating or deleting a loaded entry throws. Query-builder writes and the
 * cascade when a partner is deleted bypass this guard; the database itself does not enforce it.
 * Create entries through InsuranceLedgerService::record() so balance_after stays consistent.
 */
class InsuranceLedgerEntry extends Model
{
    /** @use HasFactory<InsuranceLedgerEntryFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'insurance_id',
        'type',
        'amount',
        'balance_after',
        'reference',
        'note',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'type' => LedgerEntryType::class,
            'amount' => 'decimal:2',
            'balance_after' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function () {
            throw new LogicException('Insurance ledger entries are append-only and cannot be updated.');
        });

        static::deleting(function () {
            throw new LogicException('Insurance ledger entries are append-only and cannot be deleted.');
        });
    }

    public function insurance(): BelongsTo
    {
        return $this->belongsTo(Insurance::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
