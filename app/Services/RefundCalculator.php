<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\UserPlan;

/**
 * Calcula el porcentaje y el monto a reembolsar al cancelar una reserva, según
 * la anticipación, el plan del usuario y si el servicio es no reembolsable.
 * Los tramos se definen en config/reservations.php.
 */
class RefundCalculator
{
    /**
     * Porcentaje (0–100) a reembolsar.
     */
    public function percentFor(UserPlan $plan, float $hoursBefore, bool $nonRefundable): int
    {
        if ($nonRefundable || $hoursBefore < 0) {
            return 0;
        }

        $tiers = config("reservations.refunds.{$plan->value}", []);

        foreach ($tiers as $tier) {
            if ($hoursBefore >= $tier['min_hours_before']) {
                return (int) $tier['percent'];
            }
        }

        return 0;
    }

    /**
     * Monto a reembolsar en centavos, redondeado al entero más cercano.
     */
    public function refundCents(int $priceCents, UserPlan $plan, float $hoursBefore, bool $nonRefundable): int
    {
        $percent = $this->percentFor($plan, $hoursBefore, $nonRefundable);

        return (int) round($priceCents * $percent / 100);
    }
}
