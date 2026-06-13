<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\HolidayCalendar;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class HolidayCalendarTest extends TestCase
{
    public function test_recognizes_a_colombian_holiday(): void
    {
        $calendar = new HolidayCalendar;

        $this->assertTrue($calendar->isHoliday(Carbon::parse('2026-01-01')));
        $this->assertTrue($calendar->isHoliday(Carbon::parse('2026-07-20')));
    }

    public function test_regular_weekday_is_not_a_holiday(): void
    {
        $calendar = new HolidayCalendar;

        $this->assertFalse($calendar->isHoliday(Carbon::parse('2026-07-07')));
    }
}
