<?php

use App\Enums\ScamType;
use App\Helpers\CommentHelper;
use App\Models\Comment;
use App\Models\Report;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
});

const RAW_TARGET_ID = '0987.654.321';
const RAW_TARGET_DIGITS = '0987654321';
const RAW_TARGET_NAME = 'Zxyqwerty Bvnmkoplu';
const RAW_REPORTER_NAME = 'Reporterqxz Uniquename';
const RAW_REPORTER_CONTACT = '0911222333444';

function detailReport(array $attributes = []): Report
{
    return Report::factory()->create(array_merge([
        'status' => 'approved',
        'type' => 'account',
        'target_id' => RAW_TARGET_ID,
        'target_name' => RAW_TARGET_NAME,
        'target_bank' => 'Vietcombank',
        'scam_type' => ScamType::Romance,
        'reporter_name' => RAW_REPORTER_NAME,
        'reporter_contact' => RAW_REPORTER_CONTACT,
        'description' => 'Đối tượng '.RAW_TARGET_NAME.' dùng số '.RAW_TARGET_DIGITS.' để nhận tiền rồi chặn liên lạc.',
        'evidence_images' => ['reports/proof-1.jpg'],
        'created_at' => now()->subDays(3),
    ], $attributes));
}

/**
 * @return array{0: string, 1: string} [head, body]
 */
function splitHtml(string $html): array
{
    $headEnd = strpos($html, '</head>');

    return [substr($html, 0, $headEnd), substr($html, $headEnd)];
}

test('approved report renders the verdict, sections and sidebar', function () {
    $report = detailReport();

    $this->get(route('scammer.show', $report->slug))
        ->assertOk()
        ->assertSeeInOrder([
            'Bị tố cáo lừa đảo',
            'Đã được kiểm duyệt',
            'Mô tả vụ việc',
            'Bằng chứng',
            'Thông tin đối tượng',
            'Bình luận cộng đồng',
            'Lời khuyên an toàn',
            'Cảnh báo liên quan',
        ])
        ->assertSee('<h1', false)
        ->assertSee('098***321')
        ->assertSee('Zxyqwerty B.')
        ->assertSee(asset('uploads/reports/proof-1.jpg'), false)
        ->assertSee('loading="lazy"', false)
        ->assertSee('Ảnh bằng chứng 1 trong tố cáo 098***321')
        ->assertDontSee('NGƯỜI NÀY CŨNG SCAM TÔI')
        ->assertDontSee('animate-pulse', false)
        ->assertDontSee('font-black', false);
});

test('pending and rejected reports are not public', function (string $status) {
    $report = detailReport(['status' => $status]);

    $this->get(route('scammer.show', $report->slug))->assertNotFound();
})->with(['pending', 'rejected']);

test('raw target and reporter details never appear in head or body', function () {
    $report = detailReport();
    Comment::factory()->create([
        'report_id' => $report->id,
        'full_name' => 'Ẩn danh - 10.20.30.40',
        'is_anonymous' => true,
        'ip_address' => '10.20.30.40',
        'content' => 'Tôi cũng chuyển cho '.RAW_TARGET_ID.' rồi bị chặn.',
    ]);

    $html = $this->get(route('scammer.show', $report->slug))->assertOk()->getContent();
    [$head, $body] = splitHtml($html);

    expect($head)
        ->toContain('<title>Cảnh báo 098***321 - '.ScamType::Romance->label())
        ->toContain('098***321')
        ->toContain('"@type":"BreadcrumbList"')
        ->toContain('"@type":"Article"');

    foreach ([$head, $body] as $part) {
        expect($part)
            ->not->toContain(RAW_TARGET_ID)
            ->not->toContain(RAW_TARGET_DIGITS)
            ->not->toContain(RAW_TARGET_NAME)
            ->not->toContain('Bvnmkoplu')
            ->not->toContain('Reporterqxz')
            ->not->toContain('Uniquename')
            ->not->toContain(RAW_REPORTER_CONTACT)
            ->not->toContain('10.20.30.40');
    }

    expect($body)->toContain('dùng số 098***321 để nhận tiền')
        ->toContain('Tôi cũng chuyển cho 098***321 rồi bị chặn.')
        ->toContain('Người dùng ẩn danh');
});

test('verdict shows the scam type badge, counts and report dates', function () {
    $older = detailReport(['created_at' => now()->subDays(20)]);
    $report = detailReport(['created_at' => now()->subDays(2)]);
    Comment::factory()->count(2)->create(['report_id' => $report->id]);

    $this->get(route('scammer.show', $report->slug))
        ->assertOk()
        ->assertSee(ScamType::Romance->label())
        ->assertSeeInOrder(['Tố cáo', '2', 'Bình luận', '2', 'Lần đầu', $older->created_at->format('d/m/Y'), 'Gần nhất', $report->created_at->format('d/m/Y')])
        ->assertSee('Lịch sử tố cáo đối tượng này')
        ->assertSee(route('scammer.show', $older->slug), false);
});

test('single report hides the timeline and handles a missing scam type', function () {
    $report = detailReport(['scam_type' => null]);

    $this->get(route('scammer.show', $report->slug))
        ->assertOk()
        ->assertSee('Chưa phân loại')
        ->assertDontSee('Lịch sử tố cáo đối tượng này')
        ->assertSee('Chưa có bình luận nào');
});

test('comments are listed and a new comment can be posted', function () {
    $report = detailReport();
    Comment::factory()->create(['report_id' => $report->id, 'full_name' => 'Trần Thị Lan', 'is_anonymous' => false, 'content' => 'Mình cũng suýt bị lừa bởi đối tượng này.']);

    $this->get(route('scammer.show', $report->slug))
        ->assertOk()
        ->assertSee('Trần Thị Lan')
        ->assertSee('Mình cũng suýt bị lừa bởi đối tượng này.');

    $this->postJson(route('comment.store', $report->id), [
        'full_name' => 'Minh Anh',
        'content' => 'Cảm ơn đã cảnh báo, mọi người cẩn thận.',
    ], ['X-Requested-With' => 'XMLHttpRequest'])
        ->assertOk()
        ->assertJsonPath('comment.display_name', 'Minh Anh');

    expect($report->comments()->count())->toBe(2);
});

test('comment validation and the daily rate limit still apply', function () {
    $report = detailReport();

    $this->postJson(route('comment.store', $report->id), ['full_name' => 'Minh Anh', 'content' => 'ngắn'], ['X-Requested-With' => 'XMLHttpRequest'])
        ->assertStatus(422)
        ->assertJsonPath('errors.content.0', 'Bình luận cần ít nhất 10 ký tự.');

    Cache::put(CommentHelper::getCommentRateLimitKey('127.0.0.1'), 5, now()->addHour());

    $this->postJson(route('comment.store', $report->id), [
        'full_name' => 'Minh Anh',
        'content' => 'Nội dung hợp lệ đủ dài để gửi.',
    ], ['X-Requested-With' => 'XMLHttpRequest'])
        ->assertStatus(422)
        ->assertJsonPath('errors.general.0', 'Bạn đã comment quá 5 lần hôm nay. Vui lòng thử lại vào ngày mai!');

    $this->from(route('scammer.show', $report->slug))
        ->post(route('comment.store', $report->id), ['full_name' => 'Minh Anh', 'content' => 'Nội dung hợp lệ đủ dài để gửi.'])
        ->assertRedirect(route('scammer.show', $report->slug))
        ->assertSessionHas('error');

    $this->withSession(['error' => 'Bạn đã comment quá 5 lần hôm nay.'])
        ->get(route('scammer.show', $report->slug))
        ->assertSee('Bạn đã comment quá 5 lần hôm nay.');

    expect($report->comments()->count())->toBe(0);
});

test('related warnings exclude the current report, its target and unapproved reports', function () {
    $report = detailReport();
    $sameTarget = detailReport();
    $related = detailReport(['target_id' => '0911000111', 'target_bank' => 'ACB']);
    $pending = detailReport(['target_id' => '0911000222', 'status' => 'pending', 'target_bank' => 'ACB']);
    detailReport(['target_id' => '0911000333', 'scam_type' => ScamType::Loan, 'target_bank' => 'ACB']);

    $ids = $this->get(route('scammer.show', $report->slug))
        ->assertOk()
        ->viewData('relatedReports')
        ->pluck('id');

    expect($ids->all())->toBe([$related->id])
        ->and($ids)->not->toContain($report->id, $sameTarget->id, $pending->id);
});

test('related warnings are capped at five', function () {
    $report = detailReport();
    foreach (range(1, 7) as $i) {
        detailReport(['target_id' => '09120000'.$i.$i]);
    }

    $this->get(route('scammer.show', $report->slug))
        ->assertOk()
        ->assertViewHas('relatedReports', fn ($related) => $related->count() === 5);
});

test('watch, share, complaint and report links are present', function () {
    $report = detailReport();

    $this->get(route('scammer.show', $report->slug))
        ->assertOk()
        ->assertSee('Đăng nhập để theo dõi')
        ->assertSee('https://www.facebook.com/sharer/sharer.php?u='.urlencode(route('scammer.show', $report->slug)), false)
        ->assertSee(route('complaints.create', ['report' => $report->slug]), false)
        ->assertSee('Gỡ phốt / Khiếu nại')
        ->assertSee('href="'.route('reports').'"', false)
        ->assertSee('Tố cáo thêm');
});

test('the share button copies the link with an inline status instead of a blocking alert', function () {
    $report = detailReport();

    $html = $this->get(route('scammer.show', $report->slug))
        ->assertOk()
        ->assertSee('data-copy="'.route('scammer.show', $report->slug).'"', false)
        ->assertSee('<span data-copy-label aria-live="polite">Chia sẻ</span>', false)
        ->assertSee("done('Đã sao chép')", false)
        ->assertDontSee('Đã sao chép liên kết')
        ->getContent();

    expect(substr_count($html, "button.querySelector('[data-copy-label]')"))->toBe(1);
});

test('the page runs a bounded number of queries regardless of data size', function () {
    $queries = 0;
    DB::listen(function () use (&$queries) {
        $queries++;
    });

    $countQueries = function (Report $report) use (&$queries): int {
        // Warm the layout's cached settings so only per-page queries are counted.
        $this->get(route('scammer.show', $report->slug))->assertOk();

        $queries = 0;
        $this->get(route('scammer.show', $report->slug))->assertOk();

        return $queries;
    };

    $small = detailReport(['target_id' => '0900000001']);
    $smallCount = $countQueries($small);

    $large = detailReport(['target_id' => '0900000002']);
    foreach (range(1, 4) as $i) {
        detailReport(['target_id' => '0900000002']);
        detailReport(['target_id' => '090000010'.$i]);
    }
    Comment::factory()->count(12)->create(['report_id' => $large->id]);
    $largeCount = $countQueries($large);

    expect($smallCount)->toBeLessThanOrEqual(8)
        ->and($largeCount)->toBeLessThanOrEqual($smallCount + 1);
});
