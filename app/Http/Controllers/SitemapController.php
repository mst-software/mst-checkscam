<?php

namespace App\Http\Controllers;

use App\Models\Insurance;
use App\Models\Post;
use App\Models\Report;
use DateTimeInterface;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

class SitemapController extends Controller
{
    public const CACHE_KEY = 'sitemap.xml';

    private const CACHE_SECONDS = 3600;

    /** Most recent approved reports listed; a single sitemap file holds at most 50,000 URLs. */
    private const MAX_REPORTS = 5000;

    /** Static public pages: [path, priority, change frequency]. */
    private const STATIC_PAGES = [
        ['/', 1.0, Url::CHANGE_FREQUENCY_DAILY],
        ['/warnings', 0.9, Url::CHANGE_FREQUENCY_DAILY],
        ['/bai-viet', 0.8, Url::CHANGE_FREQUENCY_WEEKLY],
        ['/bao-hiem-cs', 0.8, Url::CHANGE_FREQUENCY_WEEKLY],
        ['/newfeed', 0.6, Url::CHANGE_FREQUENCY_DAILY],
        ['/to-cao-lua-dao', 0.6, Url::CHANGE_FREQUENCY_MONTHLY],
        ['/huong-dan-to-cao', 0.5, Url::CHANGE_FREQUENCY_MONTHLY],
        ['/giai-quyet-khieu-nai', 0.4, Url::CHANGE_FREQUENCY_MONTHLY],
        ['/api-checkscam', 0.4, Url::CHANGE_FREQUENCY_MONTHLY],
        ['/doi-tac-uy-tin', 0.4, Url::CHANGE_FREQUENCY_MONTHLY],
        ['/lien-he-admin', 0.3, Url::CHANGE_FREQUENCY_YEARLY],
        ['/dieu-khoan', 0.3, Url::CHANGE_FREQUENCY_YEARLY],
        ['/moderation-policy', 0.4, Url::CHANGE_FREQUENCY_YEARLY],
    ];

    public function index(): Response
    {
        $xml = Cache::remember(self::CACHE_KEY, self::CACHE_SECONDS, fn (): string => $this->build()->render());

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function robots(): Response
    {
        $lines = [
            'User-agent: *',
            'Disallow: /admin',
            'Disallow: /search',
            'Disallow: /watchlist',
            'Disallow: /api/',
            '',
            'Sitemap: '.url('/sitemap.xml'),
        ];

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    private function build(): Sitemap
    {
        $sitemap = Sitemap::create();

        foreach (self::STATIC_PAGES as [$path, $priority, $frequency]) {
            $this->addUrl($sitemap, url($path), null, $priority, $frequency);
        }

        Post::query()->select(['id', 'slug', 'updated_at'])->chunkById(500, function ($posts) use ($sitemap) {
            foreach ($posts as $post) {
                $this->addUrl($sitemap, route('posts.frontend.show', $post->slug), $post->updated_at, 0.7, Url::CHANGE_FREQUENCY_MONTHLY);
            }
        });

        Insurance::query()->where('status', 1)->select(['id', 'slug', 'updated_at'])->chunkById(500, function ($insurances) use ($sitemap) {
            foreach ($insurances as $insurance) {
                $this->addUrl($sitemap, route('insurances.frontend.show', $insurance->slug), $insurance->updated_at, 0.7, Url::CHANGE_FREQUENCY_WEEKLY);
            }
        });

        // Only the public slug is used; target ids and names never reach the sitemap.
        Report::query()
            ->approved()
            ->select(['id', 'slug', 'updated_at'])
            ->latest('id')
            ->limit(self::MAX_REPORTS)
            ->get()
            ->each(function (Report $report) use ($sitemap) {
                $this->addUrl($sitemap, route('scammer.show', $report->slug), $report->updated_at, 0.6, Url::CHANGE_FREQUENCY_WEEKLY);
            });

        return $sitemap;
    }

    private function addUrl(Sitemap $sitemap, string $url, ?DateTimeInterface $lastModified, float $priority, string $frequency): void
    {
        $tag = Url::create($url)->setPriority($priority)->setChangeFrequency($frequency);

        if ($lastModified) {
            $tag->setLastModificationDate($lastModified);
        }

        $sitemap->add($tag);
    }
}
