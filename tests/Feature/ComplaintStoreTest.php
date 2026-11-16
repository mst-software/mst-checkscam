<?php

use App\Enums\ComplaintReason;
use App\Enums\ComplaintStatus;
use App\Models\Complaint;
use App\Models\Report;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('my_public');
    $this->withoutVite();
});

function validComplaintPayload(array $overrides = []): array
{
    return array_merge([
        'contact_name' => 'Nguyen Van A',
        'contact_email' => 'a@example.com',
        'contact_phone' => '0912345678',
        'reason' => 'not_scam',
        'message' => 'Giao dịch đã hoàn tất, tôi gửi kèm biên lai chuyển khoản.',
    ], $overrides);
}

it('shows the complaint form', function () {
    $this->get(route('complaints.create'))
        ->assertOk()
        ->assertSee('action="'.route('complaints.store').'"', false)
        ->assertSee(ComplaintReason::NotScam->label());
});

it('prefills the form with an approved report', function () {
    $report = Report::factory()->create(['status' => 'approved']);

    $this->get(route('complaints.create', ['report' => $report->slug]))
        ->assertOk()
        ->assertSee('name="report" value="'.$report->slug.'"', false);
});

it('does not prefill the form with an unapproved report', function () {
    $report = Report::factory()->create(['status' => 'pending']);

    $this->get(route('complaints.create', ['report' => $report->slug]))
        ->assertOk()
        ->assertDontSee('name="report"', false);
});

it('stores a pending complaint linked to the report', function () {
    $report = Report::factory()->create(['status' => 'approved']);

    $this->post(route('complaints.store'), validComplaintPayload(['report' => $report->slug]))
        ->assertRedirect(route('complaints.create').'#gui-khieu-nai')
        ->assertSessionHas('success');

    $complaint = Complaint::sole();

    expect($complaint->report_id)->toBe($report->id)
        ->and($complaint->reason)->toBe(ComplaintReason::NotScam)
        ->and($complaint->status)->toBe(ComplaintStatus::Pending)
        ->and($complaint->ip_address)->toBe('127.0.0.1')
        ->and($complaint->evidence_images)->toBeNull();
});

it('stores a complaint without a report', function () {
    $this->post(route('complaints.store'), validComplaintPayload())->assertSessionHasNoErrors();

    expect(Complaint::sole()->report_id)->toBeNull();
});

it('validates the complaint fields', function (array $overrides, string $field) {
    $this->post(route('complaints.store'), validComplaintPayload($overrides))
        ->assertSessionHasErrors($field);

    expect(Complaint::count())->toBe(0);
})->with([
    'missing name' => [['contact_name' => ''], 'contact_name'],
    'missing email' => [['contact_email' => ''], 'contact_email'],
    'invalid email' => [['contact_email' => 'not-an-email'], 'contact_email'],
    'phone too long' => [['contact_phone' => str_repeat('1', 21)], 'contact_phone'],
    'missing reason' => [['reason' => ''], 'reason'],
    'unknown reason' => [['reason' => 'angry'], 'reason'],
    'message too short' => [['message' => 'quá ngắn'], 'message'],
    'unknown report' => [['report' => 'does-not-exist'], 'report'],
    'non image upload' => [['evidence_images' => [UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf')]], 'evidence_images.0'],
    'too many images' => [['evidence_images' => array_fill(0, 6, UploadedFile::fake()->image('x.jpg'))], 'evidence_images'],
]);

it('rejects a complaint about an unapproved report', function () {
    $report = Report::factory()->create(['status' => 'rejected']);

    $this->post(route('complaints.store'), validComplaintPayload(['report' => $report->slug]))
        ->assertSessionHasErrors('report');
});

it('silently drops submissions that fill the honeypot', function () {
    $this->post(route('complaints.store'), validComplaintPayload(['website' => 'http://spam.example']))
        ->assertRedirect(route('complaints.create').'#gui-khieu-nai')
        ->assertSessionHas('success');

    expect(Complaint::count())->toBe(0);
});

it('limits each ip to three complaints per day', function () {
    Complaint::factory()->count(3)->create(['ip_address' => '127.0.0.1']);

    $this->post(route('complaints.store'), validComplaintPayload())
        ->assertSessionHas('error');

    expect(Complaint::count())->toBe(3);
});

it('ignores complaints older than a day and from other ips for the rate limit', function () {
    Complaint::factory()->count(3)->create(['ip_address' => '127.0.0.1', 'created_at' => now()->subDays(2)]);
    Complaint::factory()->count(3)->create(['ip_address' => '10.0.0.1']);

    $this->post(route('complaints.store'), validComplaintPayload())
        ->assertSessionHasNoErrors()
        ->assertSessionMissing('error');

    expect(Complaint::count())->toBe(7);
});

it('uploads evidence images to the complaints folder', function () {
    $this->post(route('complaints.store'), validComplaintPayload([
        'evidence_images' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.png')],
    ]))->assertSessionHasNoErrors();

    $paths = Complaint::sole()->evidence_images;

    expect($paths)->toHaveCount(2);
    foreach ($paths as $path) {
        expect($path)->toStartWith('complaints/');
        Storage::disk('my_public')->assertExists($path);
    }
});

it('keeps the complaint when its report is deleted', function () {
    $report = Report::factory()->create(['status' => 'approved']);
    $complaint = Complaint::factory()->create(['report_id' => $report->id]);

    $report->delete();

    expect($complaint->fresh()->report_id)->toBeNull();
});

it('links the report page to the prefilled complaint form', function () {
    $report = Report::factory()->create(['status' => 'approved']);

    $this->get(route('scammer.show', $report->slug))
        ->assertOk()
        ->assertSee(route('complaints.create', ['report' => $report->slug]), false);
});
