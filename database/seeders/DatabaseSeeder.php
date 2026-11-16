<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\Insurance;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(SettingSeeder::class);
        $this->call(BannerSeeder::class);
        // Idempotent: re-running `db:seed` must not fail on the unique admin username.
        $admin = User::updateOrCreate(
            ['username' => 'admin'],
            [
                'email' => 'admin@mstsoftware.vn',
                'password' => Hash::make('123456'),
                'full_name' => 'Quản trị viên',
                'role' => 'admin',
                'status' => 1,
            ],
        );
        if ($admin->email_verified_at === null) {
            $admin->forceFill(['email_verified_at' => now()])->save();
        }

        // Demo data is only generated on an empty database so repeated runs do not multiply it.
        if (Report::query()->doesntExist()) {
            Report::factory()->count(50)->create();
            Insurance::factory()->count(200)->create();
            Post::factory()->count(30)->create();
            User::factory()->count(2)->create();
        }

        $approvedReports = Report::where('status', 'approved')->doesntHave('comments')->get();

        $approvedReports->each(function ($report) {
            $count = fake()->numberBetween(2, 8);
            Comment::factory($count)->create([
                'report_id' => $report->id,
            ]);
        });
    }
}
