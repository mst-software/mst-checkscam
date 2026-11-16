<?php

use App\Enums\ScamType;
use App\Models\Report;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('my_public');
    $this->withoutVite();
});

function reportFormPayload(array $overrides = []): array
{
    return array_merge([
        'type' => 'account',
        'target_id' => '0912345678',
        'target_name' => 'Nguyen Van A',
        'target_bank' => 'Vietcombank',
        'description' => str_repeat('Đối tượng nhận tiền cọc rồi chặn liên lạc. ', 3),
        'reporter_name' => 'Tran B',
        'reporter_contact' => '0987654321',
        'evidence_images' => [UploadedFile::fake()->image('proof.jpg')],
    ], $overrides);
}

function ajaxHeaders(): array
{
    return ['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json'];
}

it('renders the four step panels with the stepper on step one', function () {
    $this->get(route('reports'))
        ->assertOk()
        ->assertSee('action="'.route('report.store').'"', false)
        ->assertSee('data-current-step="1"', false)
        ->assertSee('data-step-panel="1"', false)
        ->assertSee('data-step-panel="2"', false)
        ->assertSee('data-step-panel="3"', false)
        ->assertSee('data-step-panel="4"', false)
        ->assertSee('aria-current="step"', false)
        ->assertSeeInOrder(['Đối tượng', 'Hình thức lừa đảo', 'Nội dung &amp; bằng chứng', 'Người báo cáo'], false)
        ->assertSee('Gửi tố cáo')
        ->assertSee('Trước khi gửi');
});

it('keeps every field name of the report endpoint', function () {
    $response = $this->get(route('reports'))->assertOk();

    foreach (['_token', 'type', 'target_id', 'target_name', 'target_bank', 'scam_type', 'category', 'damage_amount', 'description', 'evidence_images[]', 'reporter_name', 'reporter_contact'] as $name) {
        $response->assertSee('name="'.$name.'"', false);
    }
});

it('lists every scam type with its vietnamese label', function () {
    $response = $this->get(route('reports'))->assertOk();

    foreach (ScamType::cases() as $scamType) {
        $response->assertSee('value="'.$scamType->value.'"', false)
            ->assertSee($scamType->label());
    }
});

it('shows the daily submission limit notice', function () {
    $this->get(route('reports'))
        ->assertOk()
        ->assertSee('mỗi địa chỉ IP được gửi tối đa 3 tố cáo trong 24 giờ');
});

it('creates a pending report with the scam type over ajax', function () {
    $this->post(route('report.store'), reportFormPayload([
        'scam_type' => 'fake_shop',
        'category' => ScamType::FakeShop->label(),
        'damage_amount' => '1500000',
    ]), ajaxHeaders())
        ->assertOk()
        ->assertJson(['redirect' => route('home')]);

    $report = Report::sole();

    expect($report->status)->toBe('pending')
        ->and($report->scam_type)->toBe(ScamType::FakeShop)
        ->and($report->category)->toBe(ScamType::FakeShop->label())
        ->and($report->target_bank)->toBe('Vietcombank')
        ->and((float) $report->damage_amount)->toBe(1500000.0);
});

it('rejects an unknown scam type over ajax', function () {
    $this->post(route('report.store'), reportFormPayload(['scam_type' => 'pyramid']), ajaxHeaders())
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['scam_type' => 'Hình thức lừa đảo không hợp lệ.']);

    expect(Report::count())->toBe(0);
});

it('stores every uploaded evidence image', function () {
    $this->post(route('report.store'), reportFormPayload([
        'evidence_images' => [
            UploadedFile::fake()->image('bill.jpg'),
            UploadedFile::fake()->image('chat.png'),
            UploadedFile::fake()->image('profile.gif'),
        ],
    ]), ajaxHeaders())->assertOk();

    $paths = Report::sole()->evidence_images;

    expect($paths)->toHaveCount(3);
    foreach ($paths as $path) {
        expect($path)->toStartWith('reports/');
        Storage::disk('my_public')->assertExists($path);
    }
});

it('rejects evidence images larger than five megabytes', function () {
    $this->post(route('report.store'), reportFormPayload([
        'evidence_images' => [UploadedFile::fake()->image('huge.jpg')->size(6000)],
    ]), ajaxHeaders())
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['evidence_images.0' => 'Mỗi hình ảnh không được vượt quá 5MB.']);

    expect(Report::count())->toBe(0);
});

describe('daily limit', function () {
    beforeEach(function () {
        Report::factory()->count(3)->create(['ip_address' => '127.0.0.1', 'created_at' => now()->subHour()]);
    });

    it('returns a general error over ajax', function () {
        $this->post(route('report.store'), reportFormPayload(), ajaxHeaders())
            ->assertUnprocessable()
            ->assertJsonPath('errors.general.0', 'Bạn đã gửi quá nhiều lần hôm nay. Vui lòng thử lại vào ngày mai!');

        expect(Report::count())->toBe(3);
    });

    it('shows a friendly message on the form after a regular post', function () {
        $this->from(route('reports'))
            ->followingRedirects()
            ->post(route('report.store'), reportFormPayload())
            ->assertOk()
            ->assertSee('Bạn đã gửi quá nhiều lần hôm nay.')
            ->assertSee('vui lòng quay lại sau');

        expect(Report::count())->toBe(3);
    });
});

describe('server validation without javascript', function () {
    it('preserves old input and reopens the step with the error', function () {
        $response = $this->from(route('reports'))
            ->followingRedirects()
            ->post(route('report.store'), reportFormPayload([
                'type' => 'website',
                'target_id' => 'https://lua-dao.example',
                'target_name' => 'Shop Gia Re',
                'scam_type' => 'investment',
                'damage_amount' => '250000',
                'description' => 'Quá ngắn',
            ]))
            ->assertOk()
            ->assertSee('data-current-step="3"', false)
            ->assertSee('value="https://lua-dao.example"', false)
            ->assertSee('value="Shop Gia Re"', false)
            ->assertSee('value="250000"', false)
            ->assertSee('Vui lòng mô tả hành vi lừa đảo với ít nhất 50 ký tự.');

        $html = $response->getContent();

        expect($html)
            ->toMatch('/value="website"[^>]*\schecked/')
            ->toMatch('/value="investment"[^>]*\schecked/')
            ->toMatch('/id="description"[^>]*aria-invalid="true"/')
            ->not->toMatch('/value="account"[^>]*\schecked/')
            ->and(Report::count())->toBe(0);
    });

    it('reopens the reporter step when only a reporter field fails', function () {
        $this->from(route('reports'))
            ->followingRedirects()
            ->post(route('report.store'), reportFormPayload(['reporter_contact' => str_repeat('1', 256)]))
            ->assertOk()
            ->assertSee('data-current-step="4"', false)
            ->assertSee('value="Tran B"', false);
    });
});
