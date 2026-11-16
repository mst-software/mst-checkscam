<?php

namespace App\Models;

use App\Enums\ComplaintReason;
use App\Enums\ComplaintStatus;
use Database\Factories\ComplaintFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Complaint extends Model
{
    /** @use HasFactory<ComplaintFactory> */
    use HasFactory;

    protected $fillable = [
        'report_id',
        'contact_name',
        'contact_email',
        'contact_phone',
        'reason',
        'message',
        'evidence_images',
        'status',
        'admin_note',
        'handled_by',
        'handled_at',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'reason' => ComplaintReason::class,
            'status' => ComplaintStatus::class,
            'evidence_images' => 'array',
            'handled_at' => 'datetime',
        ];
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function scopeOfStatus(Builder $query, ComplaintStatus $status): Builder
    {
        return $query->where('status', $status);
    }

    public function isPending(): bool
    {
        return $this->status === ComplaintStatus::Pending;
    }
}
