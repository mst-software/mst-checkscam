<?php

namespace App\Services;

use App\Helpers\StringHelper;
use App\Models\Insurance;
use App\Models\InsuranceChannel;
use App\Models\Report;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Single source of truth for "is this phone / bank account / Facebook / name a scammer?".
 * Used by the public search page, the public API and the Telegram bot.
 */
class ScamLookupService
{
    public const RISK_LOW = 'low';

    public const RISK_MEDIUM = 'medium';

    public const RISK_HIGH = 'high';

    /** Reports newer than this many days count as "recent" when scoring risk. */
    public const RECENT_DAYS = 90;

    /** A single report older than this many days is treated as low risk. */
    public const STALE_DAYS = 365;

    /**
     * Approved reports matching the query, newest first. Callers add eager loads / pagination.
     *
     * @return Builder<Report>
     */
    public function reportsQuery(string $query): Builder
    {
        [$type, $formattedQuery] = StringHelper::detectQueryType($query);
        $normalizedQuery = StringHelper::normalizeString($formattedQuery);

        return Report::where('status', 'approved')
            ->when(in_array($type, ['phone', 'bank']), function ($q) use ($formattedQuery) {
                $q->whereRaw("REGEXP_REPLACE(target_id, '[^0-9]', '') = ?", [$formattedQuery]);
            })
            ->when($type == 'facebook', function ($q) use ($formattedQuery, $query) {
                $q->where(function ($sub) use ($formattedQuery, $query) {
                    $sub->where('target_id', 'LIKE', "%{$formattedQuery}%")
                        ->orWhere('slug', 'LIKE', "%{$formattedQuery}%")
                        ->orWhere('target_name', 'LIKE', "%{$query}%");
                });
            })
            ->when($type == 'uuid', function ($q) use ($formattedQuery) {
                $q->where('slug', $formattedQuery);
            })
            ->when($type == 'name', function ($q) use ($normalizedQuery) {
                $q->whereRaw(
                    "LOWER(TRIM(REGEXP_REPLACE(target_name, '\\\\s+', ' '))) = ?",
                    [$normalizedQuery],
                );
            })
            ->orderByDesc('created_at');
    }

    /**
     * Active insurance partners matching the query by name, slug or an official channel, best match first.
     *
     * @return Collection<int, Insurance>
     */
    public function matchingInsurances(string $query): Collection
    {
        [, $formattedQuery] = StringHelper::detectQueryType($query);
        $normalizedQuery = StringHelper::normalizeString($formattedQuery);
        $channelInsuranceIds = InsuranceChannel::query()
            ->official()
            ->matchingInput($query)
            ->pluck('insurance_id')
            ->unique()
            ->all();

        return Insurance::where('status', 1)
            ->where(function ($q) use ($query, $formattedQuery, $normalizedQuery, $channelInsuranceIds) {
                $q->where('full_name', 'LIKE', "%{$query}%")
                    ->orWhereRaw("LOWER(TRIM(REGEXP_REPLACE(full_name, '\\\\s+', ' '))) LIKE ?", ["%{$normalizedQuery}%"])
                    ->orWhereIn('id', $channelInsuranceIds)
                    ->orWhere('slug', 'LIKE', "%{$formattedQuery}%");
            })
            ->get()
            ->sortByDesc(function ($insurance) use ($normalizedQuery, $query, $channelInsuranceIds) {
                $normalizedName = StringHelper::normalizeString($insurance->full_name);
                if ($normalizedName === $normalizedQuery || strcasecmp($insurance->full_name, $query) === 0) {
                    return 3;
                }

                if (in_array($insurance->id, $channelInsuranceIds)) {
                    return 2;
                }

                return 1;
            })
            ->values();
    }

    /**
     * Read-only lookup for machine clients (API, bot). Does not touch search counters or logs.
     */
    public function lookup(string $query, int $reportLimit = 10): ScamLookupResult
    {
        $query = trim($query);
        [$type, $formattedQuery] = StringHelper::detectQueryType($query);

        $reportsQuery = $this->reportsQuery($query);
        $totalReports = (clone $reportsQuery)->count();
        $reports = $reportsQuery->withCount('comments')->limit($reportLimit)->get();

        return new ScamLookupResult(
            type: $type,
            formattedQuery: $formattedQuery,
            reports: $reports,
            totalReports: $totalReports,
            risk: $this->riskLevel($totalReports, $reports->first()?->created_at),
            insurances: $this->matchingInsurances($query),
            verification: (new IntermediaryVerifier($this))->verify($query, $totalReports),
        );
    }

    /**
     * low: no reports, or a single report older than a year.
     * high: 3+ reports, or 2+ reports with the latest in the last 90 days.
     * medium: everything in between.
     */
    public function riskLevel(int $approvedReports, ?CarbonInterface $latestReportAt): string
    {
        if ($approvedReports === 0) {
            return self::RISK_LOW;
        }

        $daysSinceLatest = $latestReportAt ? $latestReportAt->diffInDays(null, true) : PHP_INT_MAX;

        if ($approvedReports >= 3 || ($approvedReports >= 2 && $daysSinceLatest <= self::RECENT_DAYS)) {
            return self::RISK_HIGH;
        }

        if ($approvedReports === 1 && $daysSinceLatest > self::STALE_DAYS) {
            return self::RISK_LOW;
        }

        return self::RISK_MEDIUM;
    }
}
