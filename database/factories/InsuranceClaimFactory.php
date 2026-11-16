<?php

namespace Database\Factories;

use App\Enums\ClaimStatus;
use App\Models\Insurance;
use App\Models\InsuranceClaim;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Status states only set columns; they write no events or ledger entries.
 * Use InsuranceClaimService to exercise the workflow.
 *
 * @extends Factory<InsuranceClaim>
 */
class InsuranceClaimFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'insurance_id' => Insurance::factory(),
            'claimant_name' => fake()->name(),
            'contact_email' => fake()->safeEmail(),
            'contact_phone' => fake()->numerify('09########'),
            'amount_claimed' => fake()->randomElement([500000, 1000000, 2000000, 3000000]),
            'description' => fake()->paragraph(3),
            'evidence_paths' => null,
            'status' => ClaimStatus::Submitted,
            'ip_address' => fake()->ipv4(),
        ];
    }

    public function submitted(): static
    {
        return $this->state(fn (array $attributes) => ['status' => ClaimStatus::Submitted]);
    }

    public function reviewing(): static
    {
        return $this->state(fn (array $attributes) => ['status' => ClaimStatus::Reviewing]);
    }

    public function needInfo(): static
    {
        return $this->state(fn (array $attributes) => ['status' => ClaimStatus::NeedInfo]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ClaimStatus::Approved,
            'approved_amount' => $attributes['amount_claimed'],
            'decided_by' => User::factory(),
            'decided_at' => now(),
            'public_summary' => 'Người mua chuyển tiền nhưng không nhận được hàng; đủ bằng chứng.',
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ClaimStatus::Rejected,
            'decided_by' => User::factory(),
            'decided_at' => now(),
            'decision_note' => fake()->sentence(),
        ]);
    }

    public function paid(): static
    {
        return $this->approved()->state(fn (array $attributes) => [
            'status' => ClaimStatus::Paid,
            'paid_at' => now(),
        ]);
    }
}
