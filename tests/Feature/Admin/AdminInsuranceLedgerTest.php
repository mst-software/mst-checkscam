<?php

use App\Enums\InsuranceStatusEventType;
use App\Enums\LedgerEntryType;
use App\Models\Insurance;
use App\Models\InsuranceLedgerEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin', 'status' => 1]);
    $this->insurance = Insurance::factory()->trusted()->create(['amount' => 5000000]);
});

/**
 * @return array<string, mixed>
 */
function ledgerFormData(array $overrides = []): array
{
    return array_merge([
        'type' => 'deposit',
        'amount' => '2000000',
        'reference' => 'VCB FT26111600001',
        'note' => 'Nạp thêm quỹ',
        'submission_token' => (string) str()->uuid(),
    ], $overrides);
}

describe('ledger form', function () {
    it('records a deposit and updates the balance', function () {
        $this->actingAs($this->admin)
            ->post(route('admin.insurances.ledger.store', $this->insurance), ledgerFormData())
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.insurances.show', $this->insurance))
            ->assertSessionHas('success');

        $entry = $this->insurance->ledgerEntries()->get()->last();
        expect($entry->type)->toBe(LedgerEntryType::Deposit)
            ->and((string) $entry->amount)->toBe('2000000.00')
            ->and((string) $entry->balance_after)->toBe('7000000.00')
            ->and($entry->reference)->toBe('VCB FT26111600001')
            ->and($entry->recorded_by)->toBe($this->admin->id)
            ->and((string) $this->insurance->fresh()->amount)->toBe('7000000.00');
    });

    it('records refunds and decreasing adjustments as negative amounts', function () {
        $this->actingAs($this->admin)->post(route('admin.insurances.ledger.store', $this->insurance), ledgerFormData([
            'type' => 'refund', 'amount' => '1000000',
        ]))->assertSessionHasNoErrors();

        $this->actingAs($this->admin)->post(route('admin.insurances.ledger.store', $this->insurance), ledgerFormData([
            'type' => 'adjustment', 'direction' => 'decrease', 'amount' => '500000', 'reference' => null, 'note' => 'Phí chuyển khoản',
        ]))->assertSessionHasNoErrors();

        expect($this->insurance->ledgerEntries()->pluck('amount')->map(fn ($amount) => (string) $amount)->all())
            ->toBe(['5000000.00', '-1000000.00', '-500000.00'])
            ->and((string) $this->insurance->fresh()->amount)->toBe('3500000.00');
    });

    it('validates the entry in vietnamese', function (array $data, string $field, string $message) {
        $this->actingAs($this->admin)
            ->post(route('admin.insurances.ledger.store', $this->insurance), ledgerFormData($data))
            ->assertSessionHasErrorsIn('ledger', [$field => $message]);

        expect($this->insurance->ledgerEntries()->count())->toBe(1);
    })->with([
        'payout by hand' => [['type' => 'payout'], 'type', 'Loại bút toán không hợp lệ.'],
        'zero amount' => [['amount' => '0'], 'amount', 'Số tiền phải lớn hơn 0.'],
        'decimal amount' => [['amount' => '10.5'], 'amount', 'Số tiền phải là số nguyên (VND).'],
        'missing reference' => [['reference' => ''], 'reference', 'Vui lòng nhập mã giao dịch ngân hàng.'],
        'adjustment without direction' => [['type' => 'adjustment', 'note' => 'x'], 'direction', 'Vui lòng chọn điều chỉnh tăng hay giảm quỹ.'],
        'adjustment without note' => [['type' => 'adjustment', 'direction' => 'increase', 'note' => ''], 'note', 'Vui lòng ghi lý do điều chỉnh.'],
    ]);

    it('flashes the domain error when the balance would go negative and writes nothing', function () {
        $this->actingAs($this->admin)
            ->from(route('admin.insurances.show', $this->insurance))
            ->post(route('admin.insurances.ledger.store', $this->insurance), ledgerFormData(['type' => 'refund', 'amount' => '6000000']))
            ->assertRedirect(route('admin.insurances.show', $this->insurance))
            ->assertSessionHas('error', 'Số dư quỹ không đủ (hiện còn 5.000.000 đ).');

        expect($this->insurance->ledgerEntries()->count())->toBe(1)
            ->and((string) $this->insurance->fresh()->amount)->toBe('5000000.00');
    });

    it('does not record the same submission twice', function () {
        $data = ledgerFormData();

        $this->actingAs($this->admin)->post(route('admin.insurances.ledger.store', $this->insurance), $data)
            ->assertSessionHas('success');
        $this->actingAs($this->admin)->post(route('admin.insurances.ledger.store', $this->insurance), $data)
            ->assertSessionHas('error', 'Bút toán này đã được gửi trước đó, không ghi nhận lại.');

        expect($this->insurance->ledgerEntries()->count())->toBe(2);
    });

    it('lets a failed submission be retried with the same token', function () {
        $data = ledgerFormData(['type' => 'refund', 'amount' => '6000000']);

        $this->actingAs($this->admin)->post(route('admin.insurances.ledger.store', $this->insurance), $data)
            ->assertSessionHas('error');
        $this->actingAs($this->admin)->post(route('admin.insurances.ledger.store', $this->insurance), array_merge($data, ['amount' => '1000000']))
            ->assertSessionHas('success');

        expect($this->insurance->ledgerEntries()->count())->toBe(2);
    });

    it('offers no route to edit or delete ledger entries', function () {
        $entry = $this->insurance->ledgerEntries()->first();
        $ledgerRoutes = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($route) => str_contains($route->uri(), 'ledger'));

        expect($ledgerRoutes->flatMap->methods()->unique()->values()->all())->toBe(['POST'])
            ->and(route('admin.insurances.ledger.store', $this->insurance))->toEndWith('/ledger');

        $this->actingAs($this->admin)->put(url('admin/insurances/'.$this->insurance->id.'/ledger/'.$entry->id))->assertNotFound();
        $this->actingAs($this->admin)->delete(url('admin/insurances/'.$this->insurance->id.'/ledger/'.$entry->id))->assertNotFound();

        expect(InsuranceLedgerEntry::query()->count())->toBe(1);
    });
});

describe('partner page', function () {
    it('shows the ledger and reconciliation status without any claim ui', function () {
        $this->actingAs($this->admin)->post(route('admin.insurances.ledger.store', $this->insurance), ledgerFormData());

        $this->actingAs($this->admin)->get(route('admin.insurances.show', $this->insurance))
            ->assertOk()
            ->assertSee('Sổ quỹ')
            ->assertSee('VCB FT26111600001')
            ->assertSee('7.000.000 ₫')
            ->assertSee('Số dư khớp với sổ quỹ')
            ->assertSee('Bút toán không thể sửa hoặc xóa')
            ->assertSee('Đã xác thực')
            ->assertDontSee('Lệch sổ quỹ')
            ->assertDontSee('<option value="payout"', false)
            ->assertDontSee('bồi thường')
            ->assertDontSee('bảo lãnh');
    });

    it('warns when the cached balance does not match the ledger', function () {
        Insurance::query()->whereKey($this->insurance->id)->update(['amount' => 4000000]);

        $this->actingAs($this->admin)->get(route('admin.insurances.show', $this->insurance))
            ->assertOk()
            ->assertSee('Lệch sổ quỹ');
    });
});

describe('suspension', function () {
    it('suspends with a required reason and writes a status event', function () {
        $this->actingAs($this->admin)->post(route('admin.insurances.suspend', $this->insurance), ['reason' => ''])
            ->assertSessionHasErrors(['reason' => 'Vui lòng nhập lý do tạm đình chỉ.']);
        expect($this->insurance->fresh()->isSuspended())->toBeFalse();

        $this->actingAs($this->admin)->post(route('admin.insurances.suspend', $this->insurance), ['reason' => 'Có tố cáo đang xác minh'])
            ->assertSessionHas('success');

        $insurance = $this->insurance->fresh();
        $event = $insurance->statusEvents()->sole();
        expect($insurance->isSuspended())->toBeTrue()
            ->and($insurance->suspension_reason)->toBe('Có tố cáo đang xác minh')
            ->and($event->type)->toBe(InsuranceStatusEventType::Suspended)
            ->and($event->actor_id)->toBe($this->admin->id);

        $this->actingAs($this->admin)->get(route('admin.insurances.show', $insurance))
            ->assertSee('Tạm đình chỉ')
            ->assertSee('Có tố cáo đang xác minh')
            ->assertSee('Khôi phục hoạt động');
    });

    it('reinstates a suspended partner and refuses to suspend twice', function () {
        $this->actingAs($this->admin)->post(route('admin.insurances.suspend', $this->insurance), ['reason' => 'Kiểm tra']);
        $this->actingAs($this->admin)->post(route('admin.insurances.suspend', $this->insurance), ['reason' => 'Lần hai'])
            ->assertSessionHas('error', 'Thành viên này đang bị tạm đình chỉ rồi.');

        $this->actingAs($this->admin)->post(route('admin.insurances.reinstate', $this->insurance), ['reason' => 'Đã xác minh'])
            ->assertSessionHas('success');

        $insurance = $this->insurance->fresh();
        expect($insurance->isSuspended())->toBeFalse()
            ->and($insurance->statusEvents()->pluck('type')->all())
            ->toBe([InsuranceStatusEventType::Suspended, InsuranceStatusEventType::Reinstated]);

        $this->actingAs($this->admin)->post(route('admin.insurances.reinstate', $this->insurance))
            ->assertSessionHas('error', 'Thành viên này không bị tạm đình chỉ.');
    });
});
