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
        'description' => 'Đối tượng nhận tiền cọc rồi chặn liên lạc.',
        'damage_amount' => '1500000',
        'reporter_name' => 'Tran B',
        'reporter_contact' => '0987654321',
        'reporter_email' => 'nan.nhan@example.com',
        'reporter_role' => 'victim',
        'evidence_images' => [UploadedFile::fake()->image('proof.jpg')],
    ], $overrides);
}

function ajaxHeaders(): array
{
    return ['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json'];
}

/**
 * The opening tag of the first element whose attributes match $pattern, whitespace-tolerant.
 */
function openingTag(string $html, string $pattern): string
{
    preg_match('/<[a-z]+\b[^>]*'.$pattern.'[^>]*>/is', $html, $match);

    return $match[0] ?? '';
}

describe('page', function () {
    it('renders one page with the three type tabs and no wizard', function () {
        $html = $this->get(route('reports'))
            ->assertOk()
            ->assertSee('action="'.route('report.store').'"', false)
            ->assertSee('role="tablist"', false)
            ->assertSeeInOrder(['STK scam', 'Web scam', 'Acc bị back'])
            ->assertSeeInOrder(['Đối tượng lừa đảo', 'Chuyện gì đã xảy ra?', 'Bằng chứng', 'Người gửi'])
            ->assertDontSee('data-step-panel', false)
            ->assertDontSee('data-next-btn', false)
            ->assertDontSee('Kiểm tra lại trước khi gửi')
            ->getContent();

        expect(substr_count($html, 'role="tab"'))->toBe(3);
    });

    it('keeps every type section reachable without javascript', function () {
        $html = $this->get(route('reports'))->assertOk()->getContent();

        foreach (['account', 'website', 'back'] as $type) {
            expect(openingTag($html, 'id="type-'.$type.'"'))
                ->toContain('type="radio"', 'name="type"', 'value="'.$type.'"');
        }

        // Back-only and account-only fields are in the markup, switched by CSS on the checked radio.
        expect(openingTag($html, 'data-show-for="back"\s*>'))->not->toBeEmpty()
            ->and($html)->toContain('name="sold_account"', 'name="target_socials"')
            ->toMatch('/#report-form:has\(#type-back:checked\)\s*\[data-show-for~=.back.\]/');
    });

    it('preselects the tab from the type query', function (string $type) {
        $html = $this->get(route('reports', ['type' => $type]))->assertOk()->getContent();

        expect(openingTag($html, 'id="type-'.$type.'"'))->toMatch('/\schecked/')
            ->and(openingTag($html, 'id="report-tab-'.$type.'"'))->toContain('aria-selected="true"')
            ->and(substr_count($html, 'aria-selected="true"'))->toBe(1);
    })->with(['account', 'website', 'back']);

    it('falls back to the account tab for an unknown type query', function () {
        $html = $this->get(route('reports', ['type' => 'hacked']))->assertOk()->getContent();

        expect(openingTag($html, 'id="type-account"'))->toMatch('/\schecked/')
            ->and($html)->not->toContain('value="hacked"');
    });

    it('keeps every field name of the report endpoint', function () {
        $response = $this->get(route('reports'))->assertOk();

        foreach (['_token', 'type', 'target_id', 'target_name', 'target_bank', 'target_socials', 'sold_account', 'scam_type', 'category', 'damage_amount', 'description', 'evidence_images[]', 'reporter_name', 'reporter_contact', 'reporter_email', 'reporter_role'] as $name) {
            $response->assertSee('name="'.$name.'"', false);
        }
    });

    it('offers every scam type except the back type as a chip', function () {
        $response = $this->get(route('reports'))->assertOk();

        foreach (ScamType::cases() as $scamType) {
            if ($scamType === ScamType::AccountBack) {
                $response->assertDontSee('value="'.$scamType->value.'"', false);

                continue;
            }

            $response->assertSee('value="'.$scamType->value.'"', false)->assertSee($scamType->label());
        }
    });

    it('shows the evidence guidance, role choices and privacy note', function () {
        $html = $this->get(route('reports'))
            ->assertOk()
            ->assertSee('bill chuyển tiền', false)
            ->assertSee('ảnh chụp đoạn chat', false)
            ->assertSee('Gửi ảnh qua Zalo')
            ->assertSee('Tôi là nạn nhân, chịu trách nhiệm về nội dung này.')
            ->assertSee('Tôi chỉ đăng hộ người bị lừa.')
            ->assertSee('mỗi địa chỉ IP được gửi tối đa 3 tố cáo trong 24 giờ')
            ->getContent();

        expect(openingTag($html, 'value="victim"'))->toMatch('/\schecked/');

        $note = (string) str($html)->after('data-reporter-privacy-note')->before('</p>');

        expect($note)->toContain(
            'không hiển thị công khai',
            'chỉ quản trị viên xem được để xác minh tố cáo',
            'chịu trách nhiệm về tính trung thực',
            'href="'.route('moderation.policy').'"',
        );
    });

    it('marks the reporter name optional and the email and contact required', function () {
        $html = $this->get(route('reports'))->assertOk()->getContent();

        expect(openingTag($html, 'id="reporter_name"'))->not->toMatch('/\srequired/')
            ->and(openingTag($html, 'id="reporter_email"'))->toMatch('/\srequired/')
            ->and(openingTag($html, 'id="reporter_contact"'))->toMatch('/\srequired/')
            ->and(openingTag($html, 'id="damage_amount"'))->toMatch('/\srequired/')
            ->and(openingTag($html, 'id="description"'))->toContain('maxlength="5000"');
    });
});

describe('store', function () {
    it('creates a pending report with the private reporter fields over ajax', function () {
        $this->post(route('report.store'), reportFormPayload([
            'scam_type' => 'fake_shop',
            'category' => ScamType::FakeShop->label(),
            'damage_amount' => '1.500.000',
            'target_socials' => 'Zalo 0977888999',
            'reporter_role' => 'proxy',
        ]), ajaxHeaders())
            ->assertOk()
            ->assertJson(['redirect' => route('home')]);

        $report = Report::sole();

        expect($report->status)->toBe('pending')
            ->and($report->type)->toBe('account')
            ->and($report->scam_type)->toBe(ScamType::FakeShop)
            ->and($report->category)->toBe(ScamType::FakeShop->label())
            ->and($report->target_bank)->toBe('Vietcombank')
            ->and($report->target_socials)->toBe('Zalo 0977888999')
            ->and($report->reporter_email)->toBe('nan.nhan@example.com')
            ->and($report->reporter_role)->toBe('proxy')
            ->and($report->sold_account)->toBeNull()
            ->and((float) $report->damage_amount)->toBe(1500000.0);
    });

    it('defaults an empty reporter name to Người dùng', function () {
        $this->post(route('report.store'), reportFormPayload(['reporter_name' => '']))->assertSessionHasNoErrors();

        expect(Report::sole()->reporter_name)->toBe('Người dùng');
    });

    it('accepts a twenty character description', function () {
        $this->post(route('report.store'), reportFormPayload(['description' => str_repeat('a', 20)]))
            ->assertSessionHasNoErrors();

        expect(Report::count())->toBe(1);
    });

    it('drops the sold account for types other than back', function () {
        $this->post(route('report.store'), reportFormPayload(['sold_account' => 'Liên Quân - nick ABC']))
            ->assertSessionHasNoErrors();

        expect(Report::sole()->sold_account)->toBeNull();
    });

    it('accepts the largest amount the column can hold', function () {
        $this->post(route('report.store'), reportFormPayload(['damage_amount' => '99.999.999']))->assertSessionHasNoErrors();

        expect((float) Report::sole()->damage_amount)->toBe(99999999.0);
    });

    it('drops the bank and channels of a website report', function () {
        $this->post(route('report.store'), reportFormPayload([
            'type' => 'website',
            'target_id' => 'https://shop-lua.vn',
            'target_socials' => str_repeat('a', 300),
        ]))->assertSessionHasNoErrors();

        expect(Report::sole())->target_bank->toBeNull()->target_socials->toBeNull();
    });

    it('confirms a regular post on the form page', function () {
        $this->followingRedirects()
            ->post(route('report.store'), reportFormPayload())
            ->assertOk()
            ->assertSee('Báo cáo đã được gửi và đang chờ kiểm duyệt.')
            ->assertSee('action="'.route('report.store').'"', false);
    });

    it('rejects invalid input', function (array $overrides, string $field, string $message) {
        $this->post(route('report.store'), reportFormPayload($overrides), ajaxHeaders())
            ->assertUnprocessable()
            ->assertJsonValidationErrors([$field => $message]);

        expect(Report::count())->toBe(0);
    })->with([
        'missing type' => [['type' => ''], 'type', 'Vui lòng chọn loại tố cáo.'],
        'unknown type' => [['type' => 'hacked'], 'type', 'Loại tố cáo không hợp lệ.'],
        'missing target' => [['target_id' => ''], 'target_id', 'Vui lòng nhập số tài khoản, số điện thoại hoặc link của đối tượng.'],
        'too long channels' => [['target_socials' => str_repeat('a', 256)], 'target_socials', 'Kênh liên hệ khác không được dài quá 255 ký tự.'],
        'too long sold account' => [['type' => 'back', 'sold_account' => str_repeat('a', 256)], 'sold_account', 'Tài khoản đã mua không được dài quá 255 ký tự.'],
        'unknown scam type' => [['scam_type' => 'pyramid'], 'scam_type', 'Hình thức lừa đảo không hợp lệ.'],
        'missing amount' => [['damage_amount' => ''], 'damage_amount', 'Vui lòng nhập số tiền thiệt hại (nhập 0 nếu chưa mất tiền).'],
        'text amount' => [['damage_amount' => 'năm trăm'], 'damage_amount', 'Số tiền thiệt hại phải là chữ số.'],
        'negative amount' => [['damage_amount' => '-1'], 'damage_amount', 'Số tiền không được nhỏ hơn 0.'],
        'missing description' => [['description' => ''], 'description', 'Vui lòng kể lại sự việc.'],
        'short description' => [['description' => str_repeat('a', 19)], 'description', 'Vui lòng mô tả sự việc với ít nhất 20 ký tự.'],
        'missing contact' => [['reporter_contact' => ''], 'reporter_contact', 'Vui lòng nhập số Zalo hoặc SĐT để kiểm duyệt viên liên hệ khi cần.'],
        'missing email' => [['reporter_email' => ''], 'reporter_email', 'Vui lòng nhập email để nhận kết quả kiểm duyệt.'],
        'invalid email' => [['reporter_email' => 'not-an-email'], 'reporter_email', 'Email không đúng định dạng.'],
        'email without a dot in the domain' => [['reporter_email' => 'nan.nhan@localhost'], 'reporter_email', 'Email không đúng định dạng, ví dụ: ten@gmail.com'],
        'exponent amount' => [['damage_amount' => '1e3'], 'damage_amount', 'Số tiền thiệt hại phải là chữ số.'],
        'amount over the column capacity' => [['damage_amount' => '999.999.999.999'], 'damage_amount', 'Số tiền tối đa là 99.999.999 VNĐ. Nếu thiệt hại lớn hơn, hãy ghi rõ số tiền trong phần kể lại sự việc.'],
        'too long description' => [['description' => str_repeat('a', 5001)], 'description', 'Phần kể lại sự việc không được dài quá 5000 ký tự.'],
        'back scam type on an account report' => [['scam_type' => 'account_back'], 'scam_type', 'Hình thức lừa đảo không hợp lệ.'],
        'missing role' => [['reporter_role' => ''], 'reporter_role', 'Vui lòng chọn vai trò của bạn.'],
        'unknown role' => [['reporter_role' => 'witness'], 'reporter_role', 'Vai trò người báo cáo không hợp lệ.'],
        'no evidence' => [['evidence_images' => []], 'evidence_images', 'Bắt buộc phải tải lên ít nhất 1 ảnh bằng chứng.'],
        'not an image' => [['evidence_images' => [UploadedFile::fake()->create('bill.pdf', 10, 'application/pdf')]], 'evidence_images.0', 'File tải lên phải là hình ảnh.'],
        'image too large' => [['evidence_images' => [UploadedFile::fake()->image('huge.jpg')->size(6000)]], 'evidence_images.0', 'Mỗi hình ảnh không được vượt quá 5MB.'],
    ]);

    it('rejects more than ten evidence images', function () {
        $images = array_map(fn (int $i) => UploadedFile::fake()->image("bill-{$i}.jpg"), range(1, 11));

        $this->post(route('report.store'), reportFormPayload(['evidence_images' => $images]), ajaxHeaders())
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['evidence_images' => 'Chỉ được tải lên tối đa 10 ảnh.']);
    });

    it('stores every uploaded evidence image, webp included', function () {
        $this->post(route('report.store'), reportFormPayload([
            'evidence_images' => [
                UploadedFile::fake()->image('bill.jpg'),
                UploadedFile::fake()->image('chat.png'),
                UploadedFile::fake()->image('chat.webp'),
            ],
        ]), ajaxHeaders())->assertOk();

        $paths = Report::sole()->evidence_images;

        expect($paths)->toHaveCount(3);
        foreach ($paths as $path) {
            Storage::disk('my_public')->assertExists($path);
        }
    });
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

    it('applies to back reports too', function () {
        $this->post(route('report.store'), reportFormPayload(['type' => 'back']), ajaxHeaders())
            ->assertUnprocessable()
            ->assertJsonStructure(['errors' => ['general']]);

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

    it('allows a report once the oldest one is more than a day old', function () {
        Report::query()->oldest('id')->first()->forceFill(['created_at' => now()->subDays(2)])->save();

        $this->post(route('report.store'), reportFormPayload(), ajaxHeaders())->assertOk();

        expect(Report::count())->toBe(4);
    });
});

describe('server validation without javascript', function () {
    it('keeps the chosen tab and refills the fields', function () {
        $html = $this->from(route('reports'))
            ->followingRedirects()
            ->post(route('report.store'), reportFormPayload([
                'type' => 'back',
                'target_id' => '0934567890',
                'target_name' => 'Nguoi Ban',
                'sold_account' => 'Free Fire - nick RongLua',
                'damage_amount' => '250000',
                'description' => 'Quá ngắn',
                'reporter_role' => 'proxy',
            ]))
            ->assertOk()
            ->assertSee('Vui lòng mô tả sự việc với ít nhất 20 ký tự.')
            ->getContent();

        expect(openingTag($html, 'id="type-back"'))->toMatch('/\schecked/')
            ->and(openingTag($html, 'id="type-account"'))->not->toMatch('/\schecked/')
            ->and(openingTag($html, 'id="target_id"'))->toContain('value="0934567890"')
            ->and(openingTag($html, 'id="sold_account"'))->toContain('value="Free Fire - nick RongLua"')
            ->and(openingTag($html, 'id="damage_amount"'))->toContain('value="250000"')
            ->and(openingTag($html, 'id="description"'))->toContain('aria-invalid="true"')
            ->and(openingTag($html, 'value="proxy"'))->toMatch('/\schecked/')
            ->and(Report::count())->toBe(0);
    });
});
