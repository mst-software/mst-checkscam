<?php

use App\Enums\ChannelType;
use App\Models\Insurance;
use App\Models\InsuranceChannel;
use App\Models\Setting;
use App\Models\User;
use App\Services\IntermediaryVerifier;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
});

test('partners get the next number on create and a deleted number is never reused', function () {
    Setting::setValue(Insurance::NEXT_GDV_NUMBER_SETTING, '41');

    $first = Insurance::factory()->create();
    $second = Insurance::factory()->create(['amount' => 0]);
    $second->delete();
    $third = Insurance::factory()->create();

    expect($first->gdv_number)->toBe(41)
        ->and($second->gdv_number)->toBe(42)
        ->and($third->gdv_number)->toBe(43)
        ->and(Setting::getValue(Insurance::NEXT_GDV_NUMBER_SETTING))->toBe('44');
});

test('the counter never hands out a number below the highest existing one', function () {
    Insurance::factory()->create(['gdv_number' => 90]);
    Setting::setValue(Insurance::NEXT_GDV_NUMBER_SETTING, '5');

    expect(Insurance::factory()->create()->gdv_number)->toBe(91);
});

test('the unique index rejects a duplicate number', function () {
    $partner = Insurance::factory()->create();

    expect(fn () => Insurance::factory()->create(['gdv_number' => $partner->gdv_number]))
        ->toThrow(QueryException::class);
});

test('memberCode and memberTitle show the human number', function () {
    $partner = Insurance::factory()->create(['gdv_number' => 5]);

    expect($partner->memberCode())->toBe('GDV số 5')
        ->and($partner->memberTitle())->toBe('Giao dịch viên số 5');
});

test('the migration numbers existing partners 1..N by id, sets the counter and is reversible', function () {
    config(['database.connections.gdv_backfill' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
    $default = DB::getDefaultConnection();
    DB::setDefaultConnection('gdv_backfill');

    try {
        Schema::create('insurances', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
        });
        Schema::create('settings', function (Blueprint $table) {
            $table->string('name')->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });
        DB::table('insurances')->insert([['full_name' => 'A'], ['full_name' => 'B'], ['full_name' => 'C'], ['full_name' => 'D']]);
        DB::table('insurances')->where('full_name', 'B')->delete();

        $migration = require database_path('migrations/2026_11_17_155631_add_gdv_number_to_insurances_table.php');
        $migration->up();

        expect(DB::table('insurances')->orderBy('id')->pluck('gdv_number', 'full_name')->all())->toBe(['A' => 1, 'C' => 2, 'D' => 3])
            ->and(DB::table('settings')->where('name', 'gdv_next_number')->value('value'))->toBe('4')
            ->and(fn () => DB::table('insurances')->insert(['full_name' => 'E']))->toThrow(QueryException::class)
            ->and(fn () => DB::table('insurances')->insert(['full_name' => 'F', 'gdv_number' => 1]))->toThrow(QueryException::class);

        $migration->down();

        expect(Schema::hasColumn('insurances', 'gdv_number'))->toBeFalse()
            ->and(DB::table('insurances')->count())->toBe(3)
            ->and(DB::table('settings')->count())->toBe(0);
    } finally {
        DB::setDefaultConnection($default);
        DB::purge('gdv_backfill');
    }
});

test('admins see the number read-only and cannot change it', function () {
    $admin = User::factory()->create(['role' => 'admin', 'status' => 1]);
    $partner = Insurance::factory()->trusted()->create(['gdv_number' => 7, 'full_name' => 'GDV Bảy']);

    $this->actingAs($admin)->get(route('admin.insurances.edit', $partner))
        ->assertOk()
        ->assertSee('value="Giao dịch viên số 7" readonly disabled', false);

    $this->actingAs($admin)->get(route('admin.insurances.show', $partner))
        ->assertOk()
        ->assertSee('Giao dịch viên số 7');

    $partner->update(['gdv_number' => 99]);

    expect($partner->fresh()->gdv_number)->toBe(7);
});

test('the admin list shows the number, sorts by it and finds a partner by it', function () {
    $admin = User::factory()->create(['role' => 'admin', 'status' => 1]);
    Insurance::factory()->create(['gdv_number' => 1, 'full_name' => 'GDV Một']);
    Insurance::factory()->create(['gdv_number' => 2, 'full_name' => 'GDV Hai']);

    $this->actingAs($admin)->get(route('admin.insurances.index'))
        ->assertOk()
        ->assertSee('Số GDV')
        ->assertSeeInOrder(['GDV số 2', 'GDV Hai', 'GDV số 1', 'GDV Một']);

    $this->actingAs($admin)->get(route('admin.insurances.index', ['sort' => 'number_asc']))
        ->assertOk()
        ->assertSeeInOrder(['GDV Một', 'GDV Hai']);

    $this->actingAs($admin)->get(route('admin.insurances.index', ['search' => 'gdv số 2']))
        ->assertOk()
        ->assertSee('GDV Hai')
        ->assertDontSee('GDV Một');
});

test('the public list is ordered by number, shows it and finds a partner by number', function (string $search) {
    Insurance::factory()->trusted()->create(['gdv_number' => 6, 'full_name' => 'GDV Sáu']);
    Insurance::factory()->trusted()->create(['gdv_number' => 5, 'full_name' => 'GDV Năm']);
    Insurance::factory()->trusted()->create(['gdv_number' => 15, 'full_name' => 'GDV Mười Lăm']);

    $this->get(route('insurances.frontend.index'))
        ->assertOk()
        ->assertSeeInOrder(['GDV số 5', 'GDV Năm', 'GDV số 6', 'GDV Sáu', 'GDV số 15', 'GDV Mười Lăm']);

    $this->get(route('insurances.frontend.index', ['search' => $search]))
        ->assertOk()
        ->assertSee('GDV Năm')
        ->assertDontSee('GDV Sáu')
        ->assertDontSee('GDV Mười Lăm');
})->with(['5', 'gdv 5', 'GDV số 5', 'giao dịch viên số 5']);

test('the public list still searches by name', function () {
    Insurance::factory()->trusted()->create(['gdv_number' => 5, 'full_name' => 'Trần Văn Năm', 'slug' => 'tran-van-nam']);
    Insurance::factory()->trusted()->create(['gdv_number' => 6, 'full_name' => 'Lê Thị Sáu', 'slug' => 'le-thi-sau']);

    $this->get(route('insurances.frontend.index', ['search' => 'Trần']))
        ->assertOk()
        ->assertSee('Trần Văn Năm')
        ->assertDontSee('Lê Thị Sáu');
});

test('the home partner cards show the number', function () {
    Insurance::factory()->trusted()->create(['gdv_number' => 12, 'full_name' => 'GDV Mười Hai']);

    $html = $this->get('/')->assertOk()->getContent();
    $section = (string) str($html)->after('aria-label="Trung gian đã ký quỹ"')->before('</section>');

    expect($section)->toContain('GDV số 12');
});

test('the verify page finds a partner by number', function () {
    Insurance::factory()->trusted()->create(['gdv_number' => 5, 'full_name' => 'GDV Năm']);
    Insurance::factory()->trusted()->expired()->create(['gdv_number' => 6, 'full_name' => 'GDV Sáu']);

    $this->get(route('intermediary.verify', ['q' => 'GDV số 5']))
        ->assertOk()
        ->assertSee('data-verify-state="verified"', false)
        ->assertSee('GDV số 5 là GDV Năm')
        ->assertSee('Giao dịch viên số 5')
        ->assertSee('Tra theo số giao dịch viên');

    $this->get(route('intermediary.verify', ['q' => '6']))
        ->assertOk()
        ->assertSee('data-verify-state="lapsed"', false)
        ->assertSee('GDV Sáu')
        ->assertSee('hết hạn bảo hiểm');
});

test('a number never shadows a channel match', function () {
    $owner = Insurance::factory()->trusted()->create(['gdv_number' => 1]);
    Insurance::factory()->trusted()->create(['gdv_number' => 12345]);
    InsuranceChannel::factory()->for($owner)->ofType(ChannelType::BankAccount, '12345')->create();

    $result = app(IntermediaryVerifier::class)->verify('12345');

    expect($result->insurance->is($owner))->toBeTrue()
        ->and($result->channel)->not->toBeNull();
});
