<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            // decimal(10,2) topped out at 99,999,999 VND; large frauds go past 10 billion.
            $table->decimal('damage_amount', 15, 2)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Lossy: amounts above 99,999,999.99 are capped so the narrower column can hold them.
        DB::table('reports')->where('damage_amount', '>', 99999999.99)->update(['damage_amount' => 99999999.99]);

        Schema::table('reports', function (Blueprint $table) {
            $table->decimal('damage_amount', 10, 2)->nullable()->change();
        });
    }
};
