<?php

declare(strict_types=1);

namespace App\Exceptions;

class ActiveReservationLimitException extends DomainException
{
    public function __construct(string $message = 'El usuario alcanzó el límite de reservas activas (máximo 3).')
    {
        parent::__construct($message);
    }

    public function errorCode(): string
    {
        return 'active_reservation_limit';
    }
}
