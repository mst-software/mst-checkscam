<?php

use App\Enums\ClaimStatus;
use App\Mail\InsuranceClaimStatusChanged;
use App\Models\Insurance;
use App\Models\InsuranceClaim;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('my_public');
    Mail::fake();
    $this->withoutVite();
    $this->partner = Insurance::factory()->trusted()->create(['full_name' => 'GDV Minh']);
});

function validClaimPayload(Insurance $partner, array $overrides = []): array
{
    return array_merge([
        'partner' => $partner->slug,
        'claimant_name' => 'Trần Thị Bích',
        'contact_email' => 'bich.claimant@example.com',
        'contact_phone' => '0987001122',
        'amount_claimed' => '2.500.000',
        'description' => 'Tôi chuyển tiền cọc qua GDV nhưng bên bán không giao tài khoản như đã thỏa thuận.',
        'consent' => '1',
    ], $overrides);
}

it('shows the claim form with every partner to choose from', function () {
    $suspended = Insurance::factory()->suspended()->create(['full_name' => 'GDV Đình Chỉ']);
    Insurance::factory()->create(['status' => 0, 'full_name' => 'GDV Ẩn']);

    $this->get(route('insurance-claims.create'))
        ->assertOk()
        ->assertSee('action="'.route('insurance-claims.store').'"', false)
        ->assertSee('<option value="'.$this->partner->slug.'"', false)
        ->assertSee('<option value="'.$suspended->slug.'"', false)
        ->assertDontSee('GDV Ẩn')
        ->assertSee('Tôi cam kết thông tin')
        ->assertSee('Email và số điện thoại chỉ ban quản trị xem được, không hiển thị công khai.');
});

it('preselects the partner from the query string', function () {
    $this->get(route('insurance-claims.create', ['partner' => $this->partner->slug]))
        ->assertOk()
        ->assertSee('name="partner" value="'.$this->partner->slug.'"', false)
        ->assertSee('GDV Minh')
        ->assertDontSee('<select', false);
});

it('stores a submitted claim with its first event and emails the claimant', function () {
    $response = $this->post(route('insurance-claims.store'), validClaimPayload($this->partner));

    $claim = InsuranceClaim::sole();

    $response->assertRedirect(route('insurance-claims.create'))
        ->assertSessionHas('claim_submitted', ['reference' => 'YC-'.str_pad((string) $claim->id, 6, '0', STR_PAD_LEFT), 'partner' => 'GDV Minh']);

    expect($claim->insurance_id)->toBe($this->partner->id)
        ->and($claim->status)->toBe(ClaimStatus::Submitted)
        ->and($claim->amount_claimed)->toBe('2500000.00')
        ->and($claim->contact_email)->toBe('bich.claimant@example.com')
        ->and($claim->ip_address)->toBe('127.0.0.1')
        ->and($claim->evidence_paths)->toBeNull()
        ->and($claim->events)->toHaveCount(1)
        ->and($claim->events->first()->to_status)->toBe(ClaimStatus::Submitted);

    Mail::assertQueued(InsuranceClaimStatusChanged::class, fn ($mail) => $mail->hasTo('bich.claimant@example.com'));
});

it('shows the reference code and next steps after submitting', function () {
    $this->followingRedirects()
        ->post(route('insurance-claims.store'), validClaimPayload($this->partner))
        ->assertOk()
        ->assertSee('Đã gửi yêu cầu bồi thường')
        ->assertSee('YC-'.str_pad((string) InsuranceClaim::sole()->id, 6, '0', STR_PAD_LEFT))
        ->assertSee('Ban quản trị sẽ xem xét hồ sơ')
        ->assertSee('Mọi cập nhật về trạng thái yêu cầu được gửi tới email của bạn.')
        ->assertDontSee('bich.claimant@example.com');
});

it('accepts claims against suspended partners', function () {
    $suspended = Insurance::factory()->suspended()->create();

    $this->post(route('insurance-claims.store'), validClaimPayload($suspended))->assertSessionHasNoErrors();

    expect(InsuranceClaim::sole()->insurance_id)->toBe($suspended->id);
});

it('validates the claim fields', function (Closure $overrides, string $field) {
    $this->post(route('insurance-claims.store'), validClaimPayload($this->partner, $overrides($this->partner)))
        ->assertSessionHasErrors($field);

    expect(InsuranceClaim::count())->toBe(0);
    Mail::assertNothingQueued();
})->with([
    'missing partner' => [fn () => ['partner' => ''], 'partner'],
    'unknown partner' => [fn () => ['partner' => 'does-not-exist'], 'partner'],
    'hidden partner' => [fn () => ['partner' => Insurance::factory()->create(['status' => 0])->slug], 'partner'],
    'missing name' => [fn () => ['claimant_name' => ''], 'claimant_name'],
    'missing email' => [fn () => ['contact_email' => ''], 'contact_email'],
    'invalid email' => [fn () => ['contact_email' => 'not-an-email'], 'contact_email'],
    'phone too long' => [fn () => ['contact_phone' => str_repeat('1', 21)], 'contact_phone'],
    'missing amount' => [fn () => ['amount_claimed' => ''], 'amount_claimed'],
    'zero amount' => [fn () => ['amount_claimed' => '0'], 'amount_claimed'],
    'amount without digits' => [fn () => ['amount_claimed' => 'nhiều tiền'], 'amount_claimed'],
    'description too short' => [fn () => ['description' => 'quá ngắn'], 'description'],
    'no consent' => [fn () => ['consent' => null], 'consent'],
    'non image upload' => [fn () => ['evidence_images' => [UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf')]], 'evidence_images.0'],
    'image too large' => [fn () => ['evidence_images' => [UploadedFile::fake()->image('big.jpg')->size(5121)]], 'evidence_images.0'],
    'too many images' => [fn () => ['evidence_images' => array_fill(0, 6, UploadedFile::fake()->image('x.jpg'))], 'evidence_images'],
]);

it('silently drops submissions that fill the honeypot', function () {
    $this->post(route('insurance-claims.store'), validClaimPayload($this->partner, ['website' => 'http://spam.example']))
        ->assertRedirect(route('insurance-claims.create'))
        ->assertSessionHas('claim_submitted');

    expect(InsuranceClaim::count())->toBe(0);
    Mail::assertNothingQueued();
});

it('limits each ip to three claims per day', function () {
    InsuranceClaim::factory()->count(3)->for($this->partner)->create(['ip_address' => '127.0.0.1']);

    $this->post(route('insurance-claims.store'), validClaimPayload($this->partner))
        ->assertSessionHas('error');

    expect(InsuranceClaim::count())->toBe(3);
});

it('ignores older claims and other ips for the rate limit', function () {
    InsuranceClaim::factory()->count(3)->for($this->partner)->create(['ip_address' => '127.0.0.1', 'created_at' => now()->subDays(2)]);
    InsuranceClaim::factory()->count(3)->for($this->partner)->create(['ip_address' => '10.0.0.1']);

    $this->post(route('insurance-claims.store'), validClaimPayload($this->partner))
        ->assertSessionHasNoErrors()
        ->assertSessionMissing('error');

    expect(InsuranceClaim::count())->toBe(7);
});

it('stores evidence images in the insurance-claims folder', function () {
    $this->post(route('insurance-claims.store'), validClaimPayload($this->partner, [
        'evidence_images' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.png')],
    ]))->assertSessionHasNoErrors();

    $paths = InsuranceClaim::sole()->evidence_paths;

    expect($paths)->toHaveCount(2);
    foreach ($paths as $path) {
        expect($path)->toStartWith('insurance-claims/');
        Storage::disk('my_public')->assertExists($path);
    }
});

it('never shows claimant contact data or the ip on public pages', function () {
    $this->post(route('insurance-claims.store'), validClaimPayload($this->partner))->assertSessionHasNoErrors();
    $claim = InsuranceClaim::sole();
    // Decide the claim so it appears in the partner's public history.
    $claim->forceFill(['status' => ClaimStatus::Rejected, 'decided_at' => now(), 'ip_address' => '203.0.113.77'])->save();

    $pages = [
        route('insurances.frontend.show', $this->partner->slug),
        route('insurances.frontend.index'),
        route('insurance-claims.create'),
        route('intermediary.verify', ['q' => $this->partner->slug]),
        route('home'),
    ];

    foreach ($pages as $url) {
        $this->get($url)
            ->assertOk()
            ->assertDontSee('bich.claimant@example.com')
            ->assertDontSee('0987001122', false)
            ->assertDontSee('203.0.113.77')
            ->assertDontSee('Trần Thị Bích');
    }
});
