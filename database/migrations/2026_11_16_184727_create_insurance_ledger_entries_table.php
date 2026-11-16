<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Append-only: the app refuses updates and deletes (see InsuranceLedgerEntry). The database cannot
     * enforce that without triggers, and deleting a partner still cascades to its entries.
     */
    public function up(): void
    {
        Schema::create('insurance_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('insurance_id')->constrained('insurances')->cascadeOnDelete();
            $table->string('type', 16);
            $table->decimal('amount', 15, 2);
            $table->decimal('balance_after', 15, 2);
            $table->foreignId('claim_id')->nullable()->constrained('insurance_claims')->nullOnDelete();
            $table->string('reference')->nullable();
            $table->text('note')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->index(['insurance_id', 'id']);
        });

        $this->backfillOpeningBalances();
    }

    /**
     * Give every partner without ledger history one opening deposit equal to its current amount.
     */
    public function backfillOpeningBalances(): void
    {
        $now = now();

        DB::table('insurances')
            ->where('amount', '>', 0)
            ->whereNotExists(function (Builder $query) {
                $query->select(DB::raw(1))
                    ->from('insurance_ledger_entries')
                    ->whereColumn('insurance_ledger_entries.insurance_id', 'insurances.id');
            })
            ->orderBy('id')
            ->select(['id', 'amount'])
            ->chunk(200, function ($insurances) use ($now) {
                DB::table('insurance_ledger_entries')->insert($insurances->map(fn ($insurance) => [
                    'insurance_id' => $insurance->id,
                    'type' => 'deposit',
                    'amount' => $insurance->amount,
                    'balance_after' => $insurance->amount,
                    'note' => 'Số dư ban đầu',
                    'created_at' => $now,
                ])->all());
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('insurance_ledger_entries');
    }
};
