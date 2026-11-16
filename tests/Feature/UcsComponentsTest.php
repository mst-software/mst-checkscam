<?php

test('button renders a link when href is given and a button otherwise', function () {
    $this->blade('<x-ucs.button href="/bao-hiem-cs" variant="secondary">Xem</x-ucs.button>')
        ->assertSee('<a href="/bao-hiem-cs"', false)
        ->assertSee('bg-surface', false)
        ->assertSee('Xem');

    $this->blade('<x-ucs.button type="submit">Gửi</x-ucs.button>')
        ->assertSee('<button type="submit"', false)
        ->assertSee('bg-accent', false);
});

test('badge maps variants to semantic colors', function (string $variant, string $class) {
    $this->blade('<x-ucs.badge :variant="$variant">Nhãn</x-ucs.badge>', ['variant' => $variant])
        ->assertSee($class, false)
        ->assertSee('Nhãn');
})->with([
    ['neutral', 'text-ink-muted'],
    ['danger', 'text-danger'],
    ['success', 'text-success'],
    ['info', 'text-accent'],
]);

test('card renders title, slot and footer', function () {
    $this->blade('<x-ucs.card title="Thông tin">Nội dung<x-slot:footer>Chân thẻ</x-slot:footer></x-ucs.card>')
        ->assertSeeInOrder(['Thông tin', 'Nội dung', 'Chân thẻ']);
});

test('section title renders an optional action link', function () {
    $this->blade('<x-ucs.section-title title="Cảnh báo mới" action-label="Xem tất cả" action-url="/search" />')
        ->assertSee('Cảnh báo mới')
        ->assertSee('href="/search"', false)
        ->assertSee('Xem tất cả');

    $this->blade('<x-ucs.section-title title="Cảnh báo mới" />')
        ->assertDontSee('href=', false);
});

test('stat, container and empty state render their content', function () {
    $this->blade('<x-ucs.container size="narrow"><x-ucs.stat label="Tố cáo" value="1.234" tone="danger" /></x-ucs.container>')
        ->assertSee('max-w-3xl', false)
        ->assertSeeInOrder(['Tố cáo', '1.234'])
        ->assertSee('text-danger', false);

    $this->blade('<x-ucs.empty-state title="Chưa có dữ liệu" description="Thử lại sau" />')
        ->assertSeeInOrder(['Chưa có dữ liệu', 'Thử lại sau']);
});
