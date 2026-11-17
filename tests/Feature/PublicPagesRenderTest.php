<?php

use App\Models\Complaint;
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

test('the page header is a light header with breadcrumb, title, description and actions', function () {
    $this->blade(
        '<x-ucs.page-header title="Tiêu đề" description="Mô tả ngắn" :breadcrumbs="[[\'name\' => \'Mục\', \'url\' => \'/muc\']]">Hành động</x-ucs.page-header>',
    )
        ->assertSee('border-line bg-surface', false)
        ->assertDontSee('bg-hero', false)
        ->assertSee('<h1 id="page-title"', false)
        ->assertSeeInOrder(['Trang chủ', 'Mục', 'Tiêu đề', 'Mô tả ngắn', 'Hành động']);
});

test('static public pages render their page header', function (string $uri, string $title) {
    $this->get($uri)
        ->assertOk()
        ->assertSee('<h1 id="page-title"', false)
        ->assertSee($title)
        ->assertDontSee('CheckScam');
})->with([
    'insurances' => ['/bao-hiem-cs', 'Quỹ bảo hiểm'],
    'posts' => ['/bai-viet', 'Kiến thức MMO'],
    'terms' => ['/dieu-khoan', 'Điều khoản sử dụng'],
    'moderation policy' => ['/moderation-policy', 'Quy trình kiểm duyệt'],
    'dispute' => ['/giai-quyet-khieu-nai', 'Giải quyết khiếu nại'],
    'api docs' => ['/api-checkscam', 'Public API'],
    'partners' => ['/doi-tac-uy-tin', 'Đối tác uy tín'],
    'warnings' => ['/warnings', 'Danh sách cảnh báo'],
    'newfeed' => ['/newfeed', 'Khu mua bán'],
]);

test('the insurance list shows active members with their deposit', function () {
    Insurance::factory()->create(['full_name' => 'Thành Viên Uy Tín', 'amount' => 50000000, 'status' => 1, 'expired_at' => now()->addMonth()]);
    Insurance::factory()->create(['full_name' => 'Thành Viên Đã Khoá', 'status' => 0]);

    $this->get(route('insurances.frontend.index'))
        ->assertOk()
        ->assertSee('Thành Viên Uy Tín')
        ->assertSee('50.000.000 đ')
        ->assertDontSee('Thành Viên Đã Khoá');
});

test('the insurance detail shows deposit, services, contacts and payment accounts', function () {
    // The profile lists official channels; withChannels() derives them from the legacy JSON like the backfill.
    $insurance = Insurance::factory()->withChannels()->create([
        'full_name' => 'Trung Gian A',
        'amount' => 20000000,
        'status' => 1,
        'expired_at' => now()->addMonth(),
        'services' => [['title' => 'Trung gian giao dịch']],
        'contact_info' => [['platform' => 'Zalo', 'link' => 'https://zalo.me/0900000001']],
        'payment_accounts' => [['bank' => 'VCB', 'number' => '0123456789', 'name' => 'TRUNG GIAN A']],
    ]);

    $this->get(route('insurances.frontend.show', $insurance->slug))
        ->assertOk()
        ->assertSee('<h1 id="page-title"', false)
        ->assertSee('Trung Gian A')
        ->assertSee('Đã xác thực')
        ->assertSee('20.000.000 đ')
        ->assertSee('Trung gian giao dịch')
        ->assertSee('data-copy="https://zalo.me/0900000001"', false)
        ->assertSee('data-copy="0123456789"', false)
        // Bank names and profile links are no longer sent to third-party avatar/QR services.
        ->assertDontSee('ui-avatars.com', false)
        ->assertDontSee('api.qrserver.com', false)
        ->assertDontSee('CheckScam');
});

test('the post list and detail render with cover image alt text and related posts', function () {
    $post = Post::factory()->create(['title' => 'Bài viết chính', 'is_featured' => false]);
    Post::factory()->create(['title' => 'Bài viết liên quan', 'is_featured' => false]);

    $this->get(route('posts.frontend.index'))
        ->assertOk()
        ->assertSee('Bài viết chính');

    $this->get(route('posts.frontend.show', $post->slug))
        ->assertOk()
        ->assertSee('<h1 id="page-title"', false)
        ->assertSee('alt="Ảnh bìa: Bài viết chính"', false)
        ->assertSee('Bài viết liên quan');
});

test('the dispute form keeps its fields, honeypot and masks the reported target', function () {
    $report = Report::factory()->create([
        'status' => 'approved',
        'target_name' => 'Nguyen Van Lua',
        'target_id' => '0987654321',
        'type' => 'account',
    ]);

    $this->get(route('complaints.create', ['report' => $report->slug]))
        ->assertOk()
        ->assertSee('name="website"', false)
        ->assertSee('name="contact_name"', false)
        ->assertSee('name="evidence_images[]"', false)
        ->assertSee('data-scroll-to-error', false)
        ->assertDontSee('0987654321')
        ->assertDontSee('Nguyen Van Lua');
});

test('the dispute form marks invalid fields after a failed submit', function () {
    $this->from(route('complaints.create'))
        ->followingRedirects()
        ->post(route('complaints.store'), ['contact_name' => '', 'contact_email' => 'x'])
        ->assertOk()
        ->assertSee('id="contact_name-error"', false)
        ->assertSee('aria-invalid="true"', false);
});

test('the dispute form still stores a complaint', function () {
    $this->from(route('complaints.create'))
        ->followingRedirects()
        ->post(route('complaints.store'), [
            'contact_name' => 'Nguyen Van A',
            'contact_email' => 'a@example.com',
            'reason' => 'not_scam',
            'message' => 'Giao dịch đã hoàn tất, tôi gửi kèm biên lai chuyển khoản.',
        ])
        ->assertOk()
        ->assertSee('role="status"', false);

    expect(Complaint::count())->toBe(1);
});

test('the api docs page has copy buttons for code samples', function () {
    $this->get('/api-checkscam')
        ->assertOk()
        ->assertSee('data-copy-target=', false)
        ->assertSee('<table', false);
});

test('the watch list requires login', function () {
    $this->get(route('watch.index'))->assertRedirect(route('auth.google'));
});

test('the watch list renders the header, form and limit for a signed-in user', function () {
    $user = User::factory()->create(['role' => 'user', 'status' => 1]);

    $this->actingAs($user)
        ->get(route('watch.index'))
        ->assertOk()
        ->assertSee('<h1 id="page-title"', false)
        ->assertSee('Danh sách theo dõi')
        ->assertSee('action="'.route('watch.store').'"', false)
        ->assertSee('Bạn chưa theo dõi đối tượng nào')
        ->assertDontSee('CheckScam');
});

test('the newfeed index and detail render posts and the post form for signed-in users', function () {
    $user = User::factory()->create(['role' => 'user', 'status' => 1]);
    $post = NewfeedPost::create([
        'user_id' => $user->id,
        'category' => 'Dịch vụ MMO',
        'content' => 'Bán tài khoản **uy tín** giá tốt',
        'price' => 150000,
    ]);

    $this->get(route('newfeed.index'))
        ->assertOk()
        ->assertSee('Bán tài khoản')
        ->assertSee('150.000 đ')
        ->assertSee('id="popup-login"', false);

    $this->actingAs($user)
        ->get(route('newfeed.index'))
        ->assertOk()
        ->assertSee('id="popup-post-form"', false)
        ->assertSee('id="btn-submit-post"', false);

    $this->get(route('newfeed.show', $post))
        ->assertOk()
        ->assertSee('<h1 id="page-title"', false)
        ->assertSee('<strong>uy tín</strong>', false)
        ->assertDontSee('CheckScam');
});
