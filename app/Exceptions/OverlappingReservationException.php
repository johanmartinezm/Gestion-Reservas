<?php

declare(strict_types=1);

namespace App\Exceptions;

class OverlappingReservationException extends DomainException
{
    public function __construct(string $message = 'El profesional ya tiene una reserva que se solapa con ese horario.')
    {
        parent::__construct($message);
    }

    public function errorCode(): string
    {
        return 'overlapping_reservation';
    }
}
