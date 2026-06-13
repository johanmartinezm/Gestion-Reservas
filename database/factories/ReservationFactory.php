<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Reservation>
 */
class ReservationFactory extends Factory
{
    protected $model = Reservation::class;

    public function definition(): array
    {
        $service = Service::factory()->create();
        $startsAt = now()->addDay()->setTime(10, 0);

        return [
            'user_id' => User::factory(),
            'service_id' => $service->id,
            'professional_id' => $service->professional_id,
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addMinutes($service->duration_minutes),
            'status' => ReservationStatus::Active,
            'price_cents' => $service->price_cents,
            'refund_cents' => null,
            'cancelled_at' => null,
        ];
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ReservationStatus::Cancelled,
            'cancelled_at' => now(),
        ]);
    }

    public function forService(Service $service): static
    {
        return $this->state(fn (array $attributes) => [
            'service_id' => $service->id,
            'professional_id' => $service->professional_id,
            'price_cents' => $service->price_cents,
            'ends_at' => $this->resolveStart($attributes)->copy()->addMinutes($service->duration_minutes),
        ]);
    }

    public function startingAt(\DateTimeInterface $startsAt, int $durationMinutes = 60): static
    {
        $start = Carbon::instance(
            \DateTime::createFromInterface($startsAt)
        );

        return $this->state(fn (array $attributes) => [
            'starts_at' => $start,
            'ends_at' => $start->copy()->addMinutes($durationMinutes),
        ]);
    }

    private function resolveStart(array $attributes): Carbon
    {
        return isset($attributes['starts_at'])
            ? Carbon::parse($attributes['starts_at'])
            : now()->addDay()->setTime(10, 0);
    }
}
