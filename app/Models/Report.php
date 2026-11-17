<?php

namespace App\Models;

use App\Enums\ScamType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Report extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    /** back: the seller took a sold game/social account back with its original credentials. */
    public const TYPES = ['account', 'website', 'back'];

    public const REPORTER_ROLES = ['victim', 'proxy'];

    protected $table = 'reports';

    protected $fillable = [
        'type',
        'reporter_name',
        'reporter_contact',
        'reporter_email',
        'reporter_role',

        'target_id',
        'target_name',
        'target_bank',
        'target_socials',
        'sold_account',
        'category',
        'scam_type',
        'damage_amount',
        'description',
        'evidence_images',
        'status',
        'approved_at',
        'ip_address',
        'rejection_reason',
        'view_count',
        'search_count',
        'moderator_id',
        'slug',
    ];

    protected $casts = [
        'evidence_images' => 'array',
        'approved_at' => 'datetime',
        'view_count' => 'integer',
        'search_count' => 'integer',
        'scam_type' => ScamType::class,
    ];

    /**
     * ------------------------------------------------------
     * Relationship (BelongsTo & HasMany)
     * ------------------------------------------------------
     */
    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderator_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class, 'report_id')->latest();
    }

    public function complaints(): HasMany
    {
        return $this->hasMany(Complaint::class);
    }

    /**
     * ------------------------------------------------------
     * Scopes
     * ------------------------------------------------------
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeOfScamType(Builder $query, ScamType $scamType): Builder
    {
        return $query->where('scam_type', $scamType);
    }

    /**
     * ------------------------------------------------------
     * Helper Methods
     *
     * @return bool
     *              ------------------------------------------------------
     */
    public function isPending(): bool
    {
        return $this->status == 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status == 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status == 'rejected';
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            'website' => 'Web scam',
            'back' => 'Acc bị back',
            default => 'STK scam',
        };
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->fit(Fit::Contain, 200, 200)
            ->nonQueued();

        $this->addMediaConversion('optimized')
            ->fit(Fit::Max, 1000, 1000)
            ->nonQueued();
    }

    public function incrementViewCount(): void
    {
        $this->increment('view_count');
    }

    public function incrementSearchCount(): void
    {
        $this->increment('search_count');
    }
}
