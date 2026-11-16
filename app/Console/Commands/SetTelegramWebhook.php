<?php

namespace App\Console\Commands;

use App\Services\TelegramBotService;
use Illuminate\Console\Command;

class SetTelegramWebhook extends Command
{
    protected $signature = 'telegram:set-webhook {--url= : Public base URL (defaults to APP_URL)}';

    protected $description = 'Register this app\'s Telegram webhook URL with the Bot API';

    public function handle(TelegramBotService $telegram): int
    {
        $secret = (string) config('services.telegram.webhook_secret');

        if (! config('services.telegram.bot_token') || $secret === '') {
            $this->error('Set TELEGRAM_BOT_TOKEN and TELEGRAM_WEBHOOK_SECRET first.');

            return self::FAILURE;
        }

        $path = route('telegram.webhook', ['secret' => $secret], absolute: false);
        $baseUrl = rtrim((string) ($this->option('url') ?: config('app.url')), '/');
        $url = $baseUrl.$path;

        if (! str_starts_with($url, 'https://')) {
            $this->error('Telegram requires an https:// webhook URL. Pass --url=https://your-domain.');

            return self::FAILURE;
        }

        $response = $telegram->setWebhook($url);

        if (! $response->successful() || ! $response->json('ok')) {
            $this->error('Telegram rejected the webhook: '.($response->json('description') ?? 'HTTP '.$response->status()));

            return self::FAILURE;
        }

        $this->info('Webhook registered: '.$baseUrl.'/telegram/webhook/***');

        return self::SUCCESS;
    }
}
