<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Reservation
 */
class ReservationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $tz = config('reservations.timezone');

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'service_id' => $this->service_id,
            'professional_id' => $this->professional_id,
            'starts_at' => $this->starts_at->setTimezone($tz)->toIso8601String(),
            'ends_at' => $this->ends_at->setTimezone($tz)->toIso8601String(),
            'status' => $this->status->value,
            'price_cents' => $this->price_cents,
            'refund_cents' => $this->refund_cents,
            'cancelled_at' => $this->cancelled_at?->setTimezone($tz)->toIso8601String(),
        ];
    }
}
