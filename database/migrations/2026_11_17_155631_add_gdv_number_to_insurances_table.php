<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Same key as Insurance::NEXT_GDV_NUMBER_SETTING (kept literal so the migration does not depend on the model). */
    private const NEXT_NUMBER_SETTING = 'gdv_next_number';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('insurances', function (Blueprint $table) {
            $table->unsignedInteger('gdv_number')->nullable()->after('id');
        });

        $this->backfillGdvNumbers();

        Schema::table('insurances', function (Blueprint $table) {
            $table->unsignedInteger('gdv_number')->nullable(false)->change();
            $table->unique('gdv_number');
        });
    }

    /**
     * Numbers partners without one 1..N by id (continuing after the highest number) and moves the counter past them.
     */
    public function backfillGdvNumbers(): void
    {
        $next = (int) DB::table('insurances')->max('gdv_number') + 1;

        DB::table('insurances')->whereNull('gdv_number')->orderBy('id')->select('id')
            ->chunkById(500, function ($rows) use (&$next) {
                foreach ($rows as $row) {
                    DB::table('insurances')->where('id', $row->id)->update(['gdv_number' => $next++]);
                }
            });

        $current = (int) DB::table('settings')->where('name', self::NEXT_NUMBER_SETTING)->value('value');

        DB::table('settings')->updateOrInsert(
            ['name' => self::NEXT_NUMBER_SETTING],
            ['value' => (string) max($current, $next), 'created_at' => now(), 'updated_at' => now()],
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('insurances', function (Blueprint $table) {
            $table->dropUnique(['gdv_number']);
            $table->dropColumn('gdv_number');
        });

        DB::table('settings')->where('name', self::NEXT_NUMBER_SETTING)->delete();
    }
};
