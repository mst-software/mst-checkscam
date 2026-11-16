<?php

use App\Enums\ClaimStatus;
use App\Enums\LedgerEntryType;
use App\Mail\InsuranceClaimStatusChanged;
use App\Models\Insurance;
use App\Models\InsuranceClaim;
use App\Models\User;
use App\Services\InsuranceClaimService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

beforeEach(function () {
    Mail::fake();
    $this->admin = User::factory()->create(['role' => 'admin', 'status' => 1]);
    $this->moderator = User::factory()->create(['role' => 'moderator', 'status' => 1]);
    $this->insurance = Insurance::factory()->trusted()->create(['amount' => 5000000, 'full_name' => 'Đối Tác Một']);
});

function adminSubmitClaim(Insurance $insurance, array $overrides = []): InsuranceClaim
{
    return app(InsuranceClaimService::class)->submit($insurance, array_merge([
        'claimant_name' => 'Nguyễn Văn Bình',
        'contact_email' => 'binh@example.com',
        'contact_phone' => '0912345678',
        'amount_claimed' => '3000000',
        'description' => 'Chuyển tiền qua GDV nhưng không nhận được tài khoản.',
        'evidence_paths' => ['claims/a.jpg'],
    ], $overrides), '203.0.113.5');
}

describe('access', function () {
    it('redirects guests to the admin login', function (string $method, string $routeName) {
        $claim = InsuranceClaim::factory()->create();

        $this->call($method, route($routeName, $claim))->assertRedirect(route('admin.auth.login'));
    })->with([
        ['GET', 'admin.insurance-claims.index'],
        ['GET', 'admin.insurance-claims.show'],
        ['POST', 'admin.insurance-claims.review'],
        ['POST', 'admin.insurance-claims.approve'],
        ['POST', 'admin.insurance-claims.mark-paid'],
    ]);

    it('forbids non staff users', function (string $method, string $routeName) {
        $user = User::factory()->create(['role' => 'user', 'status' => 1]);
        $claim = InsuranceClaim::factory()->create();

        $this->actingAs($user)->call($method, route($routeName, $claim), ['note' => 'x'])->assertForbidden();

        expect($claim->fresh()->status)->toBe(ClaimStatus::Submitted);
    })->with([
        ['GET', 'admin.insurance-claims.index'],
        ['GET', 'admin.insurance-claims.show'],
        ['POST', 'admin.insurance-claims.review'],
        ['POST', 'admin.insurance-claims.reject'],
    ]);

    it('lets moderators review and reject but not approve or pay', function () {
        $claim = adminSubmitClaim($this->insurance);

        $this->actingAs($this->moderator)->post(route('admin.insurance-claims.review', $claim))->assertSessionHas('success');
        $this->actingAs($this->moderator)->post(route('admin.insurance-claims.approve', $claim), ['approved_amount' => 1000])
            ->assertForbidden();
        $this->actingAs($this->moderator)->get(route('admin.insurance-claims.show', $claim))
            ->assertOk()
            ->assertSee('Chỉ quản trị viên mới được duyệt số tiền bồi thường.');

        $approved = InsuranceClaim::factory()->for($this->insurance)->approved()->create();
        $this->actingAs($this->moderator)->post(route('admin.insurance-claims.mark-paid', $approved), ['paid_amount' => 1000, 'reference' => 'x'])
            ->assertForbidden();

        $this->actingAs($this->moderator)->post(route('admin.insurance-claims.reject', $claim), ['reason' => 'Không đủ bằng chứng'])
            ->assertSessionHas('success');
        expect($claim->fresh()->status)->toBe(ClaimStatus::Rejected);
    });
});

describe('inbox', function () {
    it('filters by status, partner and date and paginates', function () {
        $other = Insurance::factory()->trusted()->create(['full_name' => 'Đối Tác Hai']);
        InsuranceClaim::factory()->for($this->insurance)->create(['claimant_name' => 'Người Mới', 'created_at' => now()->subDay()]);
        InsuranceClaim::factory()->for($this->insurance)->rejected()->create(['claimant_name' => 'Người Bị Từ Chối', 'created_at' => now()->subDays(10)]);
        InsuranceClaim::factory()->for($other)->reviewing()->create(['claimant_name' => 'Người Đối Tác Hai', 'created_at' => now()->subDays(2)]);

        $this->actingAs($this->moderator)->get(route('admin.insurance-claims.index', ['status' => 'submitted']))
            ->assertOk()->assertSee('Người Mới')->assertDontSee('Người Bị Từ Chối')->assertDontSee('Người Đối Tác Hai');

        $this->actingAs($this->moderator)->get(route('admin.insurance-claims.index', ['insurance_id' => $other->id]))
            ->assertOk()->assertSee('Người Đối Tác Hai')->assertDontSee('Người Mới');

        $this->actingAs($this->moderator)->get(route('admin.insurance-claims.index', [
            'from' => now()->subDays(5)->toDateString(),
            'to' => now()->toDateString(),
        ]))->assertOk()->assertSee('Người Mới')->assertSee('Người Đối Tác Hai')->assertDontSee('Người Bị Từ Chối');

        InsuranceClaim::factory()->count(21)->for($other)->create();
        $this->actingAs($this->moderator)->get(route('admin.insurance-claims.index', ['insurance_id' => $other->id]))
            ->assertOk()
            ->assertViewHas('claims', fn ($claims) => $claims->count() === 20 && $claims->total() === 22);
    });

    it('ignores invalid filters', function () {
        InsuranceClaim::factory()->for($this->insurance)->create(['claimant_name' => 'Người Mới']);

        $this->actingAs($this->moderator)->get(route('admin.insurance-claims.index', ['status' => 'bogus', 'from' => 'not-a-date']))
            ->assertOk()->assertSee('Người Mới');
    });

    it('shows the open claims count in the sidebar badge', function () {
        InsuranceClaim::factory()->for($this->insurance)->count(2)->create();
        InsuranceClaim::factory()->for($this->insurance)->approved()->create();
        InsuranceClaim::factory()->for($this->insurance)->rejected()->create();
        InsuranceClaim::factory()->for($this->insurance)->paid()->create();

        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertOk();

        expect(preg_match('~data-testid="open-claims-badge"[^>]*>\s*(\d+)\s*<~', $response->getContent(), $matches))->toBe(1)
            ->and($matches[1])->toBe('3');
    });

    it('hides the badge when nothing is open', function () {
        InsuranceClaim::factory()->for($this->insurance)->rejected()->create();

        $this->actingAs($this->admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('open-claims-badge');
    });
});

describe('detail page', function () {
    it('shows claimant contact data, evidence and the timeline to staff', function () {
        $claim = adminSubmitClaim($this->insurance);

        $this->actingAs($this->moderator)->get(route('admin.insurance-claims.show', $claim))
            ->assertOk()
            ->assertSee('binh@example.com')
            ->assertSee('0912345678')
            ->assertSee('203.0.113.5')
            ->assertSee(asset('uploads/claims/a.jpg'), false)
            ->assertSee('Chuyển tiền qua GDV nhưng không nhận được tài khoản.')
            ->assertSee('Bắt đầu xem xét')
            ->assertSee('Từ chối yêu cầu')
            ->assertDontSee('Duyệt bồi thường')
            ->assertDontSee('Ghi nhận đã chi trả');
    });

    it('only offers the actions allowed from the current status', function (string $state, array $visible, array $hidden) {
        $claim = InsuranceClaim::factory()->for($this->insurance)->{$state}()->create();

        $response = $this->actingAs($this->admin)->get(route('admin.insurance-claims.show', $claim))->assertOk();

        foreach ($visible as $routeName) {
            $response->assertSee(route('admin.insurance-claims.'.$routeName, $claim), false);
        }
        foreach ($hidden as $routeName) {
            $response->assertDontSee(route('admin.insurance-claims.'.$routeName, $claim), false);
        }
    })->with([
        'submitted' => ['submitted', ['review', 'reject'], ['request-info', 'approve', 'mark-paid']],
        'reviewing' => ['reviewing', ['request-info', 'approve', 'reject'], ['review', 'mark-paid']],
        'need info' => ['needInfo', ['review', 'reject'], ['request-info', 'approve', 'mark-paid']],
        'approved' => ['approved', ['mark-paid'], ['review', 'request-info', 'approve', 'reject']],
        'rejected' => ['rejected', [], ['review', 'request-info', 'approve', 'reject', 'mark-paid']],
        'paid' => ['paid', [], ['review', 'request-info', 'approve', 'reject', 'mark-paid']],
    ]);

    it('saves the public summary and decision note without changing the status', function () {
        $claim = adminSubmitClaim($this->insurance);

        $this->actingAs($this->moderator)->post(route('admin.insurance-claims.summary', $claim), [
            'public_summary' => 'Người mua chuyển tiền nhưng không nhận được hàng.',
            'decision_note' => 'Đang chờ sao kê.',
        ])->assertSessionHas('success');

        expect($claim->fresh())
            ->public_summary->toBe('Người mua chuyển tiền nhưng không nhận được hàng.')
            ->decision_note->toBe('Đang chờ sao kê.')
            ->status->toBe(ClaimStatus::Submitted);
    });
});

describe('transitions', function () {
    it('walks a claim through review, need info, approval and payment', function () {
        $claim = adminSubmitClaim($this->insurance);

        $this->actingAs($this->admin)->post(route('admin.insurance-claims.review', $claim))->assertSessionHas('success');
        $this->actingAs($this->admin)->post(route('admin.insurance-claims.request-info', $claim), ['note' => 'Cần sao kê ngân hàng'])
            ->assertSessionHas('success');
        $this->actingAs($this->admin)->post(route('admin.insurance-claims.review', $claim))->assertSessionHas('success');
        $this->actingAs($this->admin)->post(route('admin.insurance-claims.approve', $claim), [
            'approved_amount' => '2500000',
            'public_summary' => 'Đủ bằng chứng chuyển tiền.',
            'decision_note' => 'Đã đối chiếu sao kê.',
        ])->assertSessionHasNoErrors()->assertSessionHas('success');

        $claim->refresh();
        expect($claim->status)->toBe(ClaimStatus::Approved)
            ->and((string) $claim->approved_amount)->toBe('2500000.00')
            ->and($claim->decided_by)->toBe($this->admin->id)
            ->and($claim->public_summary)->toBe('Đủ bằng chứng chuyển tiền.')
            ->and($claim->decision_note)->toBe('Đã đối chiếu sao kê.');

        $this->actingAs($this->admin)->post(route('admin.insurance-claims.mark-paid', $claim), [
            'paid_amount' => '2500000',
            'reference' => 'VCB FT26111600999',
        ])->assertSessionHas('success', 'Đã ghi nhận chi trả và trừ quỹ của thành viên. Số dư còn lại: 2.500.000 đ.');

        $claim->refresh();
        $payout = $claim->payoutEntry;
        expect($claim->status)->toBe(ClaimStatus::Paid)
            ->and($payout->type)->toBe(LedgerEntryType::Payout)
            ->and((string) $payout->amount)->toBe('-2500000.00')
            ->and($payout->reference)->toBe('VCB FT26111600999')
            ->and((string) $this->insurance->fresh()->amount)->toBe('2500000.00')
            ->and($claim->events()->pluck('to_status')->all())->toBe([
                ClaimStatus::Submitted, ClaimStatus::Reviewing, ClaimStatus::NeedInfo,
                ClaimStatus::Reviewing, ClaimStatus::Approved, ClaimStatus::Paid,
            ])
            ->and($claim->events()->where('to_status', ClaimStatus::NeedInfo)->value('note'))->toBe('Cần sao kê ngân hàng');

        Mail::assertQueued(InsuranceClaimStatusChanged::class, 4);
    });

    it('requires a note to ask for more information', function () {
        $claim = InsuranceClaim::factory()->for($this->insurance)->reviewing()->create();

        $this->actingAs($this->admin)->post(route('admin.insurance-claims.request-info', $claim), ['note' => ''])
            ->assertSessionHasErrors(['note' => 'Vui lòng ghi rõ cần bổ sung thông tin gì (ghi chú nội bộ).']);

        expect($claim->fresh()->status)->toBe(ClaimStatus::Reviewing);
    });

    it('requires a reason to reject', function () {
        $claim = adminSubmitClaim($this->insurance);

        $this->actingAs($this->admin)->post(route('admin.insurance-claims.reject', $claim), ['reason' => ''])
            ->assertSessionHasErrors(['reason' => 'Vui lòng ghi lý do từ chối.']);

        $this->actingAs($this->admin)->post(route('admin.insurance-claims.reject', $claim), ['reason' => 'Bằng chứng giả'])
            ->assertSessionHas('success');
        expect($claim->fresh())
            ->status->toBe(ClaimStatus::Rejected)
            ->decision_note->toBe('Bằng chứng giả');
    });

    it('requires an approved amount within the claimed amount', function () {
        $claim = InsuranceClaim::factory()->for($this->insurance)->reviewing()->create(['amount_claimed' => 3000000]);

        $this->actingAs($this->admin)->post(route('admin.insurance-claims.approve', $claim), [])
            ->assertSessionHasErrors(['approved_amount' => 'Vui lòng nhập số tiền duyệt bồi thường.']);

        $this->actingAs($this->admin)->post(route('admin.insurance-claims.approve', $claim), ['approved_amount' => '3000001'])
            ->assertSessionHas('error', 'Số tiền duyệt phải lớn hơn 0 và không vượt quá số tiền yêu cầu.');

        expect($claim->fresh()->status)->toBe(ClaimStatus::Reviewing);
    });

    it('refuses transitions that are not allowed from the current status', function () {
        $claim = adminSubmitClaim($this->insurance);

        $this->actingAs($this->admin)->post(route('admin.insurance-claims.approve', $claim), ['approved_amount' => '1000000'])
            ->assertSessionHas('error', 'Không thể chuyển yêu cầu từ "Đã tiếp nhận" sang "Đã duyệt bồi thường".');
        $this->actingAs($this->admin)->post(route('admin.insurance-claims.mark-paid', $claim), ['paid_amount' => '1000000', 'reference' => 'FT1'])
            ->assertSessionHas('error');

        expect($claim->fresh()->status)->toBe(ClaimStatus::Submitted)
            ->and($this->insurance->ledgerEntries()->count())->toBe(1);
    });

    it('is safe against a double submitted approval', function () {
        $claim = InsuranceClaim::factory()->for($this->insurance)->reviewing()->create(['amount_claimed' => 3000000]);

        $this->actingAs($this->admin)->post(route('admin.insurance-claims.approve', $claim), ['approved_amount' => '1000000'])
            ->assertSessionHas('success');
        $this->actingAs($this->admin)->post(route('admin.insurance-claims.approve', $claim), ['approved_amount' => '2000000'])
            ->assertSessionHas('error');

        expect((string) $claim->fresh()->approved_amount)->toBe('1000000.00')
            ->and($claim->events()->count())->toBe(1);
    });

    it('shows the insufficient balance error on payout and leaves no partial state', function () {
        $claim = InsuranceClaim::factory()->for($this->insurance)->approved()->create(['amount_claimed' => 8000000, 'approved_amount' => 8000000]);

        $this->actingAs($this->admin)
            ->from(route('admin.insurance-claims.show', $claim))
            ->post(route('admin.insurance-claims.mark-paid', $claim), ['paid_amount' => '8000000', 'reference' => 'VCB FT1'])
            ->assertRedirect(route('admin.insurance-claims.show', $claim))
            ->assertSessionHas('error', 'Số dư quỹ không đủ (hiện còn 5.000.000 đ).');

        $claim->refresh();
        expect($claim->status)->toBe(ClaimStatus::Approved)
            ->and($claim->paid_at)->toBeNull()
            ->and($claim->payoutEntry)->toBeNull()
            ->and($claim->events()->count())->toBe(0)
            ->and((string) $this->insurance->fresh()->amount)->toBe('5000000.00')
            ->and($this->insurance->ledgerEntries()->count())->toBe(1);

        Mail::assertNothingQueued();
    });

    it('rejects a paid amount above the approved amount', function () {
        $claim = InsuranceClaim::factory()->for($this->insurance)->approved()->create(['amount_claimed' => 1000000, 'approved_amount' => 1000000]);

        $this->actingAs($this->admin)->post(route('admin.insurance-claims.mark-paid', $claim), ['paid_amount' => '1000001', 'reference' => 'FT1'])
            ->assertSessionHas('error', 'Số tiền chi trả phải lớn hơn 0 và không vượt quá số tiền đã duyệt.');
        $this->actingAs($this->admin)->post(route('admin.insurance-claims.mark-paid', $claim), ['paid_amount' => '1000'])
            ->assertSessionHasErrors(['reference' => 'Vui lòng nhập mã giao dịch ngân hàng.']);

        expect($claim->fresh()->status)->toBe(ClaimStatus::Approved);
    });
});
