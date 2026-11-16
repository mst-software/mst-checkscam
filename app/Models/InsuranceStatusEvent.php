<?php

namespace App\Models;

use App\Enums\InsuranceStatusEventType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Audit row for a partner's standing: suspended, reinstated, or expiry noticed.
 * Written by InsuranceSuspensionService and the insurance:sync-status command.
 */
class InsuranceStatusEvent extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'insurance_id',
        'type',
        'reason',
        'actor_id',
    ];

    protected function casts(): array
    {
        return [
            'type' => InsuranceStatusEventType::class,
        ];
    }

    public function insurance(): BelongsTo
    {
        return $this->belongsTo(Insurance::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
