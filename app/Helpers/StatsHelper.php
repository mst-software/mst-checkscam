<?php

namespace App\Helpers;

use App\Models\Report;
use App\Models\SearchLog;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StatsHelper
{
    public static function getTopDailySearches()
    {
        $topSearches = SearchLog::whereDate('created_at', today())
            ->select('search_query', DB::raw('COUNT(*) as count'))
            ->groupBy('search_query')
            ->orderByDesc('count')
            ->limit(3)
            ->get();

        if ($topSearches->isEmpty()) {
            return collect();
        }

        $reports = Report::whereIn('target_id', $topSearches->pluck('search_query'))
            ->where('status', 'approved')
            ->get()
            ->keyBy('target_id');

        return $topSearches->map(function ($search) use ($reports) {
            $scamInfo = $reports->get($search->search_query);

            return (object) [
                'is_scam' => (bool) $scamInfo,
                'target_id' => $search->search_query,
                'target_name' => $scamInfo->target_name ?? 'Chưa rõ thông tin',
                'type' => $scamInfo->type ?? 'Từ khóa hệ thống',
                'search_count' => $search->count,
                'view_count' => $search->count,
                'slug' => $scamInfo->slug ?? '#',
            ];
        });
    }

    /**
     * Approved report targets searched most often during the last 7 days.
     *
     * Logged queries are grouped by StringHelper::detectQueryType, so "0912345678", "+84912345678"
     * and "0912.345.678" count as one target. Only the 50 most frequent raw queries are considered,
     * which keeps both queries bounded.
     */
    public static function getTopWeeklySearches(int $limit = 5): Collection
    {
        $topSearches = SearchLog::where('created_at', '>=', now()->subDays(7))
            ->where('is_found', true)
            ->select('search_query', DB::raw('COUNT(*) as count'))
            ->groupBy('search_query')
            ->orderByDesc('count')
            ->limit(50)
            ->get();

        if ($topSearches->isEmpty()) {
            return collect();
        }

        $searchCounts = $topSearches
            ->groupBy(fn ($search) => self::targetKey($search->search_query))
            ->map(fn (Collection $searches) => (int) $searches->sum('count'))
            ->sortDesc();

        $numericKeys = $searchCounts->keys()
            ->filter(fn (string $key) => str_starts_with($key, 'digits:'))
            ->map(fn (string $key) => substr($key, strlen('digits:')))
            ->values();

        $reports = Report::where('status', 'approved')
            ->where(function ($query) use ($numericKeys, $topSearches) {
                $query->whereIn('target_id', $topSearches->pluck('search_query'));

                if ($numericKeys->isNotEmpty()) {
                    $query->orWhereIn(DB::raw("REGEXP_REPLACE(target_id, '[^0-9]', '')"), $numericKeys);
                }
            })
            ->select('target_id', 'target_name', 'type', 'slug', 'created_at')
            ->latest()
            ->get()
            ->unique(fn (Report $report) => self::targetKey($report->target_id))
            ->keyBy(fn (Report $report) => self::targetKey($report->target_id));

        return $searchCounts
            ->filter(fn (int $count, string $key) => $reports->has($key))
            ->take($limit)
            ->map(function (int $count, string $key) use ($reports) {
                $report = $reports->get($key);

                return (object) [
                    'target_id' => $report->target_id,
                    'target_name' => $report->target_name,
                    'type' => $report->type,
                    'slug' => $report->slug,
                    'search_count' => $count,
                ];
            })
            ->values();
    }

    /**
     * Grouping key for a searched value or report target: digits for phones and bank accounts,
     * the normalized text otherwise.
     */
    private static function targetKey(string $value): string
    {
        [$type, $formattedQuery] = StringHelper::detectQueryType(trim($value));

        if (in_array($type, ['phone', 'bank'], true)) {
            return 'digits:'.$formattedQuery;
        }

        return 'text:'.StringHelper::normalizeString($value);
    }

    public static function getTopWeeklyReports()
    {
        return Report::where('status', 'approved')
            ->where('created_at', '>=', now()->subDays(7))
            ->select(
                'target_id',
                DB::raw("SUBSTRING_INDEX(GROUP_CONCAT(slug ORDER BY created_at DESC SEPARATOR '|||'), '|||', 1) as slug"),
                DB::raw("SUBSTRING_INDEX(GROUP_CONCAT(target_name ORDER BY created_at DESC SEPARATOR '|||'), '|||', 1) as target_name"),
                DB::raw("SUBSTRING_INDEX(GROUP_CONCAT(type ORDER BY created_at DESC SEPARATOR '|||'), '|||', 1) as type"),
                DB::raw('MAX(created_at) as last_reported_at'),
                DB::raw('SUM(view_count) as total_views'),
                DB::raw('MAX(search_count) as total_searches'),
                DB::raw('(SELECT COUNT(*) FROM reports r2 WHERE r2.target_id = reports.target_id AND r2.status = "approved") as report_count'),
                DB::raw('(SUM(view_count) + MAX(search_count) + (COUNT(*) * 10)) as heat_index'),
            )
            ->groupBy('target_id')
            ->orderByDesc('heat_index')
            ->limit(7)
            ->get();
    }
}
