<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the api docs page documents the real endpoint and how to get a key', function () {
    $this->withoutVite()
        ->get('/api-checkscam')
        ->assertOk()
        ->assertSee(route('api.v1.check'))
        ->assertSee('Authorization: Bearer', false)
        ->assertSee('X-Api-Key')
        ->assertSee('Lấy API key');
});

test('the api docs page documents the verified_intermediary flag', function () {
    $this->withoutVite()
        ->get('/api-checkscam')
        ->assertOk()
        ->assertSee('"verified_intermediary": false')
        ->assertSee('kể cả kênh của giao dịch viên đã hết hạn');
});
