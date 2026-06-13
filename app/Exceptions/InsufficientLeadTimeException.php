<?php

declare(strict_types=1);

namespace App\Exceptions;

class InsufficientLeadTimeException extends DomainException
{
    public function __construct(string $message = 'La reserva debe crearse con al menos 2 horas de anticipación.')
    {
        parent::__construct($message);
    }

    public function errorCode(): string
    {
        return 'insufficient_lead_time';
    }
}
