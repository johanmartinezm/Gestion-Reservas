<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Reservation;
use App\Models\Service;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class AvailabilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Lunes 2026-07-06, 08:00 hora Bogotá.
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-07-06 08:00', 'America/Bogota'));
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    private function query(Service $service, string $date): TestResponse
    {
        return $this->getJson(
            "/api/professionals/{$service->professional_id}/availability?date={$date}&service_id={$service->id}"
        );
    }

    public function test_free_day_returns_all_slots(): void
    {
        // Servicio de 60 min: franja 07:00–19:00 => 12 slots (07..18).
        $service = Service::factory()->duration(60)->create();

        $response = $this->query($service, '2026-07-07'); // martes futuro

        $response->assertOk()->assertJsonCount(12, 'data.slots');
    }

    public function test_booked_slot_is_excluded(): void
    {
        $service = Service::factory()->duration(60)->create();
        $start = CarbonImmutable::parse('2026-07-07 10:00', 'America/Bogota');

        Reservation::factory()->create([
            'user_id' => User::factory(),
            'service_id' => $service->id,
            'professional_id' => $service->professional_id,
            'starts_at' => $start,
            'ends_at' => $start->addHour(),
            'price_cents' => $service->price_cents,
        ]);

        $response = $this->query($service, '2026-07-07');

        $response->assertOk()->assertJsonCount(11, 'data.slots');
    }

    public function test_closed_day_returns_no_slots(): void
    {
        $service = Service::factory()->duration(60)->create();

        $response = $this->query($service, '2026-07-12'); // domingo

        $response->assertOk()->assertJsonCount(0, 'data.slots');
    }

    public function test_same_day_respects_minimum_lead_time(): void
    {
        // ahora = 2026-07-06 08:00; mismo día => primer slot válido a las 10:00.
        $service = Service::factory()->duration(60)->create();

        $response = $this->query($service, '2026-07-06'); // lunes (hoy)

        // 10:00..18:00 => 9 slots.
        $response->assertOk()->assertJsonCount(9, 'data.slots');
    }
}
