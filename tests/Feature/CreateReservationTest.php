<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Models\Service;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CreateReservationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Martes 2026-07-07, 08:00 hora Bogotá.
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-07-07 08:00', 'America/Bogota'));
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_creates_a_reservation_with_valid_data(): void
    {
        $user = User::factory()->create();
        $service = Service::factory()->duration(60)->create();

        $response = $this->postJson('/api/v1/reservations', [
            'user_id' => $user->id,
            'service_id' => $service->id,
            'starts_at' => '2026-07-07 11:00',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.user_id', $user->id);

        $this->assertDatabaseHas('reservations', [
            'user_id' => $user->id,
            'service_id' => $service->id,
            'status' => ReservationStatus::Active->value,
        ]);

        $reservation = Reservation::first();
        $this->assertSame(60, (int) $reservation->starts_at->diffInMinutes($reservation->ends_at));
    }

    public function test_rejects_reservation_with_insufficient_lead_time(): void
    {
        $user = User::factory()->create();
        $service = Service::factory()->create();

        $response = $this->postJson('/api/v1/reservations', [
            'user_id' => $user->id,
            'service_id' => $service->id,
            'starts_at' => '2026-07-07 09:00', // sólo 1 hora después
        ]);

        $response->assertStatus(422)->assertJsonPath('code', 'insufficient_lead_time');
    }

    public function test_rejects_reservation_on_sunday(): void
    {
        $user = User::factory()->create();
        $service = Service::factory()->create();

        $response = $this->postJson('/api/v1/reservations', [
            'user_id' => $user->id,
            'service_id' => $service->id,
            'starts_at' => '2026-07-12 10:00', // domingo
        ]);

        $response->assertStatus(422)->assertJsonPath('code', 'outside_operating_hours');
    }

    public function test_rejects_reservation_on_holiday(): void
    {
        $user = User::factory()->create();
        $service = Service::factory()->create();

        $response = $this->postJson('/api/v1/reservations', [
            'user_id' => $user->id,
            'service_id' => $service->id,
            'starts_at' => '2026-07-20 10:00', // festivo (Independencia)
        ]);

        $response->assertStatus(422)->assertJsonPath('code', 'outside_operating_hours');
    }

    public function test_rejects_overlapping_reservation_for_same_professional(): void
    {
        $service = Service::factory()->duration(60)->create();
        $start = Carbon::parse('2026-07-07 11:00', 'America/Bogota');

        Reservation::factory()->create([
            'user_id' => User::factory(),
            'service_id' => $service->id,
            'professional_id' => $service->professional_id,
            'starts_at' => $start,
            'ends_at' => $start->copy()->addHour(),
            'price_cents' => $service->price_cents,
        ]);

        $user = User::factory()->create();
        $response = $this->postJson('/api/v1/reservations', [
            'user_id' => $user->id,
            'service_id' => $service->id,
            'starts_at' => '2026-07-07 11:30',
        ]);

        $response->assertStatus(422)->assertJsonPath('code', 'overlapping_reservation');
    }

    public function test_rejects_when_user_exceeds_active_reservation_limit(): void
    {
        $user = User::factory()->create();

        // 3 reservas activas futuras (límite alcanzado), cada una con su servicio.
        for ($i = 0; $i < 3; $i++) {
            $service = Service::factory()->duration(30)->create();
            $start = CarbonImmutable::parse('2026-07-08 10:00', 'America/Bogota')->addDays($i);
            Reservation::factory()->create([
                'user_id' => $user->id,
                'service_id' => $service->id,
                'professional_id' => $service->professional_id,
                'starts_at' => $start,
                'ends_at' => $start->addMinutes(30),
                'price_cents' => $service->price_cents,
            ]);
        }

        $service = Service::factory()->create();
        $response = $this->postJson('/api/v1/reservations', [
            'user_id' => $user->id,
            'service_id' => $service->id,
            'starts_at' => '2026-07-07 11:00',
        ]);

        $response->assertStatus(422)->assertJsonPath('code', 'active_reservation_limit');
    }

    public function test_rejects_invalid_payload(): void
    {
        $response = $this->postJson('/api/v1/reservations', [
            'starts_at' => 'not-a-date',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['user_id', 'service_id', 'starts_at']);
    }

    public function test_domain_errors_use_problem_json_envelope(): void
    {
        $user = User::factory()->create();
        $service = Service::factory()->create();

        $response = $this->postJson('/api/v1/reservations', [
            'user_id' => $user->id,
            'service_id' => $service->id,
            'starts_at' => '2026-07-12 10:00', // domingo
        ]);

        $response->assertStatus(422)
            ->assertHeader('Content-Type', 'application/problem+json')
            ->assertJsonPath('type', '/problems/outside_operating_hours')
            ->assertJsonPath('status', 422)
            ->assertJsonPath('code', 'outside_operating_hours')
            ->assertJsonStructure(['type', 'title', 'status', 'detail', 'code']);
    }
}
