<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Models\Service;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CancelReservationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-07-07 08:00', 'America/Bogota'));
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    private function reservationFor(User $user, Service $service, string $start): Reservation
    {
        $startsAt = CarbonImmutable::parse($start, 'America/Bogota');

        return Reservation::factory()->create([
            'user_id' => $user->id,
            'service_id' => $service->id,
            'professional_id' => $service->professional_id,
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->addMinutes($service->duration_minutes),
            'price_cents' => $service->price_cents,
        ]);
    }

    public function test_standard_user_cancelling_more_than_24h_ahead_gets_full_refund(): void
    {
        $user = User::factory()->create();
        $service = Service::factory()->create(['price_cents' => 100000]);
        $reservation = $this->reservationFor($user, $service, '2026-07-10 10:00'); // ~3 días

        $response = $this->postJson("/api/v1/reservations/{$reservation->id}/cancel");

        $response->assertOk()
            ->assertJsonPath('data.status', 'cancelled')
            ->assertJsonPath('data.refund_cents', 100000);
    }

    public function test_non_refundable_service_gets_no_refund_but_is_cancelled(): void
    {
        $user = User::factory()->create();
        $service = Service::factory()->nonRefundable()->create(['price_cents' => 100000]);
        $reservation = $this->reservationFor($user, $service, '2026-07-10 10:00');

        $response = $this->postJson("/api/v1/reservations/{$reservation->id}/cancel");

        $response->assertOk()
            ->assertJsonPath('data.status', 'cancelled')
            ->assertJsonPath('data.refund_cents', 0);
    }

    public function test_premium_user_cancelling_three_hours_ahead_gets_half_refund(): void
    {
        $user = User::factory()->premium()->create();
        $service = Service::factory()->create(['price_cents' => 100000]);
        // ahora = 08:00; inicio 11:00 -> 3h antes (tramo premium 4h–1h = 50%)
        $reservation = $this->reservationFor($user, $service, '2026-07-07 11:00');

        $response = $this->postJson("/api/v1/reservations/{$reservation->id}/cancel");

        $response->assertOk()->assertJsonPath('data.refund_cents', 50000);
    }

    public function test_cannot_cancel_an_already_cancelled_reservation(): void
    {
        $user = User::factory()->create();
        $service = Service::factory()->create();
        $reservation = Reservation::factory()->cancelled()->create([
            'user_id' => $user->id,
            'service_id' => $service->id,
            'professional_id' => $service->professional_id,
            'price_cents' => $service->price_cents,
        ]);

        $response = $this->postJson("/api/v1/reservations/{$reservation->id}/cancel");

        $response->assertStatus(409)->assertJsonPath('code', 'reservation_not_cancellable');
        $this->assertSame(ReservationStatus::Cancelled, $reservation->fresh()->status);
    }

    public function test_cancelling_a_missing_reservation_returns_404(): void
    {
        $this->postJson('/api/v1/reservations/999/cancel')->assertNotFound();
    }
}
