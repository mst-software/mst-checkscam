<?php

use App\Enums\ChannelType;
use App\Enums\ScamType;
use App\Mail\ReportModeratedMail;
use App\Models\Insurance;
use App\Models\InsuranceChannel;
use App\Models\Report;
use App\Models\User;
use App\Services\ScamLookupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

const BACK_EMAIL = 'nguoi.mua.rieng@example.com';
const BACK_SOCIALS = 'Zalo 0977888999, facebook.com/doituong.rieng';

beforeEach(function () {
    Storage::fake('my_public');
    $this->withoutVite();
});

function backReportPayload(array $overrides = []): array
{
    return array_merge([
        'type' => 'back',
        'target_id' => '0934567890',
        'target_name' => 'Nguoi Ban Acc',
        'target_socials' => '19034567890123',
        'target_bank' => 'Techcombank',
        'sold_account' => 'Liên Quân Mobile - nick ThanhLongVIP99',
        'description' => 'Mua acc 2 triệu, hôm sau người bán khôi phục mật khẩu lấy lại acc.',
        'damage_amount' => '2000000',
        'reporter_contact' => '0987654321',
        'reporter_email' => BACK_EMAIL,
        'reporter_role' => 'victim',
        'evidence_images' => [UploadedFile::fake()->image('bill.jpg')],
    ], $overrides);
}

describe('store', function () {
    it('stores a back report with the sold account and the forced scam type', function () {
        $this->post(route('report.store'), backReportPayload(['scam_type' => 'investment']))
            ->assertRedirect(route('home'))
            ->assertSessionHasNoErrors();

        $report = Report::sole();

        expect($report->type)->toBe('back')
            ->and($report->typeLabel())->toBe('Acc bị back')
            ->and($report->scam_type)->toBe(ScamType::AccountBack)
            ->and($report->category)->toBe(ScamType::AccountBack->label())
            ->and($report->sold_account)->toBe('Liên Quân Mobile - nick ThanhLongVIP99')
            ->and($report->target_socials)->toBe('19034567890123')
            ->and($report->target_bank)->toBe('Techcombank')
            ->and($report->status)->toBe('pending');
    });

    it('requires the seller contact and the damage amount like any report', function (string $field) {
        $this->post(route('report.store'), backReportPayload([$field => '']))->assertSessionHasErrors($field);

        expect(Report::count())->toBe(0);
    })->with(['target_id', 'damage_amount', 'evidence_images']);
});

describe('public page', function () {
    it('uses the normal scam wording and masks the sold account and channels', function () {
        $report = Report::factory()->back()->create([
            'status' => 'approved',
            'target_socials' => BACK_SOCIALS,
            'reporter_email' => BACK_EMAIL,
            'reporter_role' => 'proxy',
        ]);

        $html = $this->get(route('scammer.show', $report->slug))
            ->assertOk()
            ->assertSee('Bị tố cáo lừa đảo')
            ->assertSee('Cảnh báo lừa đảo')
            ->assertSee('Acc bị back')
            ->assertSee(ScamType::AccountBack->label())
            ->assertSee('Tài khoản đã mua')
            ->assertSee('Liên Quân Mobile - nick T.')
            ->assertSee('Kênh liên hệ khác')
            ->assertSee('097***999')
            ->assertSee('093***890')
            ->assertDontSee('ThanhLongVIP99')
            ->assertDontSee('0977888999')
            ->assertDontSee('doituong.rieng')
            ->assertDontSee('0934567890')
            ->assertDontSee(BACK_EMAIL)
            ->assertDontSee('Đăng hộ')
            ->getContent();

        expect(substr($html, 0, strpos($html, '</head>')))->toContain('Cảnh báo 093***890')
            ->not->toContain('đừng chuyển tiền');
    });

    it('redacts the secondary channels inside the description', function () {
        $report = Report::factory()->create([
            'status' => 'approved',
            'type' => 'account',
            'target_socials' => BACK_SOCIALS,
            'description' => 'Nó nhắn Zalo 0977888999 và facebook.com/doituong.rieng để hẹn giao dịch.',
        ]);

        $this->get(route('scammer.show', $report->slug))
            ->assertOk()
            ->assertSee('097***999')
            ->assertDontSee('0977888999')
            ->assertDontSee('doituong.rieng');
    });

    it('counts back reports together with other reports of the same target', function () {
        $back = Report::factory()->back()->create(['status' => 'approved', 'target_id' => '0934567890']);
        Report::factory()->create(['status' => 'approved', 'type' => 'account', 'target_id' => '0934567890']);

        $this->get(route('scammer.show', $back->slug))
            ->assertOk()
            ->assertSeeInOrder(['Tố cáo', '2']);

        $result = app(ScamLookupService::class)->lookup('0934567890');

        expect($result->totalReports)->toBe(2)
            ->and($result->risk)->not->toBe(ScamLookupService::RISK_LOW);
    });

    it('lists back reports in the warnings, home feed and search with the type label', function () {
        Report::factory()->back()->create(['status' => 'approved', 'approved_at' => now()]);

        $this->get(route('warnings.index', ['type' => 'phone']))
            ->assertOk()
            ->assertSee('093***890');

        $this->get('/')->assertOk()->assertSee('Acc bị back');
    });
});

describe('admin', function () {
    beforeEach(function () {
        Queue::fake();
        Mail::fake();
        $this->moderator = User::factory()->create(['role' => 'moderator', 'status' => 1]);
    });

    it('filters the report list by the back type and badges it', function () {
        Report::factory()->back()->create(['target_id' => 'BACK-TARGET']);
        Report::factory()->create(['type' => 'account', 'target_id' => 'ACCOUNT-TARGET']);

        $this->actingAs($this->moderator)
            ->get(route('admin.reports.index', ['type' => 'back']))
            ->assertOk()
            ->assertSee('<option value="back"', false)
            ->assertSee('BACK-TARGET')
            ->assertSee('Acc bị back')
            ->assertDontSee('ACCOUNT-TARGET');
    });

    it('shows the private fields on the detail page', function () {
        $report = Report::factory()->back()->create([
            'target_socials' => BACK_SOCIALS,
            'reporter_email' => BACK_EMAIL,
            'reporter_role' => 'proxy',
        ]);

        $this->actingAs($this->moderator)
            ->get(route('admin.reports.detail', $report->id))
            ->assertOk()
            ->assertSee(BACK_EMAIL)
            ->assertSee(BACK_SOCIALS)
            ->assertSee('Liên Quân Mobile - nick ThanhLongVIP99')
            ->assertSee('Đăng hộ')
            ->assertSee('Acc bị back');
    });

    it('emails the reporter when the report is approved', function () {
        $report = Report::factory()->back()->create(['status' => 'pending', 'reporter_email' => BACK_EMAIL]);

        $this->actingAs($this->moderator)->post(route('admin.reports.approve', $report->id))->assertRedirect();

        Mail::assertQueued(ReportModeratedMail::class, fn (ReportModeratedMail $mail) => $mail->hasTo(BACK_EMAIL)
            && $mail->report->is($report));
    });

    it('emails the reporter when the report is rejected', function () {
        $report = Report::factory()->create(['status' => 'pending', 'reporter_email' => BACK_EMAIL]);

        $this->actingAs($this->moderator)
            ->post(route('admin.reports.reject', $report->id), ['rejection_reason' => 'Thiếu bill chuyển khoản'])
            ->assertRedirect();

        Mail::assertQueued(ReportModeratedMail::class, fn (ReportModeratedMail $mail) => $mail->hasTo(BACK_EMAIL));
    });

    it('emails the reporter when an update changes the status', function () {
        $report = Report::factory()->create(['status' => 'pending', 'reporter_email' => BACK_EMAIL]);

        $this->actingAs($this->moderator)->put(route('admin.reports.update', $report->id), [
            'type' => 'back',
            'target_id' => $report->target_id,
            'description' => str_repeat('Mô tả chi tiết vụ việc. ', 3),
            'status' => 'approved',
        ])->assertSessionHasNoErrors();

        expect($report->fresh()->type)->toBe('back');
        Mail::assertQueued(ReportModeratedMail::class);
    });

    it('sends nothing for older reports without an email', function () {
        $report = Report::factory()->create(['status' => 'pending', 'reporter_email' => null]);

        $this->actingAs($this->moderator)->post(route('admin.reports.approve', $report->id))->assertRedirect();

        Mail::assertNothingQueued();
    });

    it('suspends a partner whose official channel is the seller, like any approved report', function () {
        $partner = Insurance::factory()->trusted()->create();
        InsuranceChannel::factory()->for($partner)->ofType(ChannelType::Phone, '0934567890')->create();
        $report = Report::factory()->back()->create(['status' => 'pending', 'target_id' => '0934567890']);

        $this->actingAs($this->moderator)->post(route('admin.reports.approve', $report->id))->assertRedirect();

        expect($partner->fresh()->isSuspended())->toBeTrue();
    });
});

describe('moderation mail', function () {
    it('shows the masked target and the reason when rejected', function () {
        $report = Report::factory()->create([
            'status' => 'rejected',
            'type' => 'account',
            'target_id' => '0912345678',
            'rejection_reason' => 'Thiếu bill chuyển khoản',
        ]);

        $mail = new ReportModeratedMail($report);

        $mail->assertHasSubject('Tố cáo của bạn chưa được duyệt');
        $mail->assertSeeInHtml('091***678');
        $mail->assertSeeInHtml('Thiếu bill chuyển khoản');
        $mail->assertDontSeeInHtml('0912345678');
    });

    it('links to the public warning when approved', function () {
        $report = Report::factory()->back()->create(['status' => 'approved']);

        $mail = new ReportModeratedMail($report);

        $mail->assertHasSubject('Tố cáo của bạn đã được duyệt');
        $mail->assertSeeInHtml(route('scammer.show', $report->slug));
    });
});
