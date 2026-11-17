<?php

namespace App\Http\Controllers;

use App\Helpers\StatsHelper;
use App\Models\Comment;
use App\Models\Insurance;
use App\Models\Report;
use App\Models\SearchLog;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

class HomeController extends Controller
{
    private const FIRST_COMMENT_PAGE_SIZE = 8;

    private const NEXT_COMMENT_PAGE_SIZE = 16;

    public function index(Request $request): View|string
    {
        $comments = $this->getComments($request);

        if ($request->ajax()) {
            return view('partials.comment-items', compact('comments'))->render();
        }

        $stats = $this->getHomeStats();
        $latestReports = $this->getLatestReports();
        $latestApprovedAt = $this->getLatestApprovedAt();
        $topWeeklySearches = StatsHelper::getTopWeeklySearches();
        $trustedInsurances = $this->getTrustedInsurances();

        $recentSearches = SearchLog::where('ip_address', $request->ip())
            ->orderByDesc('created_at')
            ->limit(5)
            ->pluck('search_query')
            ->unique()
            ->values();

        return view('home', compact(
            'stats',
            'latestReports',
            'latestApprovedAt',
            'topWeeklySearches',
            'trustedInsurances',
            'recentSearches',
            'comments',
        ));
    }

    /**
     * First page shows 8 comments, every following "load more" page 16.
     */
    private function getComments(Request $request): LengthAwarePaginator
    {
        $page = max(1, (int) $request->input('page', 1));
        $perPage = $page === 1 ? self::FIRST_COMMENT_PAGE_SIZE : self::NEXT_COMMENT_PAGE_SIZE;
        $offset = $page === 1 ? 0 : self::FIRST_COMMENT_PAGE_SIZE + ($page - 2) * self::NEXT_COMMENT_PAGE_SIZE;

        $query = Comment::whereHas('report', function ($query) {
            $query->where('status', 'approved');
        })->with('report')->latest();

        $totalCount = $query->count();
        $items = $query->skip($offset)->take($perPage)->get();

        return new LengthAwarePaginator(
            $items,
            $totalCount,
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()],
        );
    }

    /**
     * @return array{total_reports: int}
     */
    private function getHomeStats(): array
    {
        return [
            'total_reports' => Report::where('status', 'approved')->count(),
        ];
    }

    /**
     * Newest approvals first; evidence counts come from the loaded evidence_images column.
     */
    private function getLatestReports(): Collection
    {
        return Report::query()
            ->approved()
            ->select(['id', 'slug', 'type', 'target_id', 'target_name', 'category', 'scam_type', 'evidence_images', 'approved_at', 'created_at'])
            ->orderByDesc('approved_at')
            ->latest()
            ->limit(7)
            ->get();
    }

    /**
     * Time of the most recent approval, shown as the data freshness line.
     */
    private function getLatestApprovedAt(): ?Carbon
    {
        $latest = Report::query()->approved()->max('approved_at');

        return $latest ? Carbon::parse($latest) : null;
    }

    private function getTrustedInsurances(): Collection
    {
        return Insurance::query()
            ->trusted()
            ->with('media')
            ->orderByDesc('amount')
            ->limit(3)
            ->get();
    }
}
