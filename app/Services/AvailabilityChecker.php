<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use Carbon\CarbonInterface;

/**
 * Verifica que un profesional no tenga reservas activas que se solapen con un
 * nuevo rango horario. Los rangos son semiabiertos [start, end): dos reservas
 * contiguas (una termina cuando la otra empieza) no se consideran solapadas.
 */
class AvailabilityChecker
{
    public function hasConflict(
        int $professionalId,
        CarbonInterface $start,
        CarbonInterface $end,
        ?int $ignoreReservationId = null,
        bool $lockForUpdate = false,
    ): bool {
        $query = Reservation::query()
            ->where('professional_id', $professionalId)
            ->where('status', ReservationStatus::Active->value)
            ->where('starts_at', '<', $end)
            ->where('ends_at', '>', $start);

        if ($ignoreReservationId !== null) {
            $query->whereKeyNot($ignoreReservationId);
        }

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        return $query->exists();
    }
}
