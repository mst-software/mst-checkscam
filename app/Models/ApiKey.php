<?php

namespace App\Models;

use Database\Factories\ApiKeyFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ApiKey extends Model
{
    /** @use HasFactory<ApiKeyFactory> */
    use HasFactory;

    public const PLAINTEXT_PREFIX = 'mst_';

    protected $fillable = [
        'user_id',
        'name',
        'key_hash',
        'key_prefix',
        'rate_limit_per_minute',
        'last_used_at',
        'is_active',
    ];

    protected $hidden = [
        'key_hash',
    ];

    protected function casts(): array
    {
        return [
            'rate_limit_per_minute' => 'integer',
            'last_used_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public static function hashKey(string $plainKey): string
    {
        return hash('sha256', $plainKey);
    }

    public static function generatePlainKey(): string
    {
        return self::PLAINTEXT_PREFIX.Str::random(40);
    }

    /**
     * Create a key and return the plaintext once; only its SHA-256 hash is stored.
     *
     * @return array{0: self, 1: string}
     */
    public static function issue(string $name, ?int $userId = null, int $rateLimitPerMinute = 30): array
    {
        $plainKey = self::generatePlainKey();

        $apiKey = self::create([
            'user_id' => $userId,
            'name' => $name,
            'key_hash' => self::hashKey($plainKey),
            'key_prefix' => substr($plainKey, 0, 12),
            'rate_limit_per_minute' => $rateLimitPerMinute,
            'is_active' => true,
        ]);

        return [$apiKey, $plainKey];
    }

    public static function findActiveByPlainKey(string $plainKey): ?self
    {
        return self::query()->active()->where('key_hash', self::hashKey($plainKey))->first();
    }
}
