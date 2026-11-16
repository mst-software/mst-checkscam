<?php

namespace Database\Factories;

use App\Enums\ComplaintReason;
use App\Enums\ComplaintStatus;
use App\Models\Complaint;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Complaint>
 */
class ComplaintFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'report_id' => null,
            'contact_name' => fake()->name(),
            'contact_email' => fake()->safeEmail(),
            'contact_phone' => fake()->numerify('09########'),
            'reason' => fake()->randomElement(ComplaintReason::cases()),
            'message' => fake()->paragraph(3),
            'evidence_images' => null,
            'status' => ComplaintStatus::Pending,
            'ip_address' => fake()->ipv4(),
        ];
    }

    public function resolved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ComplaintStatus::Resolved,
            'admin_note' => fake()->sentence(),
            'handled_by' => User::factory(),
            'handled_at' => now(),
        ]);
    }

    public function dismissed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ComplaintStatus::Dismissed,
            'admin_note' => fake()->sentence(),
            'handled_by' => User::factory(),
            'handled_at' => now(),
        ]);
    }
}
