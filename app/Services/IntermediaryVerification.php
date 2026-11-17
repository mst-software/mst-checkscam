<?php

namespace App\Services;

use App\Enums\ChannelType;
use App\Enums\IntermediaryVerificationState;
use App\Models\Insurance;
use App\Models\InsuranceChannel;

/**
 * Result of IntermediaryVerifier::verify(). Wording never calls anything "safe": a verified channel
 * only means it belongs to a listed partner, and an unlisted one is not necessarily a scam.
 */
final readonly class IntermediaryVerification
{
    public const LAPSED_SUSPENDED = 'suspended';

    public const LAPSED_INACTIVE = 'inactive';

    public const LAPSED_EXPIRED = 'expired';

    /**
     * @param  string|null  $lapsedReason  One of the LAPSED_* constants when state is Lapsed.
     * @param  int  $reportedCount  Approved scam reports matching the same input.
     */
    public function __construct(
        public IntermediaryVerificationState $state,
        public string $input,
        public ?Insurance $insurance,
        public ?InsuranceChannel $channel,
        public ?string $lapsedReason,
        public int $reportedCount,
    ) {}

    public function isVerified(): bool
    {
        return $this->state === IntermediaryVerificationState::Verified;
    }

    public function isLapsed(): bool
    {
        return $this->state === IntermediaryVerificationState::Lapsed;
    }

    public function channelType(): ?ChannelType
    {
        return $this->channel?->type;
    }

    /**
     * One Vietnamese sentence describing the result, ready to display.
     */
    public function message(): string
    {
        $name = $this->insurance?->full_name;
        $code = $this->insurance?->memberCode();

        // Without a channel the partner was found by its GDV number.
        if ($this->insurance && ! $this->channel) {
            return $this->isVerified()
                ? "{$code} là {$name}, đối tác bảo hiểm đang hoạt động trên Scamproof."
                : "{$code} là {$name} nhưng đối tác hiện {$this->lapsedReasonLabel()}. Đây không phải GDV đang hoạt động của Scamproof.";
        }

        return match ($this->state) {
            IntermediaryVerificationState::Verified => "Đây là kênh chính thức của {$code} {$name}, đối tác bảo hiểm đang hoạt động trên Scamproof.",
            IntermediaryVerificationState::Lapsed => "Kênh này thuộc {$code} {$name} nhưng đối tác hiện {$this->lapsedReasonLabel()}. Đây không phải kênh GDV chính thức đang hoạt động của Scamproof.",
            IntermediaryVerificationState::NotListed => 'Không phải kênh GDV chính thức của Scamproof.',
        };
    }

    /**
     * Warning about approved scam reports on the same identifier, or null when there are none.
     */
    public function reportsWarning(): ?string
    {
        return $this->reportedCount > 0
            ? "Có {$this->reportedCount} tố cáo đã duyệt liên quan đến thông tin này."
            : null;
    }

    public function lapsedReasonLabel(): ?string
    {
        return match ($this->lapsedReason) {
            self::LAPSED_SUSPENDED => 'đang bị tạm đình chỉ',
            self::LAPSED_INACTIVE => 'đã ngừng hoạt động',
            self::LAPSED_EXPIRED => 'đã hết hạn bảo hiểm',
            default => null,
        };
    }

    /**
     * Public partner card data, or null when nothing matched.
     *
     * @return array{id: int, number: int, code: string, name: string, slug: ?string, url: ?string, status: string, balance: string, expired_at: ?string, verified_at: ?string}|null
     */
    public function partnerSummary(): ?array
    {
        if (! $this->insurance) {
            return null;
        }

        return [
            'id' => $this->insurance->id,
            'number' => $this->insurance->gdv_number,
            'code' => $this->insurance->memberCode(),
            'name' => $this->insurance->full_name,
            'slug' => $this->insurance->slug,
            'url' => $this->insurance->slug ? route('insurances.frontend.show', $this->insurance->slug) : null,
            'status' => $this->insurance->status_label,
            'balance' => (string) $this->insurance->amount,
            'expired_at' => $this->insurance->expired_at?->toDateString(),
            'verified_at' => $this->insurance->verified_at?->toDateString(),
        ];
    }

    /**
     * PII-free representation for JSON/API consumers.
     *
     * @return array<string, mixed>
     */
    public function toPublicArray(): array
    {
        return [
            'state' => $this->state->value,
            'label' => $this->state->label(),
            'message' => $this->message(),
            'channel_type' => $this->channelType()?->value,
            'lapsed_reason' => $this->lapsedReason,
            'reported_count' => $this->reportedCount,
            'partner' => $this->partnerSummary(),
        ];
    }
}
