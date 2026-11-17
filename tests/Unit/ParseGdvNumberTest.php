<?php

use App\Helpers\StringHelper;

test('a gdv number is read from short numbers and explicit prefixes', function (string $input, int $expected) {
    expect(StringHelper::parseGdvNumber($input))->toBe($expected);
})->with([
    ['5', 5],
    [' 12345 ', 12345],
    ['gdv 5', 5],
    ['GDV5', 5],
    ['GDV số 5', 5],
    ['gdv so 5', 5],
    ['Giao dịch viên số 5', 5],
    ['giao dich vien so 5', 5],
    ['GDV số 123456', 123456],
]);

test('phones, bank accounts, names and zero are not gdv numbers', function (string $input) {
    expect(StringHelper::parseGdvNumber($input))->toBeNull();
})->with([
    '123456',
    '0912345678',
    '+84912345678',
    '19036688',
    'gdv 0912345678',
    '0',
    'gdv 0',
    'Nguyễn Văn 5',
    'số 5',
    'GDV-000340',
    '',
]);
