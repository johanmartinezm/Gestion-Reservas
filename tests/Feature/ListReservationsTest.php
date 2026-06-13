<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Reservation;
use App\Models\Service;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListReservationsTest extends TestCase
{
    use RefreshDatabase;

    private function reservationAt(User $user, string $start): Reservation
    {
        $service = Service::factory()->duration(30)->create();
        $startsAt = CarbonImmutable::parse($start, 'America/Bogota');

        return Reservation::factory()->create([
            'user_id' => $user->id,
            'service_id' => $service->id,
            'professional_id' => $service->professional_id,
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->addMinutes(30),
            'price_cents' => $service->price_cents,
        ]);
    }

    public function test_lists_only_reservations_in_range_for_the_user(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $this->reservationAt($user, '2026-07-08 10:00'); // dentro
        $this->reservationAt($user, '2026-07-25 10:00'); // fuera
        $this->reservationAt($other, '2026-07-09 10:00'); // otro usuario

        $response = $this->getJson("/api/v1/users/{$user->id}/reservations?from=2026-07-01&to=2026-07-15");

        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_includes_reservations_on_the_last_day_of_the_range(): void
    {
        $user = User::factory()->create();
        // Reserva por la tarde del día 'to' -> debe incluirse (rango inclusivo hasta fin del día).
        $this->reservationAt($user, '2026-07-15 16:00');

        $response = $this->getJson("/api/v1/users/{$user->id}/reservations?from=2026-07-01&to=2026-07-15");

        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_returns_empty_list_when_no_reservations_in_range(): void
    {
        $user = User::factory()->create();
        $this->reservationAt($user, '2026-07-25 10:00');

        $response = $this->getJson("/api/v1/users/{$user->id}/reservations?from=2026-07-01&to=2026-07-10");

        $response->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_rejects_invalid_date_range(): void
    {
        $user = User::factory()->create();

        $response = $this->getJson("/api/v1/users/{$user->id}/reservations?from=2026-07-15&to=2026-07-01");

        $response->assertStatus(422)->assertJsonValidationErrors(['to']);
    }
}
