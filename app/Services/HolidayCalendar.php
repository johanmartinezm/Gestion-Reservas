<?php

declare(strict_types=1);

namespace App\Services;

use DateTimeInterface;

/**
 * Determina si una fecha es festivo en Colombia (lista hardcodeada en config).
 */
class HolidayCalendar
{
    /** @var list<string> */
    private array $holidays;

    public function __construct(?array $holidays = null)
    {
        $this->holidays = $holidays ?? (array) config('reservations.holidays_2026', []);
    }

    public function isHoliday(DateTimeInterface $date): bool
    {
        return in_array($date->format('Y-m-d'), $this->holidays, true);
    }
}
