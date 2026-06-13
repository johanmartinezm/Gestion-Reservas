<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\HolidayCalendar;
use App\Services\OperatingHours;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class OperatingHoursTest extends TestCase
{
    private OperatingHours $hours;

    protected function setUp(): void
    {
        parent::setUp();
        $this->hours = new OperatingHours(new HolidayCalendar);
    }

    private function bogota(string $datetime): Carbon
    {
        return Carbon::parse($datetime, 'America/Bogota');
    }

    public function test_tuesday_mid_morning_is_open(): void
    {
        $start = $this->bogota('2026-07-07 10:00');
        $this->assertTrue($this->hours->isOpenFor($start, $start->copy()->addMinutes(30)));
    }

    public function test_sunday_is_closed(): void
    {
        $start = $this->bogota('2026-07-12 10:00');
        $this->assertFalse($this->hours->isOpenFor($start, $start->copy()->addMinutes(30)));
    }

    public function test_holiday_is_closed(): void
    {
        $start = $this->bogota('2026-01-01 10:00');
        $this->assertFalse($this->hours->isOpenFor($start, $start->copy()->addMinutes(30)));
    }

    public function test_before_opening_is_closed(): void
    {
        $start = $this->bogota('2026-07-06 06:30');
        $this->assertFalse($this->hours->isOpenFor($start, $start->copy()->addMinutes(30)));
    }

    public function test_service_running_past_closing_is_rejected(): void
    {
        $start = $this->bogota('2026-07-07 18:45');
        $this->assertFalse($this->hours->isOpenFor($start, $start->copy()->addMinutes(45)));
    }

    public function test_service_ending_exactly_at_closing_is_allowed(): void
    {
        $start = $this->bogota('2026-07-07 18:00');
        $this->assertTrue($this->hours->isOpenFor($start, $start->copy()->addMinutes(60)));
    }
}
