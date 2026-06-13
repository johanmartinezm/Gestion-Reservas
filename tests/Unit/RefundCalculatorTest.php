<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\UserPlan;
use App\Services\RefundCalculator;
use Tests\TestCase;

class RefundCalculatorTest extends TestCase
{
    private RefundCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new RefundCalculator;
    }

    public function test_standard_more_than_24h_refunds_100_percent(): void
    {
        $this->assertSame(100, $this->calculator->percentFor(UserPlan::Standard, 30, false));
    }

    public function test_standard_between_24h_and_4h_refunds_50_percent(): void
    {
        $this->assertSame(50, $this->calculator->percentFor(UserPlan::Standard, 5, false));
    }

    public function test_standard_less_than_4h_refunds_nothing(): void
    {
        $this->assertSame(0, $this->calculator->percentFor(UserPlan::Standard, 2, false));
    }

    public function test_premium_between_4h_and_1h_refunds_50_percent(): void
    {
        $this->assertSame(50, $this->calculator->percentFor(UserPlan::Premium, 2, false));
    }

    public function test_premium_more_than_4h_refunds_100_percent(): void
    {
        $this->assertSame(100, $this->calculator->percentFor(UserPlan::Premium, 5, false));
    }

    public function test_premium_less_than_1h_refunds_nothing(): void
    {
        $this->assertSame(0, $this->calculator->percentFor(UserPlan::Premium, 0.5, false));
    }

    public function test_non_refundable_never_refunds(): void
    {
        $this->assertSame(0, $this->calculator->percentFor(UserPlan::Premium, 100, true));
        $this->assertSame(0, $this->calculator->percentFor(UserPlan::Standard, 100, true));
    }

    public function test_past_start_time_refunds_nothing(): void
    {
        $this->assertSame(0, $this->calculator->percentFor(UserPlan::Standard, -1, false));
    }

    public function test_refund_cents_rounds_to_nearest_integer(): void
    {
        // 50% de 999 = 499.5 -> 500
        $this->assertSame(500, $this->calculator->refundCents(999, UserPlan::Standard, 5, false));
    }
}
