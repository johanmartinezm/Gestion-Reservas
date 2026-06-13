<?php

declare(strict_types=1);

namespace App\Exceptions;

class ReservationNotCancellableException extends DomainException
{
    public function __construct(string $message = 'La reserva no se puede cancelar (ya está cancelada).')
    {
        parent::__construct($message);
    }

    public function errorCode(): string
    {
        return 'reservation_not_cancellable';
    }

    public function statusCode(): int
    {
        return 409;
    }
}
