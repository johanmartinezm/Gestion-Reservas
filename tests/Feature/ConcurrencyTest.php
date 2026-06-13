<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\DataTransferObjects\CreateReservationData;
use App\Enums\ReservationStatus;
use App\Exceptions\ActiveReservationLimitException;
use App\Exceptions\OverlappingReservationException;
use App\Models\Reservation;
use App\Models\Service;
use App\Models\User;
use App\Services\ReservationService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cubre el criterio de "concurrencia básica" del enunciado.
 *
 * La exclusión mutua se garantiza con un `Cache::lock` por profesional (que sí
 * funciona aunque el motor no soporte `SELECT ... FOR UPDATE`, como SQLite) más
 * la atomicidad de la transacción. SQLite en memoria con una sola conexión no
 * permite ejecutar hilos realmente en paralelo, así que la prueba ejecuta los
 * intentos en secuencia inmediata y verifica la INVARIANTE: sobre el mismo
 * recurso, solo uno puede prosperar.
 */
class ConcurrencyTest extends TestCase
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

    public function test_two_overlapping_reservations_for_same_professional_only_one_succeeds(): void
    {
        $service = Service::factory()->duration(60)->create();
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $reservations = app(ReservationService::class);

        $attempt = fn (User $user) => $reservations->create(new CreateReservationData(
            userId: $user->id,
            serviceId: $service->id,
            startsAt: CarbonImmutable::parse('2026-07-07 11:00', 'America/Bogota'),
        ));

        $succeeded = 0;
        $rejected = 0;

        foreach ([$userA, $userB] as $user) {
            try {
                $attempt($user);
                $succeeded++;
            } catch (OverlappingReservationException) {
                $rejected++;
            }
        }

        $this->assertSame(1, $succeeded, 'Solo una reserva debe persistir.');
        $this->assertSame(1, $rejected, 'La segunda debe ser rechazada por solapamiento.');
        $this->assertSame(1, Reservation::where('status', ReservationStatus::Active->value)->count());
    }

    public function test_active_limit_is_not_exceeded_under_repeated_attempts(): void
    {
        $user = User::factory()->create();
        $reservations = app(ReservationService::class);

        // Cuatro intentos en distintos servicios/horarios; el límite es 3.
        $succeeded = 0;
        $rejected = 0;

        for ($i = 0; $i < 4; $i++) {
            $service = Service::factory()->duration(30)->create();
            $start = CarbonImmutable::parse('2026-07-08 09:00', 'America/Bogota')->addHours($i * 2);

            try {
                $reservations->create(new CreateReservationData(
                    userId: $user->id,
                    serviceId: $service->id,
                    startsAt: $start,
                ));
                $succeeded++;
            } catch (ActiveReservationLimitException) {
                $rejected++;
            }
        }

        $this->assertSame(3, $succeeded, 'Como máximo 3 reservas activas.');
        $this->assertSame(1, $rejected, 'El cuarto intento debe rechazarse.');
        $this->assertSame(3, $user->reservations()->where('status', ReservationStatus::Active->value)->count());
    }
}
