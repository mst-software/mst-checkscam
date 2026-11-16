<?php

use App\Enums\ChannelType;
use App\Enums\InsuranceStatusEventType;
use App\Helpers\ConfigHelper;
use App\Models\Insurance;
use App\Models\InsuranceChannel;
use App\Models\InsuranceStatusEvent;
use App\Models\Report;
use App\Models\User;
use App\Services\InsuranceSuspensionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->suspensions = app(InsuranceSuspensionService::class);
    $this->admin = User::factory()->create(['role' => 'admin', 'status' => 1]);
});

test('suspending and reinstating write audit rows and change trusted standing', function () {
    $insurance = Insurance::factory()->trusted()->create();

    expect($this->suspensions->suspend($insurance, 'Kiểm tra', $this->admin))->toBeTrue()
        ->and($this->suspensions->suspend($insurance, 'Lần hai', $this->admin))->toBeFalse()
        ->and($insurance->isTrusted())->toBeFalse()
        ->and($insurance->status_label)->toBe('Tạm đình chỉ')
        ->and(Insurance::trusted()->pluck('id')->all())->not->toContain($insurance->id);

    expect($this->suspensions->reinstate($insurance, $this->admin, 'Đã xác minh'))->toBeTrue()
        ->and($this->suspensions->reinstate($insurance, $this->admin))->toBeFalse()
        ->and($insurance->fresh()->isTrusted())->toBeTrue()
        ->and($insurance->statusEvents()->pluck('type')->all())
        ->toBe([InsuranceStatusEventType::Suspended, InsuranceStatusEventType::Reinstated]);
});

test('the sync command does nothing while the minimum balance is disabled', function () {
    Insurance::factory()->trusted()->create(['amount' => 1000]);

    $this->artisan('insurance:sync-status')->assertSuccessful();

    expect(Insurance::whereNotNull('suspended_at')->count())->toBe(0);
});

test('the sync command suspends partners below the minimum balance once', function () {
    ConfigHelper::setConfig('insurance_min_balance', '5000000');
    $low = Insurance::factory()->trusted()->create(['amount' => 4999999]);
    $ok = Insurance::factory()->trusted()->create(['amount' => 5000000]);

    $this->artisan('insurance:sync-status')
        ->expectsOutputToContain('Suspended for low balance: 1')
        ->assertSuccessful();
    $this->artisan('insurance:sync-status')
        ->expectsOutputToContain('Suspended for low balance: 0')
        ->assertSuccessful();

    expect($low->fresh()->suspension_reason)->toBe(InsuranceSuspensionService::REASON_LOW_BALANCE)
        ->and($ok->fresh()->isSuspended())->toBeFalse()
        ->and(InsuranceStatusEvent::where('type', InsuranceStatusEventType::Suspended)->count())->toBe(1);
});

test('the sync command records each expiry once', function () {
    $expired = Insurance::factory()->trusted()->expired()->create();
    Insurance::factory()->trusted()->create();

    $this->artisan('insurance:sync-status')->expectsOutputToContain('New expiry notices: 1')->assertSuccessful();
    $this->artisan('insurance:sync-status')->expectsOutputToContain('New expiry notices: 0')->assertSuccessful();

    expect($expired->statusEvents()->sole()->type)->toBe(InsuranceStatusEventType::ExpiredNotice)
        ->and($expired->fresh()->isSuspended())->toBeFalse();
});

test('the sync command is scheduled daily', function () {
    $this->artisan('schedule:list')->expectsOutputToContain('insurance:sync-status')->assertSuccessful();
});

test('approving a report that targets an official channel suspends the trusted partner', function () {
    Queue::fake();
    $partner = Insurance::factory()->trusted()->create(['full_name' => 'GDV Hùng']);
    InsuranceChannel::factory()->for($partner)->ofType(ChannelType::Phone, '0912345678')->create();
    $report = Report::factory()->create(['status' => 'pending', 'target_id' => '+84 912 345 678']);

    $this->actingAs($this->admin)
        ->post(route('admin.reports.approve', $report->id))
        ->assertRedirect()
        ->assertSessionHas('success', fn (string $message) => str_contains($message, 'GDV Hùng'));

    expect($partner->fresh()->suspension_reason)->toBe(InsuranceSuspensionService::REASON_APPROVED_REPORT)
        ->and($partner->statusEvents()->sole()->actor_id)->toBe($this->admin->id);
});

test('first approval through the update form also suspends, unofficial channels do not', function () {
    Queue::fake();
    $official = Insurance::factory()->trusted()->create();
    InsuranceChannel::factory()->for($official)->ofType(ChannelType::BankAccount, '1903 4567 8901 23')->create();
    $unofficial = Insurance::factory()->trusted()->create();
    InsuranceChannel::factory()->for($unofficial)->ofType(ChannelType::BankAccount, '19034567890123')->unofficial()->create();
    $report = Report::factory()->create(['status' => 'pending', 'target_id' => '19034567890123']);

    $this->actingAs($this->admin)->put(route('admin.reports.update', $report->id), [
        'type' => $report->type,
        'target_id' => $report->target_id,
        'target_name' => $report->target_name,
        'description' => str_repeat('Mô tả chi tiết vụ việc lừa đảo. ', 3),
        'status' => 'approved',
    ])->assertRedirect();

    expect($official->fresh()->isSuspended())->toBeTrue()
        ->and($unofficial->fresh()->isSuspended())->toBeFalse();
});

test('approving a report with no matching channel suspends nobody', function () {
    Queue::fake();
    $partner = Insurance::factory()->trusted()->create();
    InsuranceChannel::factory()->for($partner)->ofType(ChannelType::Phone, '0987654321')->create();
    $report = Report::factory()->create(['status' => 'pending', 'target_id' => 'Nguyễn Văn A']);

    $this->actingAs($this->admin)
        ->post(route('admin.reports.approve', $report->id))
        ->assertRedirect()
        ->assertSessionHas('success', 'Báo cáo đã được duyệt thành công.');

    expect($partner->fresh()->isSuspended())->toBeFalse();
});
