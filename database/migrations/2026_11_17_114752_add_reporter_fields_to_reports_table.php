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
            // A string instead of the old enum, so new report types need no schema change.
            $table->string('type', 20)->default('account')->change();
            $table->string('reporter_email')->nullable()->after('reporter_contact');
            $table->string('reporter_role', 10)->nullable()->after('reporter_email');
            $table->string('target_socials')->nullable()->after('target_bank');
            $table->string('sold_account')->nullable()->after('target_socials');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Destructive: reporter_email, reporter_role, target_socials and sold_account are dropped with their data.
        // The old enum has no "back" value; those reports fall back to plain account reports.
        DB::table('reports')->whereNotIn('type', ['account', 'website'])->update(['type' => 'account']);

        Schema::table('reports', function (Blueprint $table) {
            $table->enum('type', ['account', 'website'])->default('account')->change();
            $table->dropColumn(['reporter_email', 'reporter_role', 'target_socials', 'sold_account']);
        });
    }
};
