<?php

use App\Models\Insurance;
use App\Models\NewfeedPost;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
});

/**
 * @return array{title: string, description: string, robots: list<string>}
 */
function seoHead(string $html): array
{
    preg_match('/<title>(.*?)<\/title>/s', $html, $title);
    preg_match('/<meta\s+name="description"\s+content="([^"]*)"/i', $html, $description);
    preg_match_all('/<meta\s+name="robots"\s+content="([^"]*)"/i', $html, $robots);

    return [
        'title' => trim(html_entity_decode($title[1] ?? '')),
        'description' => trim(html_entity_decode($description[1] ?? '')),
        'robots' => $robots[1],
    ];
}

/**
 * @return list<string>
 */
function publicPageUrls(): array
{
    $user = User::factory()->create(['role' => 'user', 'status' => 1]);
    $report = Report::factory()->create(['status' => 'approved']);
    $insurance = Insurance::factory()->create(['status' => 1, 'expired_at' => now()->addMonth()]);
    $post = Post::factory()->create();
    $newfeedPost = NewfeedPost::create([
        'user_id' => $user->id,
        'category' => 'Dịch vụ MMO',
        'content' => 'Nhận thiết kế logo giá rẻ',
        'price' => 100000,
    ]);

    return [
        '/',
        '/to-cao-lua-dao',
        '/bao-hiem-cs',
        route('insurances.frontend.show', $insurance->slug),
        '/bai-viet',
        route('posts.frontend.show', $post->slug),
        '/newfeed',
        route('newfeed.show', $newfeedPost),
        '/api-checkscam',
        '/doi-tac-uy-tin',
        '/huong-dan-to-cao',
        '/lien-he-admin',
        '/dieu-khoan',
        '/moderation-policy',
        '/giai-quyet-khieu-nai',
        '/warnings',
        '/verify-intermediary',
        '/verify-intermediary?q=0912345678',
        '/insurance-claims/create',
        '/search',
        '/search?q=0912345678',
        route('scammer.show', $report->slug),
    ];
}

test('every public page has a unique title, a description and exactly one robots meta', function () {
    $titles = [];

    foreach (publicPageUrls() as $url) {
        $head = seoHead($this->get($url)->assertOk()->getContent());

        expect($head['title'])->not->toBeEmpty("{$url} has no <title>")
            ->and($head['description'])->not->toBeEmpty("{$url} has no meta description")
            ->and($head['robots'])->toHaveCount(1, "{$url} must have exactly one robots meta");

        $titles[$url] = $head['title'];
    }

    expect(array_unique($titles))->toHaveCount(count($titles));
});

test('public pages no longer mention the old checkscam brand', function () {
    foreach (publicPageUrls() as $url) {
        $html = $this->get($url)->assertOk()->getContent();
        // The API docs URL keeps its old path for SEO; the host may contain anything.
        $html = str_ireplace(['/api-checkscam', url('/')], '', $html);

        expect(stripos($html, 'checkscam'))->toBeFalse("{$url} still mentions checkscam");
    }
});

test('the default keywords and twitter tags use the scamproof brand', function () {
    $html = $this->get('/')->assertOk()->getContent();

    expect($html)->toContain('scamproof, kiểm tra lừa đảo, tố cáo lừa đảo, kiểm tra stk, kiểm tra sđt')
        ->not->toContain('name="twitter:site"');
});

test('static pages use the "<page> - Scamproof" title format', function (string $url, string $title) {
    expect(seoHead($this->get($url)->assertOk()->getContent())['title'])->toBe($title);
})->with([
    ['/to-cao-lua-dao', 'Tố cáo lừa đảo - Scamproof'],
    ['/newfeed', 'Khu mua bán - Scamproof'],
    ['/giai-quyet-khieu-nai', 'Giải quyết khiếu nại - Scamproof'],
    ['/api-checkscam', 'Public API kiểm tra lừa đảo - Scamproof'],
    ['/doi-tac-uy-tin', 'Đối tác uy tín - Scamproof'],
    ['/huong-dan-to-cao', 'Hướng dẫn tố cáo - Scamproof'],
    ['/dieu-khoan', 'Điều khoản sử dụng - Scamproof'],
    ['/moderation-policy', 'Quy trình kiểm duyệt - Scamproof'],
    ['/lien-he-admin', 'Liên hệ Admin - Scamproof'],
    ['/verify-intermediary', 'Kiểm tra giao dịch viên - Scamproof'],
    ['/insurance-claims/create', 'Gửi yêu cầu bồi thường - Scamproof'],
]);
