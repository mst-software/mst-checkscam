<?php

use App\Models\Report;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can be run twice without duplicate key errors or multiplying demo data', function () {
    $this->seed(DatabaseSeeder::class);
    $reports = Report::count();

    $this->seed(DatabaseSeeder::class);

    expect(User::where('username', 'admin')->count())->toBe(1)
        ->and(Report::count())->toBe($reports);
});
