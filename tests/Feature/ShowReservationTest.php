<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Reservation;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowReservationTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_a_reservation_by_id(): void
    {
        $service = Service::factory()->create();
        $reservation = Reservation::factory()->create([
            'user_id' => User::factory(),
            'service_id' => $service->id,
            'professional_id' => $service->professional_id,
            'price_cents' => $service->price_cents,
        ]);

        $response = $this->getJson("/api/v1/reservations/{$reservation->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $reservation->id)
            ->assertJsonPath('data.status', 'active');
    }

    public function test_returns_problem_json_404_for_missing_reservation(): void
    {
        $response = $this->getJson('/api/v1/reservations/999');

        $response->assertNotFound()
            ->assertHeader('Content-Type', 'application/problem+json')
            ->assertJsonPath('type', '/problems/not-found')
            ->assertJsonPath('status', 404);
    }
}
