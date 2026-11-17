<?php

use App\Enums\ChannelType;
use App\Enums\LedgerEntryType;
use App\Helpers\ConfigHelper;
use App\Models\Insurance;
use App\Models\InsuranceChannel;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('my_public');
    $this->admin = User::factory()->create(['role' => 'admin', 'status' => 1]);
    $this->moderator = User::factory()->create(['role' => 'moderator', 'status' => 1]);
});

/**
 * A valid partner form submission.
 *
 * @return array<string, mixed>
 */
function partnerFormData(array $overrides = []): array
{
    return array_merge([
        'full_name' => 'Trần Trung Gian',
        'insurance_date' => now()->subMonth()->toDateString(),
        'expired_at' => now()->addYear()->toDateString(),
        'status' => '1',
        'avatar' => UploadedFile::fake()->image('avatar.jpg'),
        'opening_deposit' => '20000000',
        'channels' => [
            ['type' => 'phone', 'value' => '+84 912 345 678', 'is_official' => '1', 'verified' => '1'],
            ['type' => 'facebook', 'value' => 'https://www.facebook.com/trung.gian.uy.tin', 'is_official' => '1', 'verified' => '0'],
            ['type' => 'bank_account', 'value' => '0011 0022 33', 'is_official' => '1', 'verified' => '1', 'bank' => 'Vietcombank', 'holder' => 'TRAN TRUNG GIAN'],
            ['type' => 'telegram', 'value' => '@ban_cu_khong_dung', 'is_official' => '0', 'verified' => '0'],
        ],
        'services' => [['title' => 'Trung gian giao dịch tài khoản game']],
    ], $overrides);
}

describe('access', function () {
    it('redirects guests to the admin login', function (string $method, string $routeName) {
        $insurance = Insurance::factory()->create();

        $this->call($method, route($routeName, $insurance))->assertRedirect(route('admin.auth.login'));
    })->with([
        ['GET', 'admin.insurances.index'],
        ['GET', 'admin.insurances.show'],
        ['GET', 'admin.insurances.edit'],
        ['POST', 'admin.insurances.ledger.store'],
        ['POST', 'admin.insurances.suspend'],
        ['DELETE', 'admin.insurances.destroy'],
    ]);

    it('forbids non staff users', function (string $method, string $routeName) {
        $user = User::factory()->create(['role' => 'user', 'status' => 1]);
        $insurance = Insurance::factory()->create();

        $this->actingAs($user)->call($method, route($routeName, $insurance))->assertForbidden();
    })->with([
        ['GET', 'admin.insurances.index'],
        ['GET', 'admin.insurances.show'],
        ['POST', 'admin.insurances.ledger.store'],
        ['DELETE', 'admin.insurances.destroy'],
    ]);

    it('lets moderators view and edit profiles but not create, delete, move funds or change standing', function () {
        $insurance = Insurance::factory()->trusted()->create();

        $this->actingAs($this->moderator)->get(route('admin.insurances.index'))->assertOk();
        $this->actingAs($this->moderator)->get(route('admin.insurances.show', $insurance))->assertOk()
            ->assertDontSee('Ghi nhận bút toán');
        $this->actingAs($this->moderator)->get(route('admin.insurances.edit', $insurance))->assertOk();
        $this->actingAs($this->moderator)->get(route('admin.insurances.create'))->assertForbidden();
        $this->actingAs($this->moderator)->post(route('admin.insurances.store'), partnerFormData())->assertForbidden();
        $this->actingAs($this->moderator)->post(route('admin.insurances.ledger.store', $insurance), [
            'type' => 'deposit', 'amount' => 1000, 'reference' => 'FT1', 'submission_token' => 't1',
        ])->assertForbidden();
        $this->actingAs($this->moderator)->post(route('admin.insurances.suspend', $insurance), ['reason' => 'x'])->assertForbidden();
        $this->actingAs($this->moderator)->deleteJson(route('admin.insurances.destroy', $insurance))->assertForbidden();
    });
});

describe('partner form', function () {
    it('creates a partner, records the opening deposit and stores normalized channels', function () {
        $this->actingAs($this->admin)
            ->post(route('admin.insurances.store'), partnerFormData())
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $insurance = Insurance::query()->sole();
        $entry = $insurance->ledgerEntries()->sole();

        expect((string) $insurance->amount)->toBe('20000000.00')
            ->and($entry->type)->toBe(LedgerEntryType::Deposit)
            ->and((string) $entry->amount)->toBe('20000000.00')
            ->and($entry->recorded_by)->toBe($this->admin->id)
            ->and($insurance->avatar)->not->toBeNull();

        $channels = $insurance->channels()->orderBy('id')->get();
        expect($channels)->toHaveCount(4)
            ->and($channels[0]->normalized_value)->toBe('0912345678')
            ->and($channels[0]->verified_at)->not->toBeNull()
            ->and($channels[1]->normalized_value)->toBe('trung.gian.uy.tin')
            ->and($channels[1]->verified_at)->toBeNull()
            ->and($channels[2]->normalized_value)->toBe('0011002233')
            ->and($channels[3]->is_official)->toBeFalse();
    });

    it('keeps the legacy contact_info and payment_accounts json in sync with official channels', function () {
        $this->actingAs($this->admin)->post(route('admin.insurances.store'), partnerFormData());

        $insurance = Insurance::query()->sole();

        expect($insurance->contact_info)->toEqual([
            ['platform' => 'Số điện thoại', 'link' => '+84 912 345 678'],
            ['platform' => 'Facebook', 'link' => 'https://www.facebook.com/trung.gian.uy.tin'],
        ])->and($insurance->payment_accounts)->toEqual([
            ['bank' => 'Vietcombank', 'number' => '0011 0022 33', 'name' => 'TRAN TRUNG GIAN'],
        ]);

        // The legacy parser used by the backfill reads the synced JSON back to the same official channels.
        $legacy = collect(InsuranceChannel::fromLegacyJson($insurance->contact_info, $insurance->payment_accounts))
            ->map(fn (array $channel) => $channel['type']->value.'|'.$channel['normalized_value'])->sort()->values()->all();
        expect($legacy)->toBe(['bank_account|0011002233', 'facebook|trung.gian.uy.tin', 'phone|0912345678']);
    });

    it('creates a partner without a ledger entry when the opening deposit is zero', function () {
        $this->actingAs($this->admin)->post(route('admin.insurances.store'), partnerFormData(['opening_deposit' => '0']))
            ->assertSessionHasNoErrors();

        $insurance = Insurance::query()->sole();
        expect((string) $insurance->amount)->toBe('0.00')
            ->and($insurance->ledgerEntries()->count())->toBe(0);
    });

    it('validates the form in vietnamese', function () {
        $this->actingAs($this->admin)
            ->post(route('admin.insurances.store'), partnerFormData([
                'opening_deposit' => '-5',
                'channels' => [['type' => 'pigeon', 'value' => '']],
            ]))
            ->assertSessionHasErrors([
                'opening_deposit' => 'Số tiền ký quỹ ban đầu không được âm.',
                'channels.0.type' => 'Loại kênh không hợp lệ.',
                'channels.0.value' => 'Vui lòng nhập giá trị cho từng kênh.',
            ]);

        expect(Insurance::query()->count())->toBe(0);
    });

    it('rejects duplicate channels after normalization without saving anything', function () {
        $this->actingAs($this->admin)
            ->post(route('admin.insurances.store'), partnerFormData([
                'channels' => [
                    ['type' => 'phone', 'value' => '0912345678', 'is_official' => '1'],
                    ['type' => 'phone', 'value' => '+84 912-345-678', 'is_official' => '1'],
                ],
            ]))
            ->assertSessionHasErrors('channels.1.value');

        expect(Insurance::query()->count())->toBe(0)
            ->and(InsuranceChannel::query()->count())->toBe(0);
    });

    it('ignores a submitted amount when editing and never touches the ledger', function () {
        $insurance = Insurance::factory()->trusted()->withChannels()->create(['amount' => 5000000]);

        $this->actingAs($this->moderator)
            ->put(route('admin.insurances.update', $insurance), partnerFormData([
                'avatar' => null,
                'amount' => '999999999',
                'opening_deposit' => '999999999',
                'full_name' => 'Tên Mới',
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.insurances.show', $insurance));

        $insurance->refresh();
        expect($insurance->full_name)->toBe('Tên Mới')
            ->and((string) $insurance->amount)->toBe('5000000.00')
            ->and($insurance->ledgerEntries()->count())->toBe(1);
    });

    it('adds, updates and removes channels on edit, keeping verified_at of unchanged channels', function () {
        $insurance = Insurance::factory()->trusted()->create();
        $kept = InsuranceChannel::factory()->for($insurance)->ofType(ChannelType::Phone, '0912345678')
            ->create(['verified_at' => now()->subMonth()]);
        $removed = InsuranceChannel::factory()->for($insurance)->ofType(ChannelType::Zalo, '0987654321')->create();

        $this->actingAs($this->admin)
            ->put(route('admin.insurances.update', $insurance), partnerFormData([
                'avatar' => null,
                'channels' => [
                    ['type' => 'phone', 'value' => '+84912345678', 'is_official' => '1', 'verified' => '1'],
                    ['type' => 'website', 'value' => 'https://www.trunggian.vn/', 'is_official' => '1', 'verified' => '0'],
                ],
            ]))
            ->assertSessionHasNoErrors();

        $channels = $insurance->channels()->orderBy('id')->get();
        expect($channels)->toHaveCount(2)
            ->and($channels[0]->id)->toBe($kept->id)
            ->and($channels[0]->value)->toBe('+84912345678')
            ->and($channels[0]->verified_at->toDateString())->toBe(now()->subMonth()->toDateString())
            ->and($channels[1]->type)->toBe(ChannelType::Website)
            ->and($channels[1]->normalized_value)->toBe('trunggian.vn')
            ->and(InsuranceChannel::query()->find($removed->id))->toBeNull();
    });

    it('prefills bank name and holder from the legacy json on the edit form', function () {
        $this->actingAs($this->admin)->post(route('admin.insurances.store'), partnerFormData());
        $insurance = Insurance::query()->sole();

        $this->actingAs($this->admin)->get(route('admin.insurances.edit', $insurance))
            ->assertOk()
            ->assertSee('value="Vietcombank"', false)
            ->assertSee('value="TRAN TRUNG GIAN"', false)
            ->assertSee('Số dư quỹ hiện tại')
            ->assertDontSee('name="amount"', false)
            ->assertDontSee('name="opening_deposit"', false);
    });
});

describe('delete protection', function () {
    it('refuses to delete a partner with ledger history and suggests suspension', function () {
        $insurance = Insurance::factory()->create(['amount' => 1000000]);

        $this->actingAs($this->admin)
            ->deleteJson(route('admin.insurances.destroy', $insurance))
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->actingAs($this->admin)
            ->from(route('admin.insurances.index'))
            ->delete(route('admin.insurances.destroy', $insurance))
            ->assertSessionHas('error');

        expect(Insurance::query()->find($insurance->id))->not->toBeNull();
    });

    it('refuses to delete a partner with legacy claim rows', function () {
        $insurance = Insurance::factory()->create(['amount' => 0]);
        DB::table('insurance_claims')->insert([
            'insurance_id' => $insurance->id,
            'claimant_name' => 'Người mua cũ',
            'contact_email' => 'legacy@example.com',
            'amount_claimed' => 1000000,
            'description' => 'Dữ liệu từ tính năng đã gỡ bỏ.',
        ]);

        $this->actingAs($this->admin)->deleteJson(route('admin.insurances.destroy', $insurance))->assertStatus(422);

        $this->actingAs($this->admin)->get(route('admin.insurances.index'))
            ->assertOk()
            ->assertSee('Không thể xóa');

        expect(Insurance::query()->find($insurance->id))->not->toBeNull();
    });

    it('still deletes a partner without history, including its channels', function () {
        $insurance = Insurance::factory()->create(['amount' => 0]);
        InsuranceChannel::factory()->for($insurance)->create();

        $this->actingAs($this->admin)->deleteJson(route('admin.insurances.destroy', $insurance))
            ->assertOk()
            ->assertJsonPath('success', true);

        expect(Insurance::query()->find($insurance->id))->toBeNull()
            ->and(InsuranceChannel::query()->count())->toBe(0);
    });

    it('skips partners with history in bulk delete', function () {
        $withHistory = Insurance::factory()->create(['amount' => 1000000]);
        $clean = Insurance::factory()->create(['amount' => 0]);

        $this->actingAs($this->admin)
            ->postJson(route('admin.insurances.bulk-delete'), ['ids' => [$withHistory->id, $clean->id]])
            ->assertOk()
            ->assertJsonPath('message', 'Đã xóa 1 mục thành công. Bỏ qua 1 thành viên đã có sổ quỹ hoặc lịch sử (hãy tạm đình chỉ thay vì xóa).');

        expect(Insurance::query()->pluck('id')->all())->toBe([$withHistory->id]);
    });

    it('hides the delete button for partners with history', function () {
        $withHistory = Insurance::factory()->create(['amount' => 1000000, 'full_name' => 'Có Lịch Sử']);
        $clean = Insurance::factory()->create(['amount' => 0, 'full_name' => 'Chưa Có Gì']);

        $this->actingAs($this->admin)->get(route('admin.insurances.index'))
            ->assertOk()
            ->assertDontSee('data-id="'.$withHistory->id.'"', false)
            ->assertSee('data-id="'.$clean->id.'"', false);
    });

    it('restricts deleting history at the database level on mysql', function () {
        if (! in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            $this->markTestSkipped('Foreign key rules are only changed on MySQL / MariaDB.');
        }

        $insurance = Insurance::factory()->create(['amount' => 1000000]);

        expect(fn () => DB::table('insurances')->where('id', $insurance->id)->delete())
            ->toThrow(QueryException::class);
    });

    it('skips the foreign key migration on sqlite', function () {
        config(['database.connections.fk_guard_sqlite' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]]);
        $default = DB::getDefaultConnection();
        $migration = require database_path('migrations/2026_11_16_213850_restrict_insurance_history_foreign_keys.php');

        DB::setDefaultConnection('fk_guard_sqlite');
        try {
            $migration->up();
            $migration->down();
        } finally {
            DB::setDefaultConnection($default);
        }

        expect(DB::connection('fk_guard_sqlite')->select("select name from sqlite_master where type = 'table'"))->toBe([]);
    });
});

describe('settings', function () {
    it('saves the minimum insurance balance setting', function () {
        $this->actingAs($this->admin)
            ->post(route('admin.settings.update'), ['insurance_min_balance' => '5000000'])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        expect(Setting::query()->where('name', 'insurance_min_balance')->value('value'))->toBe('5000000')
            ->and((string) ConfigHelper::getConfig('insurance_min_balance'))->toBe('5000000');

        $this->actingAs($this->admin)->get(route('admin.settings.index'))
            ->assertOk()
            ->assertSee('Quỹ dưới mức này sẽ bị tự động đình chỉ; 0 = tắt')
            ->assertSee('value="5000000"', false);
    });

    it('turns the check off when the field is emptied and rejects negative values', function () {
        Setting::setValue('insurance_min_balance', '5000000');

        $this->actingAs($this->admin)->post(route('admin.settings.update'), ['insurance_min_balance' => ''])
            ->assertSessionHasNoErrors();
        expect(Setting::query()->where('name', 'insurance_min_balance')->value('value'))->toBe('0');

        $this->actingAs($this->admin)->post(route('admin.settings.update'), ['insurance_min_balance' => '-1'])
            ->assertSessionHasErrors(['insurance_min_balance' => 'Số dư quỹ tối thiểu không được âm.']);
    });
});
