<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
});

test('the terms page renders with its seo title, summary and last updated date', function () {
    $this->get('/dieu-khoan')
        ->assertOk()
        ->assertSee('<title>Điều khoản sử dụng - Scamproof</title>', false)
        ->assertSee('<h1 id="page-title"', false)
        ->assertSee('Cập nhật lần cuối')
        ->assertSee('<time datetime="2026-11-16">16/11/2026</time>', false);
});

test('every table of contents link points to a section in the body', function () {
    $html = $this->get('/dieu-khoan')->assertOk()->getContent();

    preg_match_all('/<a href="#([a-z-]+)"[^>]*data-toc-link/', $html, $links);
    $anchors = array_values(array_unique($links[1]));

    expect($anchors)->toBe(['data', 'reporter-privacy', 'reporter-responsibility', 'removal-and-appeals', 'acceptance']);

    foreach ($anchors as $anchor) {
        expect($html)->toMatch('/<section\s+id="'.$anchor.'"\s+aria-labelledby="'.$anchor.'-title"/')
            ->toMatch('/<h2\s+id="'.$anchor.'-title"/');
    }
});

test('the terms page has no duplicated ids', function () {
    $html = $this->get('/dieu-khoan')->assertOk()->getContent();

    preg_match_all('/\sid="([^"]+)"/', $html, $matches);

    expect($matches[1])->toEqual(array_values(array_unique($matches[1])));
});

test('the terms keep their key clauses and link to the moderation policy and complaints', function () {
    $response = $this->get('/dieu-khoan')->assertOk();

    // Collapse the line wrapping of the Blade source so clauses can be matched as plain text.
    $text = preg_replace('/\s+/u', ' ', strip_tags($response->getContent()));

    $position = 0;
    foreach ([
        'Chúng tôi không chịu trách nhiệm về tính chính xác tuyệt đối',
        'thường được xem xét trong vòng 24 giờ',
        '(tên, liên hệ, địa chỉ IP) không được hiển thị công khai.',
        'Chỉ quản trị viên xem được các thông tin này để xác minh tố cáo và xử lý tố cáo sai sự thật.',
        'trừ khi có yêu cầu bằng văn bản từ cơ quan chức năng có thẩm quyền.',
        'Người gửi tố cáo chịu trách nhiệm về tính trung thực của nội dung đã cung cấp.',
        'Hai bên đã tự thỏa thuận và người tố cáo xác nhận thu hồi đơn',
        'Có bằng chứng xác thực bài viết là sai sự thật hoặc vu khống',
        'Có sai sót trong quá trình thẩm định của ban quản trị',
        'bạn có thể gửi khiếu nại kèm chứng cứ',
        'đồng nghĩa với việc bạn đã đọc và chấp thuận các điều khoản trên',
        'sửa đổi điều khoản mà không cần thông báo trước',
    ] as $clause) {
        $found = mb_strpos($text, $clause, $position);
        expect($found)->not->toBeFalse("Missing or out of order: {$clause}");
        $position = $found;
    }

    $response->assertSee('href="'.route('moderation.policy').'"', false)
        ->assertSee('href="'.route('complaints.create').'"', false);
});

test('the help card shows the configured contact channels', function () {
    $this->get('/dieu-khoan')
        ->assertOk()
        ->assertSee('id="terms-help-title"', false)
        ->assertSee('href="tel:0812.665.001"', false)
        ->assertSee('href="mailto:support@mstsoftware.vn"', false)
        ->assertSee('href="https://zalo.me/0812665001"', false);
});
