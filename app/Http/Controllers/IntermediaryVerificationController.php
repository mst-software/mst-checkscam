<?php

namespace App\Http\Controllers;

use App\Helpers\SeoHelper;
use App\Services\IntermediaryVerifier;
use Artesaos\SEOTools\Facades\SEOTools;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Public "is this really a Scamproof intermediary (GDV)?" check against the partners' official channels.
 */
class IntermediaryVerificationController extends Controller
{
    public const MAX_CHECKS_PER_MINUTE = 30;

    private const MAX_INPUT_LENGTH = 255;

    public function __construct(private IntermediaryVerifier $verifier) {}

    public function show(Request $request): Response
    {
        $query = mb_substr(trim($request->string('q')->toString()), 0, self::MAX_INPUT_LENGTH);

        SeoHelper::setPage(
            'Kiểm tra giao dịch viên',
            'Kiểm tra số điện thoại, Telegram, Facebook, Zalo hoặc số tài khoản có phải kênh chính thức của giao dịch viên (GDV) đã ký quỹ với Scamproof hay không.',
        );
        SEOTools::metatags()->setCanonical(route('intermediary.verify'));

        if ($query === '') {
            return response()->view('verify.show', ['query' => '', 'result' => null, 'throttled' => false]);
        }

        // Result pages depend on user input: keep them out of search engines.
        SEOTools::setTitle('Kết quả kiểm tra giao dịch viên - '.SeoHelper::BRAND);
        SEOTools::metatags()->setRobots('noindex, follow');

        $limiterKey = 'verify-intermediary:'.$request->ip();

        if (RateLimiter::tooManyAttempts($limiterKey, self::MAX_CHECKS_PER_MINUTE)) {
            return response()
                ->view('verify.show', ['query' => $query, 'result' => null, 'throttled' => true], 429)
                ->header('Retry-After', (string) RateLimiter::availableIn($limiterKey));
        }

        RateLimiter::hit($limiterKey, 60);

        return response()->view('verify.show', [
            'query' => $query,
            'result' => $this->verifier->verify($query),
            'throttled' => false,
        ]);
    }
}
