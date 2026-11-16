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

    private function getLatestReports(): Collection
    {
        return Report::where('status', 'approved')
            ->withCount('comments')
            ->latest()
            ->limit(6)
            ->get();
    }

    private function getTrustedInsurances(): Collection
    {
        return Insurance::query()
            ->trusted()
            ->with('media')
            ->orderByDesc('amount')
            ->limit(6)
            ->get();
    }
}
