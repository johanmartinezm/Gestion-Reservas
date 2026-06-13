<?php

declare(strict_types=1);

namespace App\Exceptions;

class OutsideOperatingHoursException extends DomainException
{
    public function __construct(string $message = 'La reserva está fuera del horario de operación (lunes a sábado, 7:00–19:00, sin festivos).')
    {
        parent::__construct($message);
    }

    public function errorCode(): string
    {
        return 'outside_operating_hours';
    }
}
