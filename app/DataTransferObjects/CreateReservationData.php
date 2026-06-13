<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

use Carbon\CarbonImmutable;

/**
 * Datos de entrada para crear una reserva, ya validados y normalizados.
 */
final class CreateReservationData
{
    public function __construct(
        public readonly int $userId,
        public readonly int $serviceId,
        public readonly CarbonImmutable $startsAt,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            userId: (int) $data['user_id'],
            serviceId: (int) $data['service_id'],
            startsAt: CarbonImmutable::parse(
                $data['starts_at'],
                config('reservations.timezone'),
            ),
        );
    }
}
