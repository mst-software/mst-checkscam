<?php

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('database seeder can run again on a database that already has the admin', function () {
    $this->seed(DatabaseSeeder::class);
    $this->seed(DatabaseSeeder::class);

    $admins = User::where('username', 'admin')->get();

    expect($admins)->toHaveCount(1)
        ->and($admins->first()->role)->toBe('admin')
        ->and($admins->first()->email)->toBe('admin@mstsoftware.vn');
});
