<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Reservation;
use App\Models\Service;
use App\Models\User;
use App\Services\AvailabilityChecker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AvailabilityCheckerTest extends TestCase
{
    use RefreshDatabase;

    private AvailabilityChecker $checker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->checker = new AvailabilityChecker;
    }

    private function existingReservation(Service $service, string $start, string $end): Reservation
    {
        $startsAt = Carbon::parse($start, 'America/Bogota');
        $endsAt = Carbon::parse($end, 'America/Bogota');

        return Reservation::factory()->create([
            'user_id' => User::factory(),
            'service_id' => $service->id,
            'professional_id' => $service->professional_id,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'price_cents' => $service->price_cents,
        ]);
    }

    public function test_partial_overlap_is_a_conflict(): void
    {
        $service = Service::factory()->create();
        $this->existingReservation($service, '2026-07-07 10:00', '2026-07-07 11:00');

        $conflict = $this->checker->hasConflict(
            $service->professional_id,
            Carbon::parse('2026-07-07 10:30', 'America/Bogota'),
            Carbon::parse('2026-07-07 11:30', 'America/Bogota'),
        );

        $this->assertTrue($conflict);
    }

    public function test_contiguous_reservations_do_not_conflict(): void
    {
        $service = Service::factory()->create();
        $this->existingReservation($service, '2026-07-07 10:00', '2026-07-07 11:00');

        $conflict = $this->checker->hasConflict(
            $service->professional_id,
            Carbon::parse('2026-07-07 11:00', 'America/Bogota'),
            Carbon::parse('2026-07-07 12:00', 'America/Bogota'),
        );

        $this->assertFalse($conflict);
    }

    public function test_overlap_with_a_different_professional_does_not_conflict(): void
    {
        $service = Service::factory()->create();
        $this->existingReservation($service, '2026-07-07 10:00', '2026-07-07 11:00');

        $other = Service::factory()->create();

        $conflict = $this->checker->hasConflict(
            $other->professional_id,
            Carbon::parse('2026-07-07 10:30', 'America/Bogota'),
            Carbon::parse('2026-07-07 11:30', 'America/Bogota'),
        );

        $this->assertFalse($conflict);
    }

    public function test_overlap_with_a_cancelled_reservation_does_not_conflict(): void
    {
        $service = Service::factory()->create();
        Reservation::factory()->cancelled()->create([
            'user_id' => User::factory(),
            'service_id' => $service->id,
            'professional_id' => $service->professional_id,
            'starts_at' => Carbon::parse('2026-07-07 10:00', 'America/Bogota'),
            'ends_at' => Carbon::parse('2026-07-07 11:00', 'America/Bogota'),
            'price_cents' => $service->price_cents,
        ]);

        $conflict = $this->checker->hasConflict(
            $service->professional_id,
            Carbon::parse('2026-07-07 10:30', 'America/Bogota'),
            Carbon::parse('2026-07-07 11:30', 'America/Bogota'),
        );

        $this->assertFalse($conflict);
    }
}
