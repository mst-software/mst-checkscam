<?php

namespace App\Models;

use App\Enums\ClaimStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One status change of an insurance claim. `note` and `actor_id` are admin-only;
 * public pages may show the statuses and the date.
 */
class InsuranceClaimEvent extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'claim_id',
        'from_status',
        'to_status',
        'note',
        'actor_id',
    ];

    protected function casts(): array
    {
        return [
            'from_status' => ClaimStatus::class,
            'to_status' => ClaimStatus::class,
        ];
    }

    public function claim(): BelongsTo
    {
        return $this->belongsTo(InsuranceClaim::class, 'claim_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
