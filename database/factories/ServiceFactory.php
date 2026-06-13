<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Professional;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    protected $model = Service::class;

    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Corte', 'Masaje', 'Manicure', 'Consulta']),
            'duration_minutes' => fake()->randomElement([30, 60, 90]),
            'price_cents' => fake()->numberBetween(2000, 20000) * 100,
            'non_refundable' => false,
            'professional_id' => Professional::factory(),
        ];
    }

    public function nonRefundable(): static
    {
        return $this->state(fn (array $attributes) => [
            'non_refundable' => true,
        ]);
    }

    public function duration(int $minutes): static
    {
        return $this->state(fn (array $attributes) => [
            'duration_minutes' => $minutes,
        ]);
    }
}
