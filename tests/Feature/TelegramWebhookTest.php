<?php

use App\Enums\ChannelType;
use App\Models\Insurance;
use App\Models\InsuranceChannel;
use App\Models\Report;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->registerMysqlCompatibleSqliteFunctions();
    config([
        'services.telegram.bot_token' => 'test-token',
        'services.telegram.webhook_secret' => 'hook-secret',
        'services.telegram.chat_rate_limit_per_minute' => 10,
    ]);
    RateLimiter::clear('telegram-chat:4242');
    RateLimiter::clear('telegram-chat-warned:4242');
    Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
});

function telegramUpdate(string $text, int $chatId = 4242): array
{
    return ['update_id' => 1, 'message' => ['message_id' => 1, 'chat' => ['id' => $chatId, 'type' => 'private'], 'text' => $text]];
}

test('an invalid or unset secret returns 404 and sends nothing', function () {
    $this->postJson('/telegram/webhook/wrong', telegramUpdate('/start'))->assertNotFound();

    config(['services.telegram.webhook_secret' => '']);
    $this->postJson('/telegram/webhook/', telegramUpdate('/start'))->assertNotFound();
    $this->postJson('/telegram/webhook/anything', telegramUpdate('/start'))->assertNotFound();

    Http::assertNothingSent();
});

test('start and help reply with usage instructions', function (string $command) {
    $this->postJson('/telegram/webhook/hook-secret', telegramUpdate($command))->assertOk()->assertJson(['ok' => true]);

    Http::assertSent(fn (Request $request) => $request->url() === 'https://api.telegram.org/bottest-token/sendMessage'
        && $request['chat_id'] === 4242
        && $request['parse_mode'] === 'HTML'
        && str_contains($request['text'], '/check 0912345678'));
})->with(['/start', '/help', '/help@CheckScamBot']);

test('check command replies with risk, report count and masked names', function () {
    Report::factory()->count(3)->create([
        'status' => 'approved',
        'target_id' => '0912345678',
        'target_name' => 'Nguyễn Văn Đạt',
        'reporter_name' => 'Hidden Reporter',
    ]);

    $this->postJson('/telegram/webhook/hook-secret', telegramUpdate('/check +84912345678'))->assertOk();

    Http::assertSentCount(1);
    Http::assertSent(function (Request $request) {
        $text = $request['text'];

        return str_contains($text, 'Mức độ rủi ro: <b>CAO</b>')
            && str_contains($text, 'Số tố cáo đã duyệt: <b>3</b>')
            && str_contains($text, 'Nguyễn Văn Đ.')
            && ! str_contains($text, 'Nguyễn Văn Đạt')
            && ! str_contains($text, 'Hidden Reporter')
            && str_contains($text, e(route('search.index', ['q' => '+84912345678'])));
    });
});

test('plain text is looked up and html in the query is escaped', function () {
    $this->postJson('/telegram/webhook/hook-secret', telegramUpdate('<b>abc</b>'))->assertOk();

    Http::assertSent(fn (Request $request) => str_contains($request['text'], 'Mức độ rủi ro: <b>THẤP</b>')
        && str_contains($request['text'], '&lt;b&gt;abc&lt;/b&gt;')
        && str_contains($request['text'], 'Chưa có tố cáo nào khớp'));
});

test('the reply says whether the query is an official or lapsed intermediary channel', function () {
    $verified = Insurance::factory()->trusted()->create(['full_name' => 'GDV Hùng']);
    InsuranceChannel::factory()->for($verified)->ofType(ChannelType::Phone, '0911111111')->create();
    $lapsed = Insurance::factory()->trusted()->suspended()->create(['full_name' => 'GDV Nam']);
    InsuranceChannel::factory()->for($lapsed)->ofType(ChannelType::Phone, '0922222222')->create();

    $this->postJson('/telegram/webhook/hook-secret', telegramUpdate('/check 0911111111'))->assertOk();
    $this->postJson('/telegram/webhook/hook-secret', telegramUpdate('/check 0922222222'))->assertOk();
    $this->postJson('/telegram/webhook/hook-secret', telegramUpdate('/check 0933333333'))->assertOk();

    Http::assertSent(fn (Request $request) => str_contains($request['text'], '<b>Kênh GDV chính thức:</b> Đây là kênh chính thức của '.$verified->memberCode().' GDV Hùng'));
    Http::assertSent(fn (Request $request) => str_contains($request['text'], '<b>Lưu ý:</b> Kênh này thuộc '.$lapsed->memberCode().' GDV Nam')
        && str_contains($request['text'], 'tạm đình chỉ'));
    Http::assertSent(fn (Request $request) => str_contains($request['text'], '0933333333')
        && ! str_contains($request['text'], 'Kênh GDV chính thức')
        && ! str_contains($request['text'], 'Lưu ý:'));
});

test('check without an argument explains the syntax', function () {
    $this->postJson('/telegram/webhook/hook-secret', telegramUpdate('/check'))->assertOk();

    Http::assertSent(fn (Request $request) => str_contains($request['text'], 'Cú pháp'));
});

test('updates without text are acknowledged without replying', function () {
    $this->postJson('/telegram/webhook/hook-secret', ['update_id' => 2, 'edited_message' => ['text' => 'x']])
        ->assertOk();

    Http::assertNothingSent();
});

test('each chat is rate limited and warned only once', function () {
    config(['services.telegram.chat_rate_limit_per_minute' => 2]);

    foreach (range(1, 4) as $i) {
        $this->postJson('/telegram/webhook/hook-secret', telegramUpdate('/help'))->assertOk();
    }

    // 2 normal replies + 1 rate-limit warning; the 4th request is dropped silently.
    Http::assertSentCount(3);
    Http::assertSent(fn (Request $request) => str_contains($request['text'], 'quá nhiều yêu cầu'));

    RateLimiter::clear('telegram-chat:5151');
    $this->postJson('/telegram/webhook/hook-secret', telegramUpdate('/help', 5151))->assertOk();
    Http::assertSentCount(4);
});

test('set-webhook command registers the secret url with telegram', function () {
    config(['app.url' => 'https://checkscam.test']);

    $this->artisan('telegram:set-webhook')
        ->expectsOutputToContain('Webhook registered')
        ->assertSuccessful();

    Http::assertSent(fn (Request $request) => $request->url() === 'https://api.telegram.org/bottest-token/setWebhook'
        && $request['url'] === 'https://checkscam.test/telegram/webhook/hook-secret');
});

test('set-webhook refuses non https urls and missing config', function () {
    config(['app.url' => 'http://localhost']);
    $this->artisan('telegram:set-webhook')->assertFailed();

    config(['services.telegram.bot_token' => null]);
    $this->artisan('telegram:set-webhook', ['--url' => 'https://checkscam.test'])->assertFailed();

    Http::assertNothingSent();
});
