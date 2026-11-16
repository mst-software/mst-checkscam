<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('redirects guests away from the admin panel', function () {
    $this->get('/admin')->assertRedirect(route('admin.auth.login'));
});

it('blocks marketplace users from the admin panel', function () {
    $user = User::factory()->create(['role' => 'user', 'status' => 1]);

    $this->actingAs($user)->get('/admin')->assertForbidden();
    $this->actingAs($user)->get('/admin/reports')->assertForbidden();
});

it('blocks inactive staff', function () {
    $admin = User::factory()->create(['role' => 'admin', 'status' => 0]);

    $this->actingAs($admin)->get('/admin')->assertForbidden();
});

it('lets active admins and moderators in', function (string $role) {
    $user = User::factory()->create(['role' => $role, 'status' => 1]);

    $this->actingAs($user)->get('/admin')->assertOk();
})->with(['admin', 'moderator']);
