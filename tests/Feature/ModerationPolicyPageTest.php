<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
});

test('the moderation policy page explains the review process and both sides rights', function () {
    $this->get(route('moderation.policy'))
        ->assertOk()
        ->assertSee('<title>Quy trình kiểm duyệt - Scamproof</title>', false)
        ->assertSee('<h1 id="page-title"', false)
        ->assertSeeInOrder([
            'Một tố cáo cần có gì',
            'Người kiểm duyệt xem xét thế nào',
            'Kết quả kiểm duyệt',
            'Thông tin được hiển thị và không hiển thị',
            'Thông tin của người báo cáo',
            'Quyền phản hồi của bên bị tố cáo',
            'Trách nhiệm của người gửi tố cáo',
        ])
        ->assertSee('Chỉ quản trị viên xem được các thông tin này')
        ->assertSee('href="'.route('complaints.create').'"', false)
        ->assertSee('href="'.url('/dieu-khoan').'"', false)
        ->assertDontSee('tuyệt đối');
});

test('the moderation policy url is english and sits above the report catch-all', function () {
    expect(route('moderation.policy', absolute: false))->toBe('/moderation-policy');

    $this->get('/moderation-policy')->assertOk()->assertDontSee('Không tìm thấy');
});

test('the footer links to the moderation policy and carries the data disclaimer', function () {
    $html = $this->get('/dieu-khoan')->assertOk()->getContent();

    $footer = (string) str($html)->after('<footer');

    expect($footer)->toContain('href="'.route('moderation.policy').'"', 'Quy trình kiểm duyệt')
        ->toContain('Kết quả chỉ phản ánh dữ liệu tố cáo đã được kiểm duyệt trên Scamproof, không phải bảo đảm an toàn.');
});

test('terms describe reporter privacy accurately and make reporters responsible for the truth', function () {
    $this->get('/dieu-khoan')
        ->assertOk()
        ->assertSee('Chỉ quản trị viên xem được các thông tin này để xác minh tố cáo và xử lý tố cáo sai sự thật.')
        ->assertSee('Người gửi tố cáo chịu trách nhiệm về tính trung thực của nội dung đã cung cấp.')
        ->assertDontSee('bảo mật danh tính người tố cáo một cách tuyệt đối');
});

test('the review time is stated the same way everywhere', function (string $url) {
    $html = $this->get($url)->assertOk()->getContent();

    expect($html)->toContain('thường được xem xét trong vòng 24 giờ')
        ->not->toContain('30 phút đến 12 giờ')
        ->not->toContain('sẽ duyệt trong 24 giờ');
})->with([
    'home faq' => ['/'],
    'report form' => ['/to-cao-lua-dao'],
    'moderation policy' => ['/moderation-policy'],
    'terms' => ['/dieu-khoan'],
]);
