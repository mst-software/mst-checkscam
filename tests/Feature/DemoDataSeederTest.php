<?php

use App\Console\Commands\ClearDemoData;
use App\Models\Comment;
use App\Models\Complaint;
use App\Models\Insurance;
use App\Models\InsuranceLedgerEntry;
use App\Models\NewfeedPost;
use App\Models\Post;
use App\Models\Report;
use App\Models\SearchLog;
use App\Models\User;
use App\Models\Watch;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    Storage::fake('my_public');
    Storage::fake('public');
    Http::preventStrayRequests();
});

function fakeDemoPhotoDownloads(): void
{
    $image = imagecreatetruecolor(16, 12);
    ob_start();
    imagejpeg($image);
    $jpeg = ob_get_clean();

    Http::fake(['picsum.photos/*' => Http::response($jpeg, 200, ['Content-Type' => 'image/jpeg'])]);
}

it('seeds marked demo data once, even when run twice', function () {
    fakeDemoPhotoDownloads();

    $this->seed(DemoDataSeeder::class);
    $this->seed(DemoDataSeeder::class);

    $demoUserIds = ClearDemoData::demoUsers()->pluck('id');
    $reports = ClearDemoData::demoReports();

    expect($demoUserIds)->toHaveCount(16)
        ->and(User::where('email', DemoDataSeeder::ADMIN_EMAIL)->value('role'))->toBe('admin')
        ->and(NewfeedPost::count())->toBe(36)
        ->and(NewfeedPost::whereNotIn('user_id', $demoUserIds)->exists())->toBeFalse()
        ->and(NewfeedPost::where('is_hidden', 1)->count())->toBe(3)
        ->and((clone $reports)->count())->toBe(90)
        ->and(Report::count())->toBe(90)
        ->and((clone $reports)->where('status', 'pending')->count())->toBe(9)
        ->and((clone $reports)->where('status', 'rejected')->count())->toBe(5)
        ->and((clone $reports)->distinct()->pluck('type')->sort()->values()->all())->toBe(['account', 'back', 'website'])
        ->and((clone $reports)->max('damage_amount'))->toEqual(99_999_999_999)
        ->and((clone $reports)->min('damage_amount'))->toEqual(200_000)
        ->and(Comment::count())->toBe(40)
        ->and(Comment::where('ip_address', 'not like', ClearDemoData::IP_PREFIX.'%')->exists())->toBeFalse()
        ->and(Complaint::where('status', 'pending')->count())->toBe(3)
        ->and(Watch::count())->toBe(5)
        ->and(Post::whereIn('author_id', $demoUserIds)->count())->toBe(8)
        ->and(Insurance::count())->toBe(8)
        ->and(Insurance::trusted()->count())->toBe(5)
        ->and(InsuranceLedgerEntry::whereNotIn('recorded_by', $demoUserIds)->exists())->toBeFalse()
        ->and(SearchLog::where('ip_address', 'not like', ClearDemoData::IP_PREFIX.'%')->exists())->toBeFalse();
});

it('stores downloaded photos where the app keeps uploads and references them from the rows', function () {
    fakeDemoPhotoDownloads();

    $this->seed(DemoDataSeeder::class);

    $evidence = Report::pluck('evidence_images')->flatten();
    $newfeedImages = NewfeedPost::pluck('image_paths')->flatten();

    expect($evidence)->not->toBeEmpty()
        ->and($evidence->every(fn (string $path) => str_starts_with($path, 'reports/') && Storage::disk('my_public')->exists($path)))->toBeTrue()
        ->and($newfeedImages->every(fn (string $path) => str_starts_with($path, 'posts/') && Storage::disk('public')->exists($path)))->toBeTrue()
        ->and(Post::pluck('thumbnail')->every(fn (string $path) => Storage::disk('my_public')->exists($path)))->toBeTrue()
        ->and(Insurance::pluck('avatar')->every(fn (string $path) => Storage::disk('my_public')->exists($path)))->toBeTrue();

    Http::assertSent(fn ($request) => str_starts_with($request->url(), 'https://picsum.photos/seed/scamproof-'));
});

it('draws local images when the photo service is unreachable', function () {
    Http::fake(['picsum.photos/*' => Http::response('down', 503)]);

    $this->seed(DemoDataSeeder::class);

    $path = Report::first()->evidence_images[0];

    expect(Report::count())->toBe(90)
        ->and(Storage::disk('my_public')->exists($path))->toBeTrue()
        ->and(getimagesize(Storage::disk('my_public')->path($path))[0])->toBeGreaterThan(100);
});

it('clears exactly the demo rows and their files', function () {
    fakeDemoPhotoDownloads();
    $realUser = User::factory()->create(['role' => 'admin', 'status' => 1]);
    $realReport = Report::factory()->create(['ip_address' => '203.0.113.9']);
    $realPost = Post::factory()->create();
    $realInsurance = Insurance::factory()->create();
    $realNewfeed = NewfeedPost::create(['user_id' => $realUser->id, 'category' => 'Khác', 'content' => 'Bài đăng thật của người dùng']);
    $realSearch = SearchLog::create(['search_query' => '0900000000', 'is_found' => false, 'ip_address' => '203.0.113.9']);

    $this->seed(DemoDataSeeder::class);
    $files = Report::whereKeyNot($realReport->id)->pluck('evidence_images')->flatten();

    $this->artisan('demo:clear')->assertSuccessful();

    expect(User::pluck('id')->all())->toBe([$realUser->id])
        ->and(Report::pluck('id')->all())->toBe([$realReport->id])
        ->and(Post::pluck('id')->all())->toBe([$realPost->id])
        ->and(Insurance::pluck('id')->all())->toBe([$realInsurance->id])
        ->and(NewfeedPost::withTrashed()->pluck('id')->all())->toBe([$realNewfeed->id])
        ->and(SearchLog::pluck('id')->all())->toBe([$realSearch->id])
        ->and(Comment::count() + Complaint::count() + Watch::count())->toBe(0)
        ->and($files->contains(fn (string $path) => Storage::disk('my_public')->exists($path)))->toBeFalse()
        ->and(Storage::disk('public')->allFiles('posts'))->toBe([]);
});

it('renders the public pages with demo data', function () {
    fakeDemoPhotoDownloads();
    $this->seed(DemoDataSeeder::class);

    $back = Report::where('status', 'approved')->where('type', 'back')->first();
    $website = Report::where('status', 'approved')->where('type', 'website')->first();
    $post = Post::first();
    $listing = NewfeedPost::visible()->latest()->first();

    $this->get('/')->assertOk()->assertSee('Tìm kiếm nhiều trong tuần');
    $this->get('/newfeed')->assertOk()->assertSee(e(str($listing->content)->limit(40, '')), false);
    $this->get(route('newfeed.show', $listing))->assertOk();
    $this->get('/'.$back->slug)->assertOk()->assertSee(asset('uploads/'.$back->evidence_images[0]), false);
    $this->get('/'.$website->slug)->assertOk();
    $this->get('/warnings')->assertOk();
    $this->get('/bao-hiem-cs')->assertOk()->assertSee(Insurance::trusted()->first()->full_name);
    $this->get(route('posts.frontend.show', $post->slug))->assertOk()->assertSee($post->title);
});
