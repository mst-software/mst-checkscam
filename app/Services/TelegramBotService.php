<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Thin client for the Telegram Bot API. Never log the request URL: it contains the bot token.
 */
class TelegramBotService
{
    public function sendMessage(int|string $chatId, string $html): bool
    {
        try {
            $response = $this->call('sendMessage', [
                'chat_id' => $chatId,
                'text' => $html,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => true,
            ]);
        } catch (ConnectionException) {
            Log::warning('Telegram sendMessage could not connect', ['chat_id' => $chatId]);

            return false;
        }

        if (! $response->successful()) {
            Log::warning('Telegram sendMessage failed', ['status' => $response->status(), 'chat_id' => $chatId]);
        }

        return $response->successful();
    }

    public function setWebhook(string $url): Response
    {
        return $this->call('setWebhook', [
            'url' => $url,
            'allowed_updates' => ['message'],
            'drop_pending_updates' => true,
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function call(string $method, array $payload): Response
    {
        $token = config('services.telegram.bot_token');

        if (! $token) {
            throw new RuntimeException('TELEGRAM_BOT_TOKEN is not configured.');
        }

        return Http::timeout(10)->asJson()->post("https://api.telegram.org/bot{$token}/{$method}", $payload);
    }
}
