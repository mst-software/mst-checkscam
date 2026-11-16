<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Services\ScamLookupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;

class ScamCheckController extends Controller
{
    public function __construct(private ScamLookupService $scamLookup) {}

    public function show(Request $request): JsonResponse
    {
        $plainKey = $request->bearerToken() ?: $request->header('X-Api-Key');
        $apiKey = is_string($plainKey) && $plainKey !== '' ? ApiKey::findActiveByPlainKey($plainKey) : null;

        if (! $apiKey) {
            return $this->error('unauthenticated', 'API key không hợp lệ hoặc đã bị thu hồi.', 401);
        }

        // Per-key limit, enforced here like the other rate limits in this app.
        $limiterKey = 'api-key:'.$apiKey->id;
        $limit = $apiKey->rate_limit_per_minute;

        if (RateLimiter::tooManyAttempts($limiterKey, $limit)) {
            $retryAfter = RateLimiter::availableIn($limiterKey);

            return $this->error('rate_limited', 'Vượt quá giới hạn truy vấn. Vui lòng thử lại sau.', 429)
                ->withHeaders([
                    'Retry-After' => $retryAfter,
                    'X-RateLimit-Limit' => $limit,
                    'X-RateLimit-Remaining' => 0,
                ]);
        }

        RateLimiter::hit($limiterKey, 60);
        $rateLimitHeaders = [
            'X-RateLimit-Limit' => $limit,
            'X-RateLimit-Remaining' => RateLimiter::remaining($limiterKey, $limit),
        ];

        $apiKey->forceFill(['last_used_at' => now()])->save();

        $validator = Validator::make($request->query(), [
            'q' => ['required', 'string', 'min:2', 'max:255'],
        ], [
            'q.required' => 'Vui lòng nhập tham số q (SĐT, STK, link Facebook hoặc tên).',
            'q.string' => 'Tham số q phải là chuỗi.',
            'q.min' => 'Tham số q phải có ít nhất :min ký tự.',
            'q.max' => 'Tham số q tối đa :max ký tự.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => [
                    'code' => 'validation_failed',
                    'message' => $validator->errors()->first(),
                    'fields' => $validator->errors()->toArray(),
                ],
            ], 422, $rateLimitHeaders);
        }

        $result = $this->scamLookup->lookup(trim($validator->validated()['q']));

        return response()->json($result->toPublicArray(), 200, $rateLimitHeaders);
    }

    private function error(string $code, string $message, int $status): JsonResponse
    {
        return response()->json(['error' => ['code' => $code, 'message' => $message]], $status);
    }
}
