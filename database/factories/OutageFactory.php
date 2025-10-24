<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Outage>
 */
class OutageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $occurredAt = fake()->dateTimeBetween('-1 year', 'now');
        $isResolved = fake()->boolean(70);

        $minutesDown = fake()->numberBetween(1, 30);

        return [
            'occurred_at' => $occurredAt,
            'resolved_at' => $isResolved
                ? (clone $occurredAt)->modify("+{$minutesDown} minutes")
                : null,
            'response_code' => fake()->randomElement([500, 502, 503, 504, 0, null]),
            'error_message' => fake()->randomElement([
                'Connection timeout',
                'Server not responding',
                'DNS resolution failed',
                'SSL certificate error',
                'HTTP 500 Internal Server Error',
                'HTTP 502 Bad Gateway',
                'HTTP 503 Service Unavailable',
                'Connection refused',
            ]),
        ];
    }

    public function active(): static
    {
        return $this->state(fn(array $attributes) => [
            'resolved_at' => null,
            'occurred_at' => fake()->dateTimeBetween('-1 year', 'now'),
        ]);
    }

    public function resolved(): static
    {
        $occurredAt = fake()->dateTimeBetween('-1 year', 'now');
        $minutesDown = fake()->numberBetween(1, 30);

        return $this->state(fn(array $attributes) => [
            'occurred_at' => $occurredAt,
            'resolved_at' => (clone $occurredAt)->modify("+{$minutesDown} minutes"),
        ]);
    }
}
