<?php

namespace App\Http\Controllers;

use App\Services\ScamLookupResult;
use App\Services\ScamLookupService;
use App\Services\TelegramBotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class TelegramWebhookController extends Controller
{
    private const RISK_LABELS = [
        ScamLookupService::RISK_LOW => 'THẤP',
        ScamLookupService::RISK_MEDIUM => 'TRUNG BÌNH',
        ScamLookupService::RISK_HIGH => 'CAO',
    ];

    private const TYPE_LABELS = [
        'phone' => 'Số điện thoại',
        'bank' => 'Số tài khoản',
        'facebook' => 'Facebook',
        'uuid' => 'Mã hồ sơ',
        'name' => 'Tên',
    ];

    public function __construct(
        private ScamLookupService $scamLookup,
        private TelegramBotService $telegram,
    ) {}

    public function handle(Request $request, string $secret): JsonResponse
    {
        $expectedSecret = (string) config('services.telegram.webhook_secret');
        abort_if($expectedSecret === '' || ! hash_equals($expectedSecret, $secret), 404);

        $chatId = $request->input('message.chat.id');
        $text = $request->input('message.text');

        // Telegram retries non-2xx responses, so anything we do not handle is acknowledged and ignored.
        if (! is_scalar($chatId) || ! is_string($text) || trim($text) === '') {
            return $this->ok();
        }

        $limiterKey = 'telegram-chat:'.$chatId;
        if (RateLimiter::tooManyAttempts($limiterKey, (int) config('services.telegram.chat_rate_limit_per_minute', 10))) {
            // Warn once per minute, then drop silently so a flooding chat cannot make the bot spam.
            if (RateLimiter::attempt('telegram-chat-warned:'.$chatId, 1, fn () => true, 60)) {
                $this->telegram->sendMessage($chatId, 'Bạn gửi quá nhiều yêu cầu. Vui lòng thử lại sau '.RateLimiter::availableIn($limiterKey).' giây.');
            }

            return $this->ok();
        }
        RateLimiter::hit($limiterKey, 60);

        $this->telegram->sendMessage($chatId, $this->replyFor(trim($text)));

        return $this->ok();
    }

    private function replyFor(string $text): string
    {
        if (! str_starts_with($text, '/')) {
            return $this->lookupReply($text);
        }

        [$command, $argument] = array_pad(preg_split('/\s+/', $text, 2), 2, '');
        $command = Str::before(mb_strtolower($command), '@');

        return match ($command) {
            '/check' => $argument === ''
                ? 'Cú pháp: <code>/check 0912345678</code> (SĐT, STK, link Facebook hoặc tên).'
                : $this->lookupReply(trim($argument)),
            default => $this->helpText(),
        };
    }

    private function lookupReply(string $query): string
    {
        if (mb_strlen($query) < 2 || mb_strlen($query) > 255) {
            return 'Nội dung tra cứu phải từ 2 đến 255 ký tự.';
        }

        return $this->formatResult($query, $this->scamLookup->lookup($query, reportLimit: 3));
    }

    private function formatResult(string $query, ScamLookupResult $result): string
    {
        $payload = $result->toPublicArray();
        $lines = [
            '<b>Kết quả kiểm tra:</b> <code>'.e($result->formattedQuery).'</code> ('.(self::TYPE_LABELS[$result->type] ?? $result->type).')',
            'Mức độ rủi ro: <b>'.self::RISK_LABELS[$result->risk].'</b>',
            'Số tố cáo đã duyệt: <b>'.$result->totalReports.'</b>',
        ];

        if ($result->totalReports === 0) {
            $lines[] = 'Chưa có tố cáo nào khớp. Hãy luôn cẩn trọng và dùng trung gian uy tín khi giao dịch.';
        }

        foreach ($payload['reports'] as $report) {
            $date = $report['reported_at'] ? date('d/m/Y', strtotime($report['reported_at'])) : '';
            $name = e($report['target_name_masked']);
            $lines[] = $report['url']
                ? '- <a href="'.e($report['url'])."\">{$name}</a> ({$date})"
                : "- {$name} ({$date})";
        }

        foreach (array_slice($payload['insurance'], 0, 3) as $insurance) {
            $label = 'Đối tác bảo hiểm: '.e($insurance['name']).' ('.e($insurance['status']).')';
            $lines[] = $insurance['url'] ? '<a href="'.e($insurance['url'])."\">{$label}</a>" : $label;
        }

        $lines[] = '';
        $lines[] = '<a href="'.e(route('search.index', ['q' => $query])).'">Xem chi tiết trên CheckScam</a>';

        return implode("\n", $lines);
    }

    private function helpText(): string
    {
        return implode("\n", [
            '<b>Bot tra cứu lừa đảo CheckScam</b>',
            'Gửi SĐT, số tài khoản, link Facebook hoặc tên để kiểm tra.',
            'Hoặc dùng lệnh: <code>/check 0912345678</code>',
            '/help - Xem hướng dẫn',
        ]);
    }

    private function ok(): JsonResponse
    {
        return response()->json(['ok' => true]);
    }
}
