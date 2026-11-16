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
        Schema::table('insurances', function (Blueprint $table) {
            $table->date('verified_at')->nullable()->after('expired_at');
            $table->timestamp('suspended_at')->nullable()->after('status');
            $table->string('suspension_reason')->nullable()->after('suspended_at');
        });

        $this->backfillVerifiedAt();
    }

    /**
     * Existing partners were verified when they joined.
     */
    public function backfillVerifiedAt(): void
    {
        DB::table('insurances')
            ->whereNull('verified_at')
            ->update(['verified_at' => DB::raw('insurance_date')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('insurances', function (Blueprint $table) {
            $table->dropColumn(['verified_at', 'suspended_at', 'suspension_reason']);
        });
    }
};
