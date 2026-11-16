<?php

namespace App\Models;

use App\Enums\ClaimStatus;
use App\Helpers\StringHelper;
use Database\Factories\InsuranceClaimFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * A buyer's request to be compensated from an insurance partner's deposit.
 *
 * Public pages may show: status, amounts, dates, public_summary and maskedClaimantName().
 * Admin-only: claimant_name, contact_email, contact_phone, description, evidence_paths,
 * decision_note, ip_address and event notes.
 * Change status only through InsuranceClaimService.
 */
class InsuranceClaim extends Model
{
    /** @use HasFactory<InsuranceClaimFactory> */
    use HasFactory;

    protected $fillable = [
        'insurance_id',
        'claimant_name',
        'contact_email',
        'contact_phone',
        'amount_claimed',
        'description',
        'evidence_paths',
        'status',
        'public_summary',
        'decision_note',
        'decided_by',
        'decided_at',
        'approved_amount',
        'paid_at',
        'ip_address',
    ];

    protected $attributes = [
        'status' => 'submitted',
    ];

    protected function casts(): array
    {
        return [
            'status' => ClaimStatus::class,
            'amount_claimed' => 'decimal:2',
            'approved_amount' => 'decimal:2',
            'evidence_paths' => 'array',
            'decided_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function insurance(): BelongsTo
    {
        return $this->belongsTo(Insurance::class);
    }

    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    /** Status history, oldest first. */
    public function events(): HasMany
    {
        return $this->hasMany(InsuranceClaimEvent::class, 'claim_id')->orderBy('id');
    }

    /** The payout ledger entry, once paid. */
    public function payoutEntry(): HasOne
    {
        return $this->hasOne(InsuranceLedgerEntry::class, 'claim_id');
    }

    public function scopeOfStatus(Builder $query, ClaimStatus $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Claims an admin still has to act on (not rejected or paid yet).
     */
    public function scopeAwaitingAction(Builder $query): Builder
    {
        return $query->whereNotIn('status', [ClaimStatus::Rejected, ClaimStatus::Paid]);
    }

    /**
     * Claimant name safe for public pages, e.g. "Nguyễn Văn A******".
     */
    public function maskedClaimantName(): string
    {
        return StringHelper::mask_reporter_name($this->claimant_name);
    }
}
