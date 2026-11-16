<?php

namespace App\Models;

use App\Enums\ChannelType;
use App\Helpers\StringHelper;
use Database\Factories\InsuranceChannelFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A contact or payment channel (phone, Facebook, Telegram, Zalo, bank account, website) of an
 * insurance partner. `normalized_value` is what lookups compare against; `value` is shown as entered.
 */
class InsuranceChannel extends Model
{
    /** @use HasFactory<InsuranceChannelFactory> */
    use HasFactory;

    protected $fillable = [
        'insurance_id',
        'type',
        'value',
        'normalized_value',
        'is_official',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => ChannelType::class,
            'is_official' => 'boolean',
            'verified_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // Keep the lookup key in sync with what admins type.
        static::saving(function (InsuranceChannel $channel) {
            if ($channel->type instanceof ChannelType && is_string($channel->value)) {
                $channel->normalized_value = self::normalize($channel->type, $channel->value);
            }
        });
    }

    public function insurance(): BelongsTo
    {
        return $this->belongsTo(Insurance::class);
    }

    public function scopeOfficial(Builder $query): Builder
    {
        return $query->where('is_official', true);
    }

    /**
     * Channels whose type and normalized value match free-form user input
     * (phone, bank account, profile URL, @handle, ...). Matches nothing for unrecognised input.
     */
    public function scopeMatchingInput(Builder $query, string $input): Builder
    {
        [$types, $value] = StringHelper::detectChannelQuery($input);

        if ($types === [] || $value === '') {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn('type', array_map(fn (ChannelType $type) => $type->value, $types))
            ->where('normalized_value', $value);
    }

    /**
     * Canonical lookup key for a channel value:
     * phone -> 0xxxxxxxxx digits, bank account -> lowercase alphanumerics, Facebook -> lowercase
     * username/UID, Telegram/Zalo -> lowercase handle without @ or host (Zalo phone numbers as phones),
     * website -> lowercase host + path without scheme, www, query or trailing slash.
     */
    public static function normalize(ChannelType $type, string $value): string
    {
        $value = trim($value);

        return match ($type) {
            ChannelType::Phone => self::normalizePhone($value),
            ChannelType::BankAccount => strtolower((string) preg_replace('/[^0-9A-Za-z]/', '', $value)),
            ChannelType::Facebook => self::normalizeFacebook($value),
            ChannelType::Telegram => self::normalizeHandle($value, '(?:t|telegram)\.(?:me|dog)'),
            ChannelType::Zalo => self::normalizeZalo($value),
            ChannelType::Website => self::normalizeWebsite($value),
        };
    }

    /**
     * Turn the legacy free-form JSON on `insurances` into channel attributes.
     * contact_info: [{platform, link}], payment_accounts: [{bank, number, name}]. Unknown shapes are skipped.
     *
     * @return list<array{type: ChannelType, value: string, normalized_value: string}>
     */
    public static function fromLegacyJson(mixed $contactInfo, mixed $paymentAccounts): array
    {
        $channels = [];

        foreach (is_array($contactInfo) ? $contactInfo : [] as $contact) {
            $link = is_array($contact) && is_string($contact['link'] ?? null) ? trim($contact['link']) : '';
            if ($link === '') {
                continue;
            }

            $platform = is_string($contact['platform'] ?? null) ? $contact['platform'] : '';
            $type = self::guessContactType($platform, $link);
            if ($type) {
                $channels[] = ['type' => $type, 'value' => $link];
            }
        }

        foreach (is_array($paymentAccounts) ? $paymentAccounts : [] as $account) {
            $number = is_array($account) && is_scalar($account['number'] ?? null) ? trim((string) $account['number']) : '';
            if ($number !== '') {
                $channels[] = ['type' => ChannelType::BankAccount, 'value' => $number];
            }
        }

        $unique = [];
        foreach ($channels as $channel) {
            $normalized = self::normalize($channel['type'], $channel['value']);
            if ($normalized !== '') {
                $unique[$channel['type']->value.'|'.$normalized] = $channel + ['normalized_value' => $normalized];
            }
        }

        return array_values($unique);
    }

    private static function guessContactType(string $platform, string $link): ?ChannelType
    {
        $platform = mb_strtolower(trim($platform));
        $lowerLink = mb_strtolower($link);

        return match (true) {
            str_contains($platform, 'facebook') || $platform === 'fb' || preg_match('~(^|[/.])(facebook|fb)\.com/~', $lowerLink) === 1 => ChannelType::Facebook,
            str_contains($platform, 'telegram') || $platform === 'tele' || preg_match('~(^|[/.])(t|telegram)\.(me|dog)/~', $lowerLink) === 1 => ChannelType::Telegram,
            str_contains($platform, 'zalo') || str_contains($lowerLink, 'zalo.me/') => ChannelType::Zalo,
            str_contains($platform, 'web') || preg_match('~^https?://~', $lowerLink) === 1 => ChannelType::Website,
            str_contains($platform, 'điện thoại') || str_contains($platform, 'sđt') || str_contains($platform, 'phone')
                || str_contains($platform, 'hotline') || StringHelper::detectQueryType($link)[0] === 'phone' => ChannelType::Phone,
            default => null,
        };
    }

    private static function normalizePhone(string $value): string
    {
        [$type, $formatted] = StringHelper::detectQueryType($value);

        return in_array($type, ['phone', 'bank'], true) ? $formatted : (string) preg_replace('/\D/', '', $value);
    }

    private static function normalizeFacebook(string $value): string
    {
        [$type, $formatted] = StringHelper::detectQueryType($value);
        $handle = $type === 'facebook' ? $formatted : $value;

        return mb_strtolower(trim(ltrim($handle, '@'), '/ '));
    }

    private static function normalizeZalo(string $value): string
    {
        $handle = (string) preg_replace('~^(?:https?://)?(?:www\.|chat\.)?zalo\.me/~i', '', $value);
        [$type, $formatted] = StringHelper::detectQueryType(trim($handle, '/ '));

        if ($type === 'phone') {
            return $formatted;
        }

        $handle = self::normalizeHandle($handle, '(?:chat\.)?zalo\.me');
        [$type, $formatted] = StringHelper::detectQueryType($handle);

        return $type === 'phone' ? $formatted : $handle;
    }

    /**
     * First path segment after the given host pattern, or the bare value, without @ and lowercased.
     */
    private static function normalizeHandle(string $value, string $hostPattern): string
    {
        $value = (string) preg_replace('~^(?:https?://)?(?:www\.)?'.$hostPattern.'/~i', '', $value);
        $value = (string) preg_split('~[/?#\s]~', ltrim($value, '@'))[0];

        return mb_strtolower($value);
    }

    private static function normalizeWebsite(string $value): string
    {
        $value = mb_strtolower($value);
        $value = (string) preg_replace('~^[a-z][a-z0-9+.-]*://~', '', $value);
        $value = (string) preg_replace('~^www\.~', '', $value);
        $value = (string) preg_split('~[?#]~', $value)[0];

        return rtrim($value, '/');
    }
}
