<?php

namespace Database\Factories;

use App\Enums\ChannelType;
use App\Models\Insurance;
use App\Models\InsuranceChannel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * normalized_value is filled by the model's saving hook.
 *
 * @extends Factory<InsuranceChannel>
 */
class InsuranceChannelFactory extends Factory
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
            'type' => ChannelType::Phone,
            'value' => fake()->unique()->numerify('09########'),
            'is_official' => true,
            'verified_at' => now(),
        ];
    }

    public function ofType(ChannelType $type, string $value): static
    {
        return $this->state(fn (array $attributes) => ['type' => $type, 'value' => $value]);
    }

    public function unofficial(): static
    {
        return $this->state(fn (array $attributes) => ['is_official' => false]);
    }
}
