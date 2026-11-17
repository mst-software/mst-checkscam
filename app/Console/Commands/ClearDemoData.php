<?php

namespace App\Console\Commands;

use App\Helpers\FileHelper;
use App\Models\Complaint;
use App\Models\Insurance;
use App\Models\InsuranceLedgerEntry;
use App\Models\InsuranceStatusEvent;
use App\Models\NewfeedPost;
use App\Models\Post;
use App\Models\Report;
use App\Models\SearchLog;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Removes exactly the rows written by Database\Seeders\DemoDataSeeder, and their uploaded files.
 *
 * Demo rows are recognised by their markers: users with an @demo.scamproof.test email, reports,
 * comments, complaints and search logs from the 192.0.2.x TEST-NET range, newfeed posts and blog
 * posts owned by demo users, and insurance partners whose ledger was recorded by a demo user.
 */
class ClearDemoData extends Command
{
    public const EMAIL_DOMAIN = 'demo.scamproof.test';

    /** RFC 5737 TEST-NET-1: never a real visitor address. */
    public const IP_PREFIX = '192.0.2.';

    protected $signature = 'demo:clear';

    protected $description = 'Remove the demo data written by DemoDataSeeder and its uploaded images';

    public function handle(): int
    {
        $userIds = self::demoUsers()->pluck('id');
        $reports = self::demoReports()->get(['id', 'evidence_images']);
        $complaints = Complaint::query()->where('ip_address', 'like', self::IP_PREFIX.'%')->get(['id', 'evidence_images']);
        $newfeedPosts = NewfeedPost::withTrashed()->whereIn('user_id', $userIds)->get(['id', 'image_path', 'image_paths']);
        $posts = Post::query()->whereIn('author_id', $userIds)->get();
        $insurances = Insurance::query()
            ->whereIn('id', InsuranceLedgerEntry::query()->whereIn('recorded_by', $userIds)->select('insurance_id'))
            ->get();

        $uploads = $reports->pluck('evidence_images')
            ->merge($complaints->pluck('evidence_images'))
            ->flatten()
            ->merge($posts->pluck('thumbnail'))
            ->merge($insurances->pluck('avatar'))
            ->filter(fn ($path): bool => is_string($path) && $path !== '' && ! filter_var($path, FILTER_VALIDATE_URL))
            ->unique()
            ->values();
        $newfeedImages = $newfeedPosts->pluck('image_paths')->flatten()->merge($newfeedPosts->pluck('image_path'))->filter()->unique()->values();

        $counts = DB::transaction(function () use ($userIds, $reports, $complaints, $newfeedPosts, $posts, $insurances) {
            $insuranceIds = $insurances->pluck('id');

            // History tables restrict deletes and the ledger model refuses them, so remove rows with the query builder first.
            InsuranceStatusEvent::query()->whereIn('insurance_id', $insuranceIds)->delete();
            InsuranceLedgerEntry::query()->whereIn('insurance_id', $insuranceIds)->delete();
            $insurances->each(fn (Insurance $insurance) => $insurance->delete());
            $posts->each(fn (Post $post) => $post->delete());

            return [
                'complaints' => Complaint::query()->whereKey($complaints->pluck('id'))->delete(),
                'search_logs' => SearchLog::query()->where('ip_address', 'like', self::IP_PREFIX.'%')->delete(),
                'reports' => Report::query()->whereKey($reports->pluck('id'))->delete(),
                'newfeed_posts' => NewfeedPost::withTrashed()->whereKey($newfeedPosts->pluck('id'))->forceDelete(),
                'posts' => $posts->count(),
                'insurances' => $insurances->count(),
                'users' => User::query()->whereKey($userIds)->delete(),
            ];
        });

        FileHelper::deleteMultipleImages($uploads->all());
        $newfeedImages->each(fn (string $path) => Storage::disk('public')->delete($path));

        foreach ($counts as $table => $count) {
            $this->line("{$table}: {$count}");
        }
        $this->info('Đã xoá dữ liệu demo và '.($uploads->count() + $newfeedImages->count()).' ảnh.');

        return self::SUCCESS;
    }

    public static function demoUsers(): Builder
    {
        return User::query()->where('email', 'like', '%@'.self::EMAIL_DOMAIN);
    }

    public static function demoReports(): Builder
    {
        return Report::query()->where('ip_address', 'like', self::IP_PREFIX.'%');
    }
}
