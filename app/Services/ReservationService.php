<?php

declare(strict_types=1);

namespace App\Services;

use App\DataTransferObjects\CreateReservationData;
use App\Enums\ReservationStatus;
use App\Exceptions\ActiveReservationLimitException;
use App\Exceptions\InsufficientLeadTimeException;
use App\Exceptions\OutsideOperatingHoursException;
use App\Exceptions\OverlappingReservationException;
use App\Exceptions\ReservationNotCancellableException;
use App\Models\Reservation;
use App\Models\Service;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Orquesta la creación, cancelación y listado de reservas aplicando las reglas
 * de negocio. La creación y cancelación corren en transacción con bloqueo
 * pesimista para evitar condiciones de carrera (solapamiento y límite).
 */
class ReservationService
{
    public function __construct(
        private readonly OperatingHours $operatingHours,
        private readonly AvailabilityChecker $availability,
        private readonly RefundCalculator $refunds,
    ) {}

    public function create(CreateReservationData $data): Reservation
    {
        $user = User::query()->findOrFail($data->userId);
        $service = Service::query()->findOrFail($data->serviceId);

        $startsAt = $data->startsAt;
        $endsAt = $startsAt->addMinutes($service->duration_minutes);

        $this->assertSufficientLeadTime($startsAt);
        $this->assertWithinOperatingHours($startsAt, $endsAt);

        // Lock por profesional: garantiza exclusión mutua real incluso cuando el
        // motor no soporta SELECT ... FOR UPDATE (p. ej. SQLite). Dos peticiones
        // para el mismo profesional se serializan; la transacción asegura atomicidad.
        $lock = Cache::lock("reservations:professional:{$service->professional_id}", 10);

        return $lock->block(5, fn () => DB::transaction(function () use ($user, $service, $startsAt, $endsAt) {
            $this->assertNoOverlap($service->professional_id, $startsAt, $endsAt);
            $this->assertUnderActiveLimit($user);

            return Reservation::query()->create([
                'user_id' => $user->id,
                'service_id' => $service->id,
                'professional_id' => $service->professional_id,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'status' => ReservationStatus::Active,
                'price_cents' => $service->price_cents,
            ]);
        }));
    }

    public function cancel(Reservation $reservation): Reservation
    {
        return DB::transaction(function () use ($reservation) {
            $fresh = Reservation::query()->lockForUpdate()->findOrFail($reservation->id);

            if (! $fresh->isActive()) {
                throw new ReservationNotCancellableException;
            }

            $hoursBefore = $this->hoursUntil($fresh->starts_at);
            $service = $fresh->service;

            $refundCents = $this->refunds->refundCents(
                priceCents: $fresh->price_cents,
                plan: $fresh->user->plan,
                hoursBefore: $hoursBefore,
                nonRefundable: (bool) $service->non_refundable,
            );

            $fresh->update([
                'status' => ReservationStatus::Cancelled,
                'cancelled_at' => CarbonImmutable::now(),
                'refund_cents' => $refundCents,
            ]);

            return $fresh->refresh();
        });
    }

    /**
     * @return Collection<int, Reservation>
     */
    public function listForUser(User $user, CarbonInterface $from, CarbonInterface $to): Collection
    {
        return $user->reservations()
            ->whereBetween('starts_at', [$from, $to])
            ->orderBy('starts_at')
            ->get();
    }

    private function assertSufficientLeadTime(CarbonImmutable $startsAt): void
    {
        $minHours = (int) config('reservations.minimum_lead_time_hours');

        if ($startsAt->lt(CarbonImmutable::now()->addHours($minHours))) {
            throw new InsufficientLeadTimeException;
        }
    }

    private function assertWithinOperatingHours(CarbonImmutable $startsAt, CarbonImmutable $endsAt): void
    {
        if (! $this->operatingHours->isOpenFor($startsAt, $endsAt)) {
            throw new OutsideOperatingHoursException;
        }
    }

    private function assertNoOverlap(int $professionalId, CarbonImmutable $startsAt, CarbonImmutable $endsAt): void
    {
        if ($this->availability->hasConflict($professionalId, $startsAt, $endsAt, lockForUpdate: true)) {
            throw new OverlappingReservationException;
        }
    }

    private function assertUnderActiveLimit(User $user): void
    {
        $max = (int) config('reservations.max_active_reservations');

        $activeCount = $user->reservations()
            ->where('status', ReservationStatus::Active->value)
            ->where('starts_at', '>', CarbonImmutable::now())
            ->lockForUpdate()
            ->count();

        if ($activeCount >= $max) {
            throw new ActiveReservationLimitException;
        }
    }

    private function hoursUntil(CarbonInterface $date): float
    {
        return ($date->getTimestamp() - CarbonImmutable::now()->getTimestamp()) / 3600;
    }
}
