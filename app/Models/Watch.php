<?php

namespace App\Models;

use App\Helpers\StringHelper;
use Database\Factories\WatchFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Watch extends Model
{
    /** @use HasFactory<WatchFactory> */
    use HasFactory;

    public const MAX_PER_USER = 20;

    public const WATCHABLE_TYPES = ['phone', 'bank', 'facebook'];

    protected $fillable = [
        'user_id',
        'type',
        'value',
        'last_notified_at',
    ];

    protected function casts(): array
    {
        return [
            'last_notified_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Normalize a raw lookup value with the same rules as search.
     *
     * @return array{0: string, 1: string}|null [type, value], or null when the value cannot be watched
     */
    public static function normalizeTarget(?string $raw): ?array
    {
        $raw = trim((string) $raw);

        if ($raw === '') {
            return null;
        }

        [$type, $value] = StringHelper::detectQueryType($raw);

        if (! in_array($type, self::WATCHABLE_TYPES, true)) {
            return null;
        }

        if ($type === 'facebook') {
            $value = StringHelper::normalizeString($value);
        }

        return [$type, $value];
    }

    public function scopeMatchingTarget(Builder $query, string $type, string $value): Builder
    {
        return $query->where('type', $type)->where('value', $value);
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            'phone' => 'Số điện thoại',
            'bank' => 'Số tài khoản',
            'facebook' => 'Facebook',
            default => 'Khác',
        };
    }
}
