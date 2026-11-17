<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Insurance extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $table = 'insurances';

    protected $fillable = [
        'full_name',
        'avatar',
        'amount',
        'insurance_date',
        'expired_at',
        'contact_info',
        'payment_accounts',
        'services',
        'status',
        'slug',
        'verified_at',
    ];

    /**
     * `amount` is a cached copy of the latest ledger balance; change it only through InsuranceLedgerService.
     * `suspended_at` / `suspension_reason` are set only through InsuranceSuspensionService.
     */
    protected $casts = [
        'contact_info' => 'array',
        'payment_accounts' => 'array',
        'services' => 'array',
        'amount' => 'decimal:2',
        'insurance_date' => 'date',
        'expired_at' => 'date',
        'verified_at' => 'date',
        'suspended_at' => 'datetime',
    ];

    /**
     * ------------------------------------------------------
     * Relationships
     * ------------------------------------------------------
     */

    /** Official contact/payment channels (phone, Facebook, Telegram, Zalo, bank account, website). */
    public function channels(): HasMany
    {
        return $this->hasMany(InsuranceChannel::class);
    }

    /** Append-only deposit ledger, oldest first. */
    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(InsuranceLedgerEntry::class)->orderBy('id');
    }

    /** Suspension / reinstatement / expiry audit trail, oldest first. */
    public function statusEvents(): HasMany
    {
        return $this->hasMany(InsuranceStatusEvent::class)->orderBy('id');
    }

    /**
     * ------------------------------------------------------
     * Helper Methods
     * ------------------------------------------------------
     */
    public function isActive(): bool
    {
        return $this->status == 1;
    }

    /**
     * Stable public member code shown on the profile, e.g. "GDV-000123".
     */
    public function memberCode(): string
    {
        return 'GDV-'.str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Active (status 1), not suspended partners whose guarantee has not expired.
     */
    public function scopeTrusted(Builder $query): Builder
    {
        return $query->where('status', 1)
            ->whereNull('suspended_at')
            ->notExpired();
    }

    /**
     * Same rule as ! isExpired(): no expiry date, or one that has not started yet (a partner expiring today is expired).
     */
    public function scopeNotExpired(Builder $query): Builder
    {
        return $query->where(function (Builder $query) {
            $query->whereNull('expired_at')->orWhere('expired_at', '>', now());
        });
    }

    public function isSuspended(): bool
    {
        return $this->suspended_at !== null;
    }

    /**
     * Same rule as scopeTrusted, for a loaded model.
     */
    public function isTrusted(): bool
    {
        return $this->isActive() && ! $this->isSuspended() && ! $this->isExpired();
    }

    public function isExpired(): bool
    {
        return $this->expired_at && $this->expired_at->isPast();
    }

    public function isExpiringSoon(int $days = 7): bool
    {
        return $this->expired_at
            && ! $this->expired_at->isPast()
            && $this->expired_at->diffInDays(now()) <= $days;
    }

    public function getStatusLabelAttribute(): string
    {
        if ($this->isSuspended()) {
            return 'Tạm đình chỉ';
        }

        if (! $this->isActive()) {
            return 'Tạm dừng';
        }

        if ($this->isExpired()) {
            return 'Đã hết hạn';
        }

        if ($this->isExpiringSoon()) {
            return 'Sắp hết hạn';
        }

        return 'Hoạt động';
    }

    /**
     * Status for the public profile: never includes the internal suspension reason.
     */
    public function publicStatusLabel(): string
    {
        return match (true) {
            $this->isTrusted() => 'Giao dịch viên đã xác thực',
            $this->isSuspended() => 'Tạm đình chỉ',
            ! $this->isActive() => 'Tạm dừng',
            default => 'Hết hạn',
        };
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->fit(Fit::Contain, 200, 200)
            ->nonQueued();

        $this->addMediaConversion('optimized')
            ->fit(Fit::Max, 800, 800)
            ->nonQueued();
    }

    public function getAvatarUrlAttribute(): ?string
    {
        if ($this->hasMedia('avatar')) {
            return $this->getFirstMediaUrl('avatar', 'thumb');
        }

        if (! $this->avatar) {
            return null;
        }

        if (filter_var($this->avatar, FILTER_VALIDATE_URL)) {
            return $this->avatar;
        }

        return asset('uploads/'.$this->avatar);
    }
}
