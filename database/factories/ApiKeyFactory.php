<?php

namespace Database\Factories;

use App\Models\ApiKey;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApiKey>
 *
 * Use ->withPlainKey('mst_...') when a test needs to authenticate with the key.
 */
class ApiKeyFactory extends Factory
{
    protected $model = ApiKey::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $plainKey = ApiKey::generatePlainKey();

        return [
            'user_id' => null,
            'name' => $this->faker->company(),
            'key_hash' => ApiKey::hashKey($plainKey),
            'key_prefix' => substr($plainKey, 0, 12),
            'rate_limit_per_minute' => 30,
            'last_used_at' => null,
            'is_active' => true,
        ];
    }

    public function withPlainKey(string $plainKey): static
    {
        return $this->state(fn () => [
            'key_hash' => ApiKey::hashKey($plainKey),
            'key_prefix' => substr($plainKey, 0, 12),
        ]);
    }

    public function revoked(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
