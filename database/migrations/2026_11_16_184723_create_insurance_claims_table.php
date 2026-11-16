<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('insurance_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('insurance_id')->constrained('insurances')->cascadeOnDelete();
            $table->string('claimant_name');
            $table->string('contact_email');
            $table->string('contact_phone', 20)->nullable();
            $table->decimal('amount_claimed', 15, 2);
            $table->text('description');
            $table->json('evidence_paths')->nullable();
            $table->string('status', 16)->default('submitted');
            $table->text('public_summary')->nullable();
            $table->text('decision_note')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->decimal('approved_amount', 15, 2)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['insurance_id', 'status']);
            $table->index('status');
            $table->index(['ip_address', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('insurance_claims');
    }
};
