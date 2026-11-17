<?php

namespace Database\Seeders;

use App\Console\Commands\ClearDemoData;
use App\Enums\ComplaintReason;
use App\Enums\ComplaintStatus;
use App\Enums\LedgerEntryType;
use App\Enums\ScamType;
use App\Helpers\StringHelper;
use App\Models\Comment;
use App\Models\Complaint;
use App\Models\Insurance;
use App\Models\NewfeedPost;
use App\Models\NewfeedPostReport;
use App\Models\Post;
use App\Models\Report;
use App\Models\SearchLog;
use App\Models\User;
use App\Models\Watch;
use App\Services\InsuranceChannelService;
use App\Services\InsuranceLedgerService;
use App\Services\InsuranceSuspensionService;
use Carbon\CarbonInterface;
use Database\Seeders\Demo\DemoContent;
use Database\Seeders\Demo\DemoPhotos;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Throwable;

/**
 * Realistic demo content with real photos for the whole site. Run on a dev database only:
 * `php artisan db:seed --class=DemoDataSeeder`, remove with `php artisan demo:clear`.
 * Not called from DatabaseSeeder. Skips when demo data already exists.
 */
class DemoDataSeeder extends Seeder
{
    public const ADMIN_EMAIL = 'admin@'.ClearDemoData::EMAIL_DOMAIN;

    public const ADMIN_PASSWORD = 'demo123456';

    /** Reports per target for the repeat offenders; every other target gets one report. */
    private const REPEAT_COUNTS = [5, 4, 4, 4, 3, 3, 3, 3, 2, 2, 2, 2, 2, 2];

    private const SINGLE_TARGETS = 49;

    private const EVIDENCE_POOL = 100;

    /** Same as the POST_REPORT_THRESHOLD default in NewfeedPostReportController. */
    private const HIDE_THRESHOLD = 10;

    private DemoPhotos $photos;

    private User $editor;

    private int $evidenceCursor = 0;

    private bool $capUsed = false;

    public function run(): void
    {
        if (ClearDemoData::demoUsers()->exists() || ClearDemoData::demoReports()->exists()) {
            $this->command?->warn('Đã có dữ liệu demo. Chạy "php artisan demo:clear" trước nếu muốn seed lại.');

            return;
        }

        $startedAt = microtime(true);
        mt_srand(20261117);
        $this->photos = new DemoPhotos(fn (string $message) => $this->command?->warn($message));

        try {
            $counts = DB::transaction(fn (): array => $this->seedAll());
        } catch (Throwable $e) {
            $this->photos->deleteWritten();

            throw $e;
        } finally {
            mt_srand();
        }

        foreach ($counts as $table => $count) {
            $this->command?->line("{$table}: {$count}");
        }
        $this->command?->info(sprintf(
            'Xong sau %.1f giây: tải %d ảnh, tạo tại chỗ %d ảnh.',
            microtime(true) - $startedAt,
            $this->photos->downloaded,
            $this->photos->generated,
        ));
    }

    /**
     * @return array<string, int>
     */
    private function seedAll(): array
    {
        $admin = $this->seedAdminIfMissing();
        $this->editor = $this->createUser('demo.editor', 'Ban biên tập Scamproof', 'moderator');
        $sellers = collect(DemoContent::sellers())->map(fn (array $seller) => $this->createUser($seller['username'], $seller['name'], 'user', $seller['verified']));
        $buyers = collect(DemoContent::buyers())->map(fn (array $buyer) => $this->createUser($buyer['username'], $buyer['name'], 'user'));

        $newfeed = $this->seedNewfeed($sellers->all(), $buyers->all());
        $reports = $this->seedReports();
        $comments = $this->seedComments($reports);
        $insurances = $this->seedInsurances();
        $searchLogs = $this->seedSearchLogs($reports, $insurances);

        return [
            'users' => 1 + $sellers->count() + $buyers->count() + ($admin ? 1 : 0),
            'newfeed_posts' => $newfeed['posts'],
            'newfeed_post_reports' => $newfeed['reports'],
            'reports' => count($reports),
            'comments' => $comments,
            'complaints' => $this->seedComplaints($reports),
            'watches' => $this->seedWatches($reports, $buyers->all()),
            'search_logs' => $searchLogs,
            'posts' => $this->seedBlogPosts(),
            'insurances' => count($insurances),
        ];
    }

    private function seedAdminIfMissing(): ?User
    {
        if (User::query()->where('role', 'admin')->exists()) {
            return null;
        }

        $admin = User::forceCreate([
            'username' => 'demo.admin',
            'email' => self::ADMIN_EMAIL,
            'full_name' => 'Quản trị viên demo',
            'password' => Hash::make(self::ADMIN_PASSWORD),
            'role' => 'admin',
            'status' => 1,
            'email_verified_at' => now(),
        ]);
        $this->command?->warn('Chưa có admin nên đã tạo: '.self::ADMIN_EMAIL.' / '.self::ADMIN_PASSWORD);

        return $admin;
    }

    /**
     * Marketplace users look like Google sign-ins (role "user", google_id, no password).
     */
    private function createUser(string $username, string $name, string $role, bool $verified = false): User
    {
        return User::forceCreate([
            'username' => $username,
            'email' => $username.'@'.ClearDemoData::EMAIL_DOMAIN,
            'google_id' => $role === 'user' ? 'demo-'.crc32($username) : null,
            'full_name' => $name,
            // The editor only signs moderation and ledger rows; nobody knows this password.
            'password' => $role === 'user' ? null : Hash::make(Str::random(40)),
            'role' => $role,
            'status' => 1,
            'is_verified' => $verified ? 1 : 0,
            'email_verified_at' => now(),
            'created_at' => now()->subDays(mt_rand(40, 120)),
        ]);
    }

    /**
     * @param  list<User>  $sellers
     * @param  list<User>  $buyers
     * @return array{posts: int, reports: int}
     */
    private function seedNewfeed(array $sellers, array $buyers): array
    {
        $zalo = array_map(fn () => $this->phone(), $sellers);
        $everyone = array_merge($buyers, $sellers);
        $listings = DemoContent::listings();
        $reasons = DemoContent::postReportReasons();
        $reportCount = 0;

        foreach ($listings as $i => $listing) {
            $seller = $sellers[$listing['seller']];
            $createdAt = now()->subDays(29 - intdiv($i * 29, count($listings) - 1))->subMinutes(mt_rand(0, 600));
            $reports = $listing['reports'] ?? 0;

            $images = [];
            for ($p = 0; $p < $listing['photos']; $p++) {
                $images[] = $this->photos->newfeed("scamproof-nf-{$i}-{$p}", $listing['category']);
            }

            $post = NewfeedPost::forceCreate([
                'user_id' => $seller->id,
                'category' => $listing['category'],
                'content' => str_replace('{zalo}', $zalo[$listing['seller']], $listing['content']),
                'price' => $listing['price'],
                'image_paths' => $images ?: null,
                'report_count' => $reports,
                'is_hidden' => $reports >= self::HIDE_THRESHOLD ? 1 : 0,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            $reporters = array_values(array_filter($everyone, fn (User $user) => $user->id !== $seller->id));
            foreach (array_slice($reporters, 0, $reports) as $r => $reporter) {
                NewfeedPostReport::forceCreate([
                    'post_id' => $post->id,
                    'reporter_id' => $reporter->id,
                    'reason' => $reasons[$r % count($reasons)],
                    'created_at' => $this->notAfterNow($createdAt->copy()->addHours(mt_rand(1, 48))),
                ]);
                $reportCount++;
            }
        }

        return ['posts' => count($listings), 'reports' => $reportCount];
    }

    /**
     * About 90 reports over the last 45 days: repeat offenders first, then single reports.
     *
     * @return list<Report>
     */
    private function seedReports(): array
    {
        $counts = array_merge(self::REPEAT_COUNTS, array_fill(0, self::SINGLE_TARGETS, 1));
        $types = array_merge(array_fill(0, 39, 'account'), array_fill(0, 11, 'website'), array_fill(0, 13, 'back'));
        shuffle($types);

        $accountScams = [ScamType::FakeShop, ScamType::Investment, ScamType::Romance, ScamType::JobOffer, ScamType::Impersonation, ScamType::Loan, ScamType::LotteryGift, ScamType::AccountTakeover, ScamType::Other];
        $websites = collect(DemoContent::websites())->flatMap(fn (array $urls, string $scam) => array_map(fn ($url) => [$scam, $url], $urls))->values();
        $soldAccounts = DemoContent::soldAccounts();
        $cursor = ['account' => 0, 'website' => 0, 'back' => 0];

        $reports = [];
        $single = 0;
        foreach ($counts as $t => $count) {
            $type = $types[$t];
            $target = match ($type) {
                'account' => $this->accountTarget($accountScams[$cursor['account']++ % count($accountScams)]),
                'website' => $this->websiteTarget(...$websites[$cursor['website']++ % $websites->count()]),
                'back' => $this->backTarget($soldAccounts[$cursor['back']++ % count($soldAccounts)]),
            };

            for ($k = 0; $k < $count; $k++) {
                $status = 'approved';
                if ($count === 1) {
                    $status = match (true) {
                        $single % 6 === 1 => 'pending',
                        $single % 10 === 4 => 'rejected',
                        default => 'approved',
                    };
                    $single++;
                } elseif ($t === 0 && $k === $count - 1) {
                    $status = 'pending';
                }

                $reports[] = $this->createReport($target, $status, count($reports));
            }
        }

        return $reports;
    }

    /**
     * @return array<string, mixed>
     */
    private function accountTarget(ScamType $scamType): array
    {
        $byBank = mt_rand(1, 100) <= 60;
        $name = $this->personName();

        return [
            'type' => 'account',
            'scam_type' => $scamType,
            'target_id' => $byBank ? $this->bankAccount() : $this->phone(),
            'target_name' => $name,
            'target_bank' => $byBank ? $this->pick(DemoContent::banks()) : null,
            'target_socials' => mt_rand(1, 100) <= 40 ? 'Zalo '.$this->phone().', facebook.com/'.Str::slug(Str::ascii($name), '.').mt_rand(10, 99) : null,
            'sold_account' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function websiteTarget(string $scamType, string $url): array
    {
        $host = (string) parse_url($url, PHP_URL_HOST);

        return [
            'type' => 'website',
            'scam_type' => ScamType::from($scamType),
            'target_id' => $url,
            'target_name' => Str::headline(Str::before($host, '.')),
            'target_bank' => $this->pick(DemoContent::banks()),
            'target_socials' => null,
            'sold_account' => null,
        ];
    }

    /**
     * @param  array{game: string, nick: string}  $sold
     * @return array<string, mixed>
     */
    private function backTarget(array $sold): array
    {
        $name = $this->personName();

        return [
            'type' => 'back',
            'scam_type' => ScamType::AccountBack,
            'target_id' => $this->phone(),
            'target_name' => $name,
            'target_bank' => $this->pick(DemoContent::banks()),
            'target_socials' => mt_rand(1, 100) <= 50 ? 'facebook.com/'.Str::slug(Str::ascii($name), '.').'.shopacc' : null,
            'sold_account' => $sold['game'].' - nick '.$sold['nick'],
            'game' => $sold['game'],
            'nick' => $sold['nick'],
        ];
    }

    /**
     * @param  array<string, mixed>  $target
     */
    private function createReport(array $target, string $status, int $index): Report
    {
        /** @var ScamType $scamType */
        $scamType = $target['scam_type'];
        $createdAt = $status === 'pending'
            ? now()->subHours(mt_rand(2, 120))
            : now()->subDays(mt_rand(1, 45))->subMinutes(mt_rand(0, 1200));
        $amount = $this->damageAmount($scamType, $target['type'], $index);
        $reporterName = mt_rand(1, 100) <= 70 ? $this->personName() : null;
        $approved = $status === 'approved';

        $images = [];
        for ($i = mt_rand(1, 4); $i > 0; $i--) {
            $images[] = $this->photos->upload('scamproof-evidence-'.($this->evidenceCursor++ % self::EVIDENCE_POOL), 'reports', 800, 1000, 'Bang chung');
        }

        return Report::forceCreate([
            'type' => $target['type'],
            'reporter_name' => $reporterName ?? 'Người dùng',
            'reporter_contact' => $this->phone(),
            'reporter_email' => Str::slug(Str::ascii($reporterName ?? 'nguoidung'), '.').$index.'@'.ClearDemoData::EMAIL_DOMAIN,
            'reporter_role' => mt_rand(1, 100) <= 85 ? 'victim' : 'proxy',
            'target_id' => $target['target_id'],
            'target_name' => $target['target_name'],
            'target_bank' => $target['target_bank'],
            'target_socials' => $target['target_socials'],
            'sold_account' => $target['sold_account'],
            'category' => $scamType->label(),
            'scam_type' => $scamType,
            'damage_amount' => $amount,
            'description' => $this->story($target, $amount, $createdAt),
            'evidence_images' => $images,
            'status' => $status,
            'approved_at' => $approved ? $this->notAfterNow($createdAt->copy()->addHours(mt_rand(2, 36))) : null,
            'rejection_reason' => $status === 'rejected' ? $this->pick([
                'Bằng chứng chưa rõ ràng, ảnh bị cắt mất thông tin chuyển khoản.',
                'Trùng với một tố cáo đã được duyệt trước đó.',
                'Nội dung là tranh chấp dân sự, chưa đủ căn cứ lừa đảo.',
            ]) : null,
            'ip_address' => $this->demoIp(),
            'view_count' => $approved ? mt_rand(30, 2500) : 0,
            'search_count' => $approved ? mt_rand(0, 300) : 0,
            'moderator_id' => $status === 'pending' ? null : $this->editor->id,
            'slug' => Str::slug(($target['target_name'] ?? 'scammer').'-'.Str::random(8)),
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }

    /**
     * Whole VND rounded to 50.000; the first reports pin the extremes (200.000 and the 99.999.999.999 cap).
     */
    private function damageAmount(ScamType $scamType, string $type, int $index): int
    {
        if ($index === 1) {
            return 200_000;
        }
        if ($type === 'website' && $scamType === ScamType::Investment && ! $this->capUsed) {
            $this->capUsed = true;

            return 99_999_999_999;
        }

        [$min, $max] = match ($scamType) {
            ScamType::Investment => [20_000_000, 5_000_000_000],
            ScamType::Impersonation => [50_000_000, 2_500_000_000],
            ScamType::Romance => [10_000_000, 800_000_000],
            ScamType::JobOffer => [1_000_000, 120_000_000],
            ScamType::AccountTakeover => [2_000_000, 80_000_000],
            ScamType::Loan => [1_000_000, 30_000_000],
            ScamType::LotteryGift => [500_000, 20_000_000],
            ScamType::FakeShop => [200_000, 15_000_000],
            ScamType::AccountBack => [300_000, 25_000_000],
            ScamType::Other => [500_000, 10_000_000],
        };

        // Skewed towards the low end, like real damage amounts.
        $value = $min + ($max - $min) * (mt_rand(0, 1000) / 1000) ** 3;

        return (int) (round($value / 50_000) * 50_000);
    }

    /**
     * @param  array<string, mixed>  $target
     */
    private function story(array $target, int $amount, CarbonInterface $createdAt): string
    {
        $stories = DemoContent::stories();
        $items = DemoContent::items();
        $templates = match ($target['type']) {
            'website' => $stories['website'],
            'back' => $stories['back'],
            default => $stories[$target['scam_type']->value],
        };

        return strtr($this->pick($templates), [
            '{amount}' => number_format($amount, 0, ',', '.').'đ',
            '{bank}' => $target['target_bank'] ?? $this->pick(DemoContent::banks()),
            '{platform}' => $this->pick($items['platforms']),
            '{item}' => $this->pick($items['items']),
            '{site}' => $target['type'] === 'website' ? (string) parse_url($target['target_id'], PHP_URL_HOST) : '',
            '{game}' => $target['game'] ?? '',
            '{nick}' => $target['nick'] ?? '',
            '{date}' => $createdAt->copy()->subDays(mt_rand(1, 10))->format('d/m'),
        ]);
    }

    /**
     * About 40 comments, half on repeat offenders' reports.
     *
     * @param  list<Report>  $reports
     */
    private function seedComments(array $reports): int
    {
        $approved = array_values(array_filter($reports, fn (Report $report) => $report->isApproved()));
        $samples = DemoContent::comments();

        for ($c = 0; $c < 40; $c++) {
            $report = $c < 20 ? $approved[$c % 30] : $approved[mt_rand(0, count($approved) - 1)];
            $sample = $samples[$c % count($samples)];
            $ip = $this->demoIp();
            $createdAt = $this->notAfterNow($report->created_at->copy()->addHours(mt_rand(3, 96)));

            Comment::forceCreate([
                'report_id' => $report->id,
                'full_name' => $sample['name'] ?? 'Ẩn danh - '.$ip,
                'content' => $sample['content'],
                'ip_address' => $ip,
                'is_anonymous' => $sample['name'] === null,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        }

        return 40;
    }

    /**
     * @param  list<Report>  $reports
     */
    private function seedComplaints(array $reports): int
    {
        $approved = array_values(array_filter($reports, fn (Report $report) => $report->isApproved()));
        $complaints = [
            [ComplaintReason::NotScam, 'Tôi là chủ số tài khoản này. Tôi đã giao hàng đầy đủ nhưng người mua không nhận và quay lại tố cáo. Tôi gửi kèm ảnh vận đơn để ban quản trị xem xét.'],
            [ComplaintReason::RemoveRequest, 'Hai bên đã thương lượng và tôi đã hoàn lại toàn bộ tiền cho người tố cáo. Kính mong ban quản trị gỡ bài, tôi gửi kèm ảnh xác nhận hoàn tiền.'],
            [ComplaintReason::WrongInfo, 'Bài tố cáo ghi sai tên chủ tài khoản. Số tài khoản này của tôi đã bị kẻ gian dùng giấy tờ giả để mở, tôi đã trình báo ngân hàng.'],
        ];

        foreach ($complaints as $i => [$reason, $message]) {
            $name = $this->personName();
            $createdAt = now()->subHours(mt_rand(3, 72));

            Complaint::forceCreate([
                'report_id' => $approved[$i * 7]->id,
                'contact_name' => $name,
                'contact_email' => Str::slug(Str::ascii($name), '.').'@'.ClearDemoData::EMAIL_DOMAIN,
                'contact_phone' => $this->phone(),
                'reason' => $reason,
                'message' => $message,
                'evidence_images' => [$this->photos->upload('scamproof-evidence-'.($i * 11), 'complaints', 800, 1000, 'Bang chung')],
                'status' => ComplaintStatus::Pending,
                'ip_address' => $this->demoIp(),
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        }

        return count($complaints);
    }

    /**
     * Buyers follow a few phone/bank targets of approved reports.
     *
     * @param  list<Report>  $reports
     * @param  list<User>  $buyers
     */
    private function seedWatches(array $reports, array $buyers): int
    {
        $targets = collect($reports)
            ->filter(fn (Report $report) => $report->isApproved() && $report->type !== 'website')
            ->map(fn (Report $report) => Watch::normalizeTarget($report->target_id))
            ->filter()
            ->unique(fn (array $target) => implode('|', $target))
            ->take(5)
            ->values();

        foreach ($targets as $i => [$type, $value]) {
            Watch::create(['user_id' => $buyers[$i % count($buyers)]->id, 'type' => $type, 'value' => $value]);
        }

        return $targets->count();
    }

    /**
     * "Tìm kiếm nhiều trong tuần": 8 approved targets with falling counts, plus GDV name lookups and misses.
     *
     * @param  list<Report>  $reports
     * @param  list<Insurance>  $insurances
     */
    private function seedSearchLogs(array $reports, array $insurances): int
    {
        $targets = collect($reports)->filter(fn (Report $report) => $report->isApproved())->pluck('target_id')->unique()->take(8)->values();
        $logs = 0;

        foreach ([26, 21, 17, 14, 11, 8, 6, 4] as $t => $count) {
            for ($n = 0; $n < $count; $n++) {
                $query = $targets[$t];
                // Some visitors type the +84 form; search groups it with the 0 form.
                if ($n % 3 === 2 && StringHelper::detectQueryType($query)[0] === 'phone') {
                    $query = '+84'.substr($query, 1);
                }
                $this->searchLog($query, true);
                $logs++;
            }
        }

        foreach (array_slice($insurances, 0, 3) as $insurance) {
            for ($n = mt_rand(2, 4); $n > 0; $n--) {
                $this->searchLog($insurance->full_name, true);
                $logs++;
            }
        }

        for ($n = 0; $n < 12; $n++) {
            $this->searchLog($this->phone(), false);
            $logs++;
        }

        return $logs;
    }

    private function searchLog(string $query, bool $found): void
    {
        $createdAt = now()->subMinutes(mt_rand(5, 6 * 24 * 60));

        SearchLog::forceCreate([
            'search_query' => $query,
            'is_found' => $found,
            'ip_address' => $this->demoIp(),
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }

    private function seedBlogPosts(): int
    {
        $posts = DemoContent::blogPosts();

        foreach ($posts as $i => $post) {
            $createdAt = now()->subDays($i * 5 + 1)->subMinutes(mt_rand(0, 600));

            Post::forceCreate([
                'title' => $post['title'],
                'slug' => StringHelper::generateGlobalUniqueSlug($post['title']),
                'description' => $post['description'],
                'content' => $post['content'],
                'thumbnail' => $this->photos->upload("scamproof-blog-{$i}", 'posts', 1200, 675, 'Scamproof'),
                'is_featured' => $post['featured'] ? 1 : 0,
                'view_count' => mt_rand(120, 4000),
                'hashtags' => $post['hashtags'],
                'author_id' => $this->editor->id,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        }

        return count($posts);
    }

    /**
     * GDV partners created through the same services as the admin form, so channels and the ledger look real.
     *
     * @return list<Insurance>
     */
    private function seedInsurances(): array
    {
        $channels = app(InsuranceChannelService::class);
        $ledger = app(InsuranceLedgerService::class);
        $insurances = [];

        foreach (DemoContent::insurances() as $i => $partner) {
            $expiredAt = match ($partner['state']) {
                'expiring' => now()->addDays(4),
                'expired' => now()->subDays(mt_rand(5, 40)),
                default => now()->addDays(mt_rand(90, 330)),
            };
            $joinedAt = $expiredAt->copy()->subYear();
            $handle = Str::slug(Str::ascii($partner['name']), '.');
            $phone = $this->phone();

            $insurance = Insurance::create([
                'full_name' => $partner['name'],
                'slug' => StringHelper::generateGlobalUniqueSlug($partner['name']),
                'amount' => 0,
                'insurance_date' => $joinedAt,
                'expired_at' => $expiredAt,
                'verified_at' => $joinedAt,
                'status' => 1,
                'services' => array_map(fn (string $title) => ['title' => $title], $partner['services']),
            ]);

            $channels->sync($insurance, [
                ['type' => 'phone', 'value' => $phone, 'is_official' => true, 'verified' => true],
                ['type' => 'zalo', 'value' => 'https://zalo.me/'.$phone, 'is_official' => true, 'verified' => true],
                ['type' => 'facebook', 'value' => 'https://facebook.com/'.$handle.'.gdv', 'is_official' => true, 'verified' => true],
                ['type' => 'telegram', 'value' => 'https://t.me/'.str_replace('.', '_', $handle).'_gdv', 'is_official' => true, 'verified' => true],
                ['type' => 'bank_account', 'value' => $this->bankAccount(), 'is_official' => true, 'verified' => true, 'bank' => $this->pick(DemoContent::banks()), 'holder' => Str::upper(Str::ascii($partner['name']))],
            ]);

            $ledger->record($insurance, LedgerEntryType::Deposit, $partner['deposit'], $this->editor, 'FT'.mt_rand(10_000_000, 99_999_999), 'Ký quỹ ban đầu');
            foreach ($partner['topups'] as $topup) {
                $ledger->record($insurance, LedgerEntryType::Deposit, $topup, $this->editor, 'FT'.mt_rand(10_000_000, 99_999_999), 'Nạp thêm quỹ');
            }

            if ($partner['state'] === 'suspended') {
                app(InsuranceSuspensionService::class)->suspend($insurance, 'Đang xác minh tố cáo liên quan đến giao dịch trung gian', $this->editor);
            }

            $insurance->update(['avatar' => $this->photos->upload("scamproof-gdv-{$i}", 'insurances', 400, 400, $partner['name'])]);
            $insurances[] = $insurance;
        }

        return $insurances;
    }

    private function phone(): string
    {
        $prefixes = ['090', '091', '093', '094', '096', '097', '098', '086', '088', '032', '033', '035', '037', '038', '070', '077', '079', '081', '083', '085'];

        return $this->pick($prefixes).sprintf('%07d', mt_rand(0, 9_999_999));
    }

    private function bankAccount(): string
    {
        $digits = (string) mt_rand(1, 9);
        for ($i = mt_rand(8, 12); $i > 0; $i--) {
            $digits .= mt_rand(0, 9);
        }

        return $digits;
    }

    private function personName(): string
    {
        return $this->pick(DemoContent::familyNames()).' '.$this->pick(DemoContent::middleNames()).' '.$this->pick(DemoContent::givenNames());
    }

    private function demoIp(): string
    {
        return ClearDemoData::IP_PREFIX.mt_rand(1, 254);
    }

    private function notAfterNow(CarbonInterface $time): CarbonInterface
    {
        return $time->isFuture() ? now() : $time;
    }

    /**
     * @template T
     *
     * @param  list<T>  $items
     * @return T
     */
    private function pick(array $items): mixed
    {
        return $items[mt_rand(0, count($items) - 1)];
    }
}
