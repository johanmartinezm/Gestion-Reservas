<?php

declare(strict_types=1);

namespace App\Services;

use Carbon\CarbonInterface;

/**
 * Valida que una reserva caiga dentro del horario de operación:
 * días permitidos (lun–sáb), franja horaria (07:00–19:00) y no festivos.
 * Toda la duración de la reserva debe estar dentro de la franja.
 */
class OperatingHours
{
    public function __construct(private readonly HolidayCalendar $holidays) {}

    public function isOpenFor(CarbonInterface $start, CarbonInterface $end): bool
    {
        $tz = config('reservations.timezone');
        $start = $start->copy()->setTimezone($tz);
        $end = $end->copy()->setTimezone($tz);

        $operatingDays = (array) config('reservations.operating_days');
        $opening = (int) config('reservations.opening_hour');
        $closing = (int) config('reservations.closing_hour');

        // Mismo día calendario para inicio y fin.
        if (! $start->isSameDay($end)) {
            return false;
        }

        if (! in_array($start->dayOfWeek, $operatingDays, true)) {
            return false;
        }

        if ($this->holidays->isHoliday($start)) {
            return false;
        }

        $startMinutes = $start->hour * 60 + $start->minute;
        $endMinutes = $end->hour * 60 + $end->minute;
        // Fin a medianoche (00:00) cuenta como fin del día.
        if ($endMinutes === 0 && $end->gt($start)) {
            $endMinutes = 24 * 60;
        }

        return $startMinutes >= $opening * 60 && $endMinutes <= $closing * 60;
    }
}
