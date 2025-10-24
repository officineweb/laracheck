<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Exception>
 */
class ExceptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $exceptions = [
            'Illuminate\Database\QueryException',
            'ErrorException',
            'Symfony\Component\HttpKernel\Exception\NotFoundHttpException',
            'PDOException',
            'BadMethodCallException',
            'InvalidArgumentException',
            'RuntimeException',
        ];

        $files = [
            'app/Http/Controllers/UserController.php',
            'app/Models/User.php',
            'app/Services/PaymentService.php',
            'database/migrations/create_users_table.php',
            'routes/web.php',
            'app/Http/Middleware/CheckAuth.php',
        ];

        $methods = [
            'handle',
            'store',
            'update',
            'destroy',
            'index',
            'show',
            'create',
            'edit',
        ];

        $messages = [
            'Call to undefined method',
            'SQLSTATE[23000]: Integrity constraint violation',
            'Class not found',
            'Trying to access array offset on value of type null',
            'Division by zero',
            'Undefined variable',
            'Attempt to read property on null',
        ];

        $exceptionType = fake()->randomElement($exceptions);
        $file = fake()->randomElement($files);
        $line = fake()->numberBetween(10, 500);

        $createdAt = fake()->dateTimeBetween('-1 year', 'now');

        return [
            'host' => fake()->domainName(),
            'env' => fake()->randomElement(['local', 'staging', 'production']),
            'method' => fake()->randomElement(['GET', 'POST', 'PUT', 'DELETE']),
            'full_url' => fake()->url(),
            'exception' => $exceptionType . ': ' . fake()->randomElement($messages),
            'error' => fake()->randomElement($messages),
            'line' => $line,
            'file' => $file,
            'class' => $exceptionType,
            'code' => fake()->randomElement([500, 503, 502, 500]),
            'storage' => [
                'framework' => 'Laravel',
                'version' => '12.x',
            ],
            'executor' => [
                'method' => fake()->randomElement($methods),
                'file' => $file,
                'line' => $line,
            ],
            'user' => fake()->optional(0.6)->passthrough([
                'id' => fake()->numberBetween(1, 100),
                'email' => fake()->email(),
                'name' => fake()->name(),
            ]),
            'additional' => [
                'request_data' => fake()->optional(0.5)->passthrough([
                    'ip' => fake()->ipv4(),
                    'user_agent' => fake()->userAgent(),
                ]),
            ],
            'mailed' => fake()->boolean(30),
            'notified_at' => fake()->optional(0.4)->dateTimeBetween($createdAt, 'now'),
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ];
    }

    public function open(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => \App\Models\Exception::OPEN,
        ]);
    }

    public function fixed(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => \App\Models\Exception::FIXED,
        ]);
    }

    public function clientError(): static
    {
        return $this->state(fn(array $attributes) => [
            'code' => fake()->randomElement([400, 401, 403, 404, 405, 422, 429]),
            'status' => \App\Models\Exception::FIXED,
            'notified_at' => now(),
        ]);
    }
}
