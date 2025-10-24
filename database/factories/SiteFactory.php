<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Site>
 */
class SiteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $appName = fake()->randomElement([
            'E-commerce Platform',
            'Blog System',
            'CRM Application',
            'Portfolio Site',
            'API Service',
            'Dashboard App',
            'Social Network',
            'Booking System',
        ]);

        return [
            'name' => $appName . ' ' . fake()->unique()->numberBetween(1, 999999),
            'url' => fake()->unique()->url(),
            'key' => \Illuminate\Support\Str::random(32),
            'description' => fake()->optional(0.7)->sentence(),
            'receive_email' => fake()->boolean(80),
            'slack_webhook' => fake()->optional(0.3)->url(),
            'discord_webhook' => fake()->optional(0.2)->url(),
            'last_exception_at' => fake()->optional(0.5)->dateTimeBetween('-30 days', 'now'),
            'check_url' => fake()->boolean(70) ? fake()->unique()->url() : null,
            'enable_uptime_check' => fake()->boolean(70),
            'email_outage' => fake()->optional(0.8)->safeEmail(),
            'email_resolved' => fake()->optional(0.7)->safeEmail(),
            'checked_at' => fake()->optional(0.8)->dateTimeBetween('-1 day', 'now'),
            'is_online' => true,
        ];
    }

    public function offline(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_online' => false,
            'checked_at' => now(),
        ]);
    }

    public function withUptimeCheck(): static
    {
        return $this->state(fn(array $attributes) => [
            'enable_uptime_check' => true,
            'check_url' => fake()->unique()->url(),
        ]);
    }
}
