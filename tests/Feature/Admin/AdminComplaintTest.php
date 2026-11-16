<?php

use App\Enums\ComplaintStatus;
use App\Models\Complaint;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->moderator = User::factory()->create(['role' => 'moderator', 'status' => 1]);
});

it('redirects guests to the admin login', function (string $method, string $routeName) {
    $complaint = Complaint::factory()->create();

    $this->call($method, route($routeName, $complaint))
        ->assertRedirect(route('admin.auth.login'));
})->with([
    ['GET', 'admin.complaints.index'],
    ['GET', 'admin.complaints.show'],
    ['POST', 'admin.complaints.resolve'],
    ['POST', 'admin.complaints.dismiss'],
]);

it('forbids non staff users such as google accounts', function (string $method, string $routeName) {
    $googleUser = User::factory()->create(['role' => 'user', 'status' => 1]);
    $complaint = Complaint::factory()->create();

    $this->actingAs($googleUser)
        ->call($method, route($routeName, $complaint), ['admin_note' => 'x'])
        ->assertForbidden();

    expect($complaint->fresh()->status)->toBe(ComplaintStatus::Pending);
})->with([
    ['GET', 'admin.complaints.index'],
    ['GET', 'admin.complaints.show'],
    ['POST', 'admin.complaints.resolve'],
    ['POST', 'admin.complaints.dismiss'],
]);

it('lists complaints filtered by status', function () {
    Complaint::factory()->create(['contact_name' => 'Pending Person']);
    Complaint::factory()->resolved()->create(['contact_name' => 'Resolved Person']);

    $this->actingAs($this->moderator)
        ->get(route('admin.complaints.index', ['status' => 'pending']))
        ->assertOk()
        ->assertSee('Pending Person')
        ->assertDontSee('Resolved Person');

    $this->actingAs($this->moderator)
        ->get(route('admin.complaints.index'))
        ->assertOk()
        ->assertSee('Pending Person')
        ->assertSee('Resolved Person');
});

it('shows a complaint', function () {
    $complaint = Complaint::factory()->create(['message' => 'Nội dung khiếu nại cần xem xét kỹ.']);

    $this->actingAs($this->moderator)
        ->get(route('admin.complaints.show', $complaint))
        ->assertOk()
        ->assertSee('Nội dung khiếu nại cần xem xét kỹ.')
        ->assertSee($complaint->reason->label());
});

it('resolves a pending complaint', function () {
    $complaint = Complaint::factory()->create();

    $this->actingAs($this->moderator)
        ->post(route('admin.complaints.resolve', $complaint), ['admin_note' => 'Đã gỡ báo cáo.'])
        ->assertRedirect()
        ->assertSessionHas('success');

    $complaint->refresh();

    expect($complaint->status)->toBe(ComplaintStatus::Resolved)
        ->and($complaint->admin_note)->toBe('Đã gỡ báo cáo.')
        ->and($complaint->handled_by)->toBe($this->moderator->id)
        ->and($complaint->handled_at)->not->toBeNull();
});

it('dismisses a pending complaint with a note', function () {
    $complaint = Complaint::factory()->create();

    $this->actingAs($this->moderator)
        ->post(route('admin.complaints.dismiss', $complaint), ['admin_note' => 'Bằng chứng không hợp lệ.'])
        ->assertSessionHas('success');

    expect($complaint->fresh())
        ->status->toBe(ComplaintStatus::Dismissed)
        ->admin_note->toBe('Bằng chứng không hợp lệ.');
});

it('requires a note to dismiss a complaint', function () {
    $complaint = Complaint::factory()->create();

    $this->actingAs($this->moderator)
        ->post(route('admin.complaints.dismiss', $complaint), ['admin_note' => ''])
        ->assertSessionHasErrors('admin_note');

    expect($complaint->fresh()->status)->toBe(ComplaintStatus::Pending);
});

it('does not handle a complaint twice', function () {
    $complaint = Complaint::factory()->resolved()->create(['admin_note' => 'original']);

    $this->actingAs($this->moderator)
        ->post(route('admin.complaints.dismiss', $complaint), ['admin_note' => 'changed'])
        ->assertSessionHas('error');

    expect($complaint->fresh())
        ->status->toBe(ComplaintStatus::Resolved)
        ->admin_note->toBe('original');
});
