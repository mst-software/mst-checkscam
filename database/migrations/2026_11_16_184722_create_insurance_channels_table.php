<?php

use App\Models\InsuranceChannel;
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
        Schema::create('insurance_channels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('insurance_id')->constrained('insurances')->cascadeOnDelete();
            $table->string('type', 32);
            $table->string('value', 500);
            $table->string('normalized_value')->index();
            $table->boolean('is_official')->default(true);
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->unique(['insurance_id', 'type', 'normalized_value']);
        });

        $this->backfillChannels();
    }

    /**
     * Copy the free-form contact_info / payment_accounts JSON into channel rows.
     * Unknown shapes are skipped; re-running only inserts what is missing.
     */
    public function backfillChannels(): void
    {
        DB::table('insurances')
            ->select(['id', 'contact_info', 'payment_accounts'])
            ->orderBy('id')
            ->chunk(200, function ($insurances) {
                $now = now();
                $rows = [];

                foreach ($insurances as $insurance) {
                    $channels = InsuranceChannel::fromLegacyJson(
                        $this->decode($insurance->contact_info),
                        $this->decode($insurance->payment_accounts),
                    );

                    foreach ($channels as $channel) {
                        $rows[] = [
                            'insurance_id' => $insurance->id,
                            'type' => $channel['type']->value,
                            'value' => $channel['value'],
                            'normalized_value' => $channel['normalized_value'],
                            'is_official' => true,
                            'verified_at' => null,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                }

                if ($rows !== []) {
                    DB::table('insurance_channels')->insertOrIgnore($rows);
                }
            });
    }

    private function decode(mixed $json): mixed
    {
        return is_string($json) ? json_decode($json, true) : $json;
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('insurance_channels');
    }
};
