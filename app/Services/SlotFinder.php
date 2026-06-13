<?php

declare(strict_types=1);

namespace App\Services;

use Carbon\CarbonImmutable;

/**
 * Calcula los horarios libres (slots) de un profesional para una fecha y una
 * duración dadas, respetando el horario de operación, festivos, la anticipación
 * mínima y las reservas ya existentes.
 */
class SlotFinder
{
    public function __construct(
        private readonly OperatingHours $operatingHours,
        private readonly AvailabilityChecker $availability,
    ) {}

    /**
     * @return list<string> Horas de inicio disponibles en formato ISO 8601.
     */
    public function freeSlots(int $professionalId, CarbonImmutable $date, int $durationMinutes): array
    {
        if ($durationMinutes <= 0) {
            return [];
        }

        $tz = config('reservations.timezone');
        $date = $date->setTimezone($tz);

        $opening = (int) config('reservations.opening_hour');
        $closing = (int) config('reservations.closing_hour');
        $leadHours = (int) config('reservations.minimum_lead_time_hours');

        $dayStart = $date->setTime($opening, 0);
        $dayEnd = $date->setTime($closing, 0);
        $earliest = CarbonImmutable::now($tz)->addHours($leadHours);

        $slots = [];

        for ($start = $dayStart; $start->addMinutes($durationMinutes)->lte($dayEnd); $start = $start->addMinutes($durationMinutes)) {
            $end = $start->addMinutes($durationMinutes);

            if ($start->lt($earliest)) {
                continue;
            }
            if (! $this->operatingHours->isOpenFor($start, $end)) {
                continue;
            }
            if ($this->availability->hasConflict($professionalId, $start, $end)) {
                continue;
            }

            $slots[] = $start->toIso8601String();
        }

        return $slots;
    }
}
