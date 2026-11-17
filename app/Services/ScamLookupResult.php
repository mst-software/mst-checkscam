<?php

namespace App\Services;

use App\Helpers\StringHelper;
use App\Models\Insurance;
use App\Models\Report;
use Illuminate\Support\Collection;

final class ScamLookupResult
{
    /**
     * @param  Collection<int, Report>  $reports  Newest approved reports (limited), with comments_count.
     * @param  Collection<int, Insurance>  $insurances
     */
    public function __construct(
        public readonly string $type,
        public readonly string $formattedQuery,
        public readonly Collection $reports,
        public readonly int $totalReports,
        public readonly string $risk,
        public readonly Collection $insurances,
        public readonly ?IntermediaryVerification $verification = null,
    ) {}

    /**
     * Whether the query is an official channel of a currently trusted intermediary.
     */
    public function isVerifiedIntermediary(): bool
    {
        return $this->verification?->isVerified() ?? false;
    }

    /**
     * Public, PII-safe representation: masked names only, no reporter data, no raw target ids.
     *
     * @return array<string, mixed>
     */
    public function toPublicArray(): array
    {
        return [
            'query' => [
                'type' => $this->type,
                'value' => $this->formattedQuery,
            ],
            'risk' => $this->risk,
            'total_reports' => $this->totalReports,
            'reports' => $this->reports->map(fn (Report $report) => [
                'slug' => $report->slug,
                'url' => $report->slug ? route('scammer.show', $report->slug) : null,
                'target_name_masked' => StringHelper::mask_name($report->target_name),
                'type' => $report->type,
                'reported_at' => $report->created_at?->toIso8601String(),
                'comments_count' => (int) $report->comments_count,
            ])->values()->all(),
            'insurance' => $this->insurances->map(fn (Insurance $insurance) => [
                'number' => $insurance->gdv_number,
                'code' => $insurance->memberCode(),
                'name' => $insurance->full_name,
                'slug' => $insurance->slug,
                'url' => $insurance->slug ? route('insurances.frontend.show', $insurance->slug) : null,
                'status' => $insurance->status_label,
                'expired_at' => $insurance->expired_at?->toDateString(),
            ])->values()->all(),
            'verified_intermediary' => $this->isVerifiedIntermediary(),
        ];
    }
}
