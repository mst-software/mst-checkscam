<?php

namespace Database\Factories;

use App\Enums\LedgerEntryType;
use App\Models\Insurance;
use App\Models\InsuranceLedgerEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Raw rows for display tests. It does not update the partner's cached balance:
 * use InsuranceLedgerService::record() when balances matter.
 *
 * @extends Factory<InsuranceLedgerEntry>
 */
class InsuranceLedgerEntryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $amount = fake()->randomElement([1000000, 5000000, 10000000]);

        return [
            'insurance_id' => Insurance::factory(),
            'type' => LedgerEntryType::Deposit,
            'amount' => $amount,
            'balance_after' => $amount,
            'reference' => fake()->bothify('FT##########'),
            'note' => null,
            'recorded_by' => null,
        ];
    }
}
