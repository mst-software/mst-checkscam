<?php

use App\Enums\ScamType;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('my_public');
});

function validReportPayload(array $overrides = []): array
{
    return array_merge([
        'type' => 'account',
        'target_id' => '0912345678',
        'target_name' => 'Nguyen Van A',
        'description' => str_repeat('Đối tượng nhận tiền rồi chặn liên lạc. ', 3),
        'reporter_name' => 'Tran B',
        'reporter_contact' => '0987654321',
        'evidence_images' => [UploadedFile::fake()->image('proof.jpg')],
    ], $overrides);
}

it('stores the scam type submitted with a report', function () {
    $this->post(route('report.store'), validReportPayload(['scam_type' => 'investment']))
        ->assertRedirect(route('home'))
        ->assertSessionHasNoErrors();

    expect(Report::sole()->scam_type)->toBe(ScamType::Investment);
});

it('leaves the scam type empty when it is not submitted', function () {
    $this->post(route('report.store'), validReportPayload())->assertSessionHasNoErrors();

    expect(Report::sole()->scam_type)->toBeNull();
});

it('rejects an unknown scam type', function () {
    $this->post(route('report.store'), validReportPayload(['scam_type' => 'pyramid']))
        ->assertSessionHasErrors(['scam_type' => 'Hình thức lừa đảo không hợp lệ.']);

    expect(Report::count())->toBe(0);
});

it('filters reports by scam type with the ofScamType scope', function () {
    Report::factory()->create(['scam_type' => ScamType::Romance]);
    Report::factory()->count(2)->create(['scam_type' => ScamType::Loan]);
    Report::factory()->create(['scam_type' => null]);

    expect(Report::query()->ofScamType(ScamType::Loan)->count())->toBe(2);
});

describe('admin', function () {
    beforeEach(function () {
        $this->admin = User::factory()->create(['role' => 'admin', 'status' => 1]);
    });

    it('filters the report list by scam type', function () {
        Report::factory()->create(['target_id' => 'ROMANCE-TARGET', 'scam_type' => ScamType::Romance]);
        Report::factory()->create(['target_id' => 'LOAN-TARGET', 'scam_type' => ScamType::Loan]);

        $this->actingAs($this->admin)
            ->get(route('admin.reports.index', ['scam_type' => 'loan']))
            ->assertOk()
            ->assertSee('LOAN-TARGET')
            ->assertDontSee('ROMANCE-TARGET');
    });

    it('ignores an unknown scam type filter', function () {
        Report::factory()->create(['target_id' => 'ROMANCE-TARGET', 'scam_type' => ScamType::Romance]);

        $this->actingAs($this->admin)
            ->get(route('admin.reports.index', ['scam_type' => 'nope']))
            ->assertOk()
            ->assertSee('ROMANCE-TARGET');
    });

    it('shows the scam type label on the report detail page', function () {
        $report = Report::factory()->create(['scam_type' => ScamType::JobOffer]);

        $this->actingAs($this->admin)
            ->get(route('admin.reports.detail', $report->id))
            ->assertOk()
            ->assertSee(ScamType::JobOffer->label());
    });

    it('shows one scam type row with the legacy category as a hint when unclassified', function () {
        $legacy = Report::factory()->create(['scam_type' => null, 'category' => 'Lừa đảo cọc']);
        $classified = Report::factory()->create(['scam_type' => ScamType::JobOffer, 'category' => 'Lừa đảo cọc']);

        $this->actingAs($this->admin)
            ->get(route('admin.reports.detail', $legacy->id))
            ->assertOk()
            ->assertDontSee('Phân loại lừa đảo')
            ->assertSeeInOrder(['Hình thức lừa đảo', 'Chưa phân loại', '(cũ: Lừa đảo cọc)']);

        $this->actingAs($this->admin)
            ->get(route('admin.reports.detail', $classified->id))
            ->assertOk()
            ->assertSee(ScamType::JobOffer->label())
            ->assertDontSee('(cũ: Lừa đảo cọc)');
    });

    it('updates the scam type of a report', function () {
        $report = Report::factory()->create(['scam_type' => null, 'status' => 'pending']);

        $this->actingAs($this->admin)
            ->put(route('admin.reports.update', $report->id), [
                'type' => $report->type,
                'target_id' => $report->target_id,
                'description' => str_repeat('Mô tả chi tiết hành vi lừa đảo. ', 3),
                'status' => 'pending',
                'scam_type' => 'impersonation',
            ])
            ->assertSessionHasNoErrors();

        expect($report->fresh()->scam_type)->toBe(ScamType::Impersonation);
    });

    it('rejects an unknown scam type on update', function () {
        $report = Report::factory()->create(['status' => 'pending']);

        $this->actingAs($this->admin)
            ->put(route('admin.reports.update', $report->id), [
                'type' => $report->type,
                'target_id' => $report->target_id,
                'description' => str_repeat('Mô tả chi tiết hành vi lừa đảo. ', 3),
                'status' => 'pending',
                'scam_type' => 'pyramid',
            ])
            ->assertSessionHasErrors('scam_type');
    });
});
