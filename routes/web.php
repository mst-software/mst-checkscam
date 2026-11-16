<?php

use App\Http\Controllers\Admin\AdminApiKeyController;
use App\Http\Controllers\Admin\AdminBannerController;
use App\Http\Controllers\Admin\AdminCommentController;
use App\Http\Controllers\Admin\AdminComplaintController;
use App\Http\Controllers\Admin\AdminInsuranceController;
use App\Http\Controllers\Admin\AdminNewfeedController;
use App\Http\Controllers\Admin\AdminPostController;
use App\Http\Controllers\Admin\AdminReportController;
use App\Http\Controllers\Admin\AdminSearchAnalyticsController;
use App\Http\Controllers\Admin\AdminSettingController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Api\ScamCheckController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\ComplaintController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InsuranceController;
use App\Http\Controllers\NewfeedController;
use App\Http\Controllers\NewfeedPostController;
use App\Http\Controllers\NewfeedPostReportController;
use App\Http\Controllers\OgImageController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\SocialiteController;
use App\Http\Controllers\TelegramWebhookController;
use App\Http\Controllers\WarningController;
use App\Http\Controllers\WatchController;
use Illuminate\Support\Facades\Route;

// --- Frontend Pages ---
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/to-cao-lua-dao', function () {
    return view('reports.index');
})->name('reports');
Route::post('/to-cao-lua-dao', [ReportController::class, 'store'])->name('report.store');

Route::get('/bao-hiem-cs', [InsuranceController::class, 'index'])->name('insurances.frontend.index');
Route::get('/bao-hiem-cs/{slug}', [InsuranceController::class, 'show'])->name('insurances.frontend.show');

Route::get('/bai-viet', [PostController::class, 'index'])->name('posts.frontend.index');
Route::get('/bai-viet/{slug}', [PostController::class, 'show'])->name('posts.frontend.show');

// --- Khu Mua Bán ---
Route::get('/newfeed', [NewfeedController::class, 'index'])->name('newfeed.index');
Route::get('/newfeed/{post}', [NewfeedController::class, 'show'])->name('newfeed.show');
Route::get('/api/newfeed/posts', [NewfeedPostController::class, 'index'])->name('api.newfeed.posts.index');
Route::post('/api/newfeed/posts', [NewfeedPostController::class, 'store'])->name('api.newfeed.posts.store');
Route::delete('/api/newfeed/posts/{post}', [NewfeedPostController::class, 'destroy'])->name('api.newfeed.posts.destroy');
Route::post('/api/newfeed/posts/{post}/report', [NewfeedPostReportController::class, 'store'])->name('api.newfeed.posts.report');

// --- Public API v1 (API key auth, CSRF-exempt in bootstrap/app.php) ---
Route::prefix('api/v1')->name('api.v1.')->group(function () {
    Route::get('/check', [ScamCheckController::class, 'show'])->name('check');
});

// --- Telegram Bot (CSRF-exempt in bootstrap/app.php) ---
Route::post('/telegram/webhook/{secret}', [TelegramWebhookController::class, 'handle'])->name('telegram.webhook');

// --- Google OAuth ---
Route::get('/auth/google', [SocialiteController::class, 'redirect'])->name('auth.google');
Route::get('/auth/google/callback', [SocialiteController::class, 'callback'])->name('auth.google.callback');
Route::post('/auth/logout', [SocialiteController::class, 'logout'])->name('auth.logout');

// --- System Pages ---
Route::get('/api-checkscam', function () {
    return view('system.api');
});
Route::get('/doi-tac-uy-tin', function () {
    return view('system.partners');
});

// --- Support Pages ---
Route::get('/huong-dan-to-cao', function () {
    return view('support.guide');
});
Route::get('/lien-he-admin', function () {
    return view('support.contact');
});

// --- Legal Pages ---
Route::get('/dieu-khoan', function () {
    return view('legal.terms');
});
Route::get('/giai-quyet-khieu-nai', [ComplaintController::class, 'create'])->name('complaints.create');
Route::post('/giai-quyet-khieu-nai', [ComplaintController::class, 'store'])->name('complaints.store');

// --- Admin Panel ---
Route::name('admin.')->prefix('admin')->group(function () {
    Route::middleware(['auth', 'staff'])->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        // Reports
        Route::post('/reports/bulk-delete', [AdminReportController::class, 'bulkDestroy'])->name('reports.bulk-delete');
        Route::name('reports.')->prefix('reports')->group(function () {
            Route::get('/', [AdminReportController::class, 'index'])->name('index');
            Route::get('/{id}', [AdminReportController::class, 'show'])->name('detail');
            Route::post('/{id}/approve', [AdminReportController::class, 'approve'])->name('approve');
            Route::post('/{id}/reject', [AdminReportController::class, 'reject'])->name('reject');
            Route::put('/{id}', [AdminReportController::class, 'update'])->name('update');
            Route::delete('/{id}', [AdminReportController::class, 'destroy'])->name('destroy');
        });

        // Insurances
        Route::post('/insurances/bulk-delete', [AdminInsuranceController::class, 'bulkDestroy'])->name('insurances.bulk-delete');
        Route::name('insurances.')->prefix('insurances')->group(function () {
            Route::get('/', [AdminInsuranceController::class, 'index'])->name('index');
            Route::get('/create', [AdminInsuranceController::class, 'create'])->name('create');
            Route::post('/', [AdminInsuranceController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [AdminInsuranceController::class, 'edit'])->name('edit');
            Route::put('/{id}', [AdminInsuranceController::class, 'update'])->name('update');
            Route::delete('/{id}', [AdminInsuranceController::class, 'destroy'])->name('destroy');
        });

        // Posts
        Route::post('/posts/bulk-delete', [AdminPostController::class, 'bulkDestroy'])->name('posts.bulk-delete');
        Route::name('posts.')->prefix('posts')->group(function () {
            Route::get('/', [AdminPostController::class, 'index'])->name('index');
            Route::get('/create', [AdminPostController::class, 'create'])->name('create');
            Route::post('/', [AdminPostController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [AdminPostController::class, 'edit'])->name('edit');
            Route::put('/{id}', [AdminPostController::class, 'update'])->name('update');
            Route::delete('/{id}', [AdminPostController::class, 'destroy'])->name('destroy');
        });

        // Comments
        Route::post('/comments/bulk-delete', [AdminCommentController::class, 'bulkDestroy'])->name('comments.bulk-delete');
        Route::name('comments.')->prefix('comments')->group(function () {
            Route::get('/', [AdminCommentController::class, 'index'])->name('index');
            Route::delete('/{id}', [AdminCommentController::class, 'destroy'])->name('destroy');
        });

        // Complaints
        Route::name('complaints.')->prefix('complaints')->group(function () {
            Route::get('/', [AdminComplaintController::class, 'index'])->name('index');
            Route::get('/{complaint}', [AdminComplaintController::class, 'show'])->name('show');
            Route::post('/{complaint}/resolve', [AdminComplaintController::class, 'resolve'])->name('resolve');
            Route::post('/{complaint}/dismiss', [AdminComplaintController::class, 'dismiss'])->name('dismiss');
        });

        // Analytics
        Route::get('/search-analytics', [AdminSearchAnalyticsController::class, 'index'])->name('search-analytics.index');

        // Users
        Route::post('/users/bulk-delete', [AdminUserController::class, 'bulkDestroy'])->name('users.bulk-delete');
        Route::patch('/users/{user}/verify', [AdminUserController::class, 'toggleVerify'])->name('users.verify');
        Route::name('users.')->prefix('users')->group(function () {
            Route::get('/', [AdminUserController::class, 'index'])->name('index');
            Route::get('/create', [AdminUserController::class, 'create'])->name('create');
            Route::post('/', [AdminUserController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [AdminUserController::class, 'edit'])->name('edit');
            Route::put('/{id}', [AdminUserController::class, 'update'])->name('update');
            Route::delete('/{id}', [AdminUserController::class, 'destroy'])->name('destroy');
        });

        // Settings
        Route::name('settings.')->prefix('settings')->group(function () {
            Route::get('/', [AdminSettingController::class, 'index'])->name('index');
            Route::post('/', [AdminSettingController::class, 'update'])->name('update');
        });

        // Banners
        Route::post('/banners/bulk-delete', [AdminBannerController::class, 'bulkDestroy'])->name('banners.bulk-delete');
        Route::name('banners.')->prefix('banners')->group(function () {
            Route::get('/', [AdminBannerController::class, 'index'])->name('index');
            Route::get('/create', [AdminBannerController::class, 'create'])->name('create');
            Route::post('/', [AdminBannerController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [AdminBannerController::class, 'edit'])->name('edit');
            Route::put('/{id}', [AdminBannerController::class, 'update'])->name('update');
            Route::delete('/{id}', [AdminBannerController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/toggle', [AdminBannerController::class, 'toggleStatus'])->name('toggle');
        });

        Route::get('/upgrade', function () {
            return view('admin.system.upgrade');
        })->name('upgrade');

        // Public API keys
        Route::name('api-keys.')->prefix('api-keys')->group(function () {
            Route::get('/', [AdminApiKeyController::class, 'index'])->name('index');
            Route::get('/create', [AdminApiKeyController::class, 'create'])->name('create');
            Route::post('/', [AdminApiKeyController::class, 'store'])->name('store');
            Route::patch('/{apiKey}/revoke', [AdminApiKeyController::class, 'revoke'])->name('revoke');
        });

        // Khu Mua Bán (Newfeed Admin)
        Route::get('/newfeed/hidden', [AdminNewfeedController::class, 'hidden'])->name('newfeed.hidden');
        Route::patch('/newfeed/posts/{post}/unhide', [AdminNewfeedController::class, 'unhide'])->name('newfeed.unhide');
    });

    // Auth
    Route::name('auth.')->group(function () {
        Route::get('/login', [AuthController::class, 'showFormLogin'])->name('login');
        Route::post('/login', [AuthController::class, 'login'])->name('login');
        Route::get('/logout', [AuthController::class, 'logout'])->name('logout');
    });
});

// --- Search ---
Route::get('/search', [SearchController::class, 'index'])->name('search.index');
Route::get('/search/autocomplete', [SearchController::class, 'autoComplete'])->name('search.autocomplete');
Route::post('/search/clear-history', [SearchController::class, 'clearHistory'])->name('search.clearHistory');

// --- Comments ---
Route::post('/reports/{reportId}/comments', [CommentController::class, 'store'])->name('comment.store');
Route::patch('/comments/{id}', [CommentController::class, 'update'])->name('comment.update');
Route::delete('/comments/{id}', [CommentController::class, 'destroy'])->name('comment.destroy');

// --- Warnings List ---
Route::get('/warnings', [WarningController::class, 'index'])->name('warnings.index');

// --- Sitemap Generator ---
Route::get('/generate-sitemap', [SitemapController::class, 'generate'])->name('sitemap.generate');

// --- Theo dõi (Watchlist) ---
Route::middleware('auth')->group(function () {
    Route::get('/watchlist', [WatchController::class, 'index'])->name('watch.index');
    Route::post('/watchlist', [WatchController::class, 'store'])->name('watch.store');
    Route::delete('/watchlist/{watch}', [WatchController::class, 'destroy'])->name('watch.destroy');
});

// --- Share card (OG image) ---
Route::get('/og/{slug}.png', [OgImageController::class, 'report'])
    ->where('slug', '[A-Za-z0-9_-]+')
    ->name('og.report');

// --- Slug / Catch-all ---
Route::get('/{slug}', [ReportController::class, 'show'])->name('scammer.show');
