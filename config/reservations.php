<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Zona horaria de operación
    |--------------------------------------------------------------------------
    | Todas las reglas (horario, anticipación, reembolsos) se evalúan en esta
    | zona horaria, independientemente de cómo se almacenen las fechas (UTC).
    */
    'timezone' => 'America/Bogota',

    /*
    |--------------------------------------------------------------------------
    | Horario de operación
    |--------------------------------------------------------------------------
    | Días permitidos (0=domingo ... 6=sábado) y franja horaria. Toda la
    | duración de la reserva debe caer dentro de [opening, closing).
    */
    'operating_days' => [1, 2, 3, 4, 5, 6], // lunes a sábado
    'opening_hour' => 7,  // 07:00
    'closing_hour' => 19, // 19:00

    /*
    |--------------------------------------------------------------------------
    | Anticipación mínima para crear una reserva (en horas)
    |--------------------------------------------------------------------------
    */
    'minimum_lead_time_hours' => 2,

    /*
    |--------------------------------------------------------------------------
    | Límite de reservas activas (no canceladas y futuras) por usuario
    |--------------------------------------------------------------------------
    */
    'max_active_reservations' => 3,

    /*
    |--------------------------------------------------------------------------
    | Política de reembolso
    |--------------------------------------------------------------------------
    | Tramos evaluados por horas de anticipación respecto al inicio. Cada lista
    | está ordenada de mayor a menor anticipación; se aplica el primer tramo
    | cuyo umbral (min_hours_before) se cumpla. percent es el % a reembolsar.
    */
    'refunds' => [
        'standard' => [
            ['min_hours_before' => 24, 'percent' => 100],
            ['min_hours_before' => 4, 'percent' => 50],
            ['min_hours_before' => 0, 'percent' => 0],
        ],
        'premium' => [
            ['min_hours_before' => 4, 'percent' => 100],
            ['min_hours_before' => 1, 'percent' => 50],
            ['min_hours_before' => 0, 'percent' => 0],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Festivos de Colombia 2026 (hardcodeados, formato Y-m-d)
    |--------------------------------------------------------------------------
    | Fuente: calendario oficial de festivos de Colombia para 2026.
    */
    'holidays_2026' => [
        '2026-01-01', // Año Nuevo
        '2026-01-12', // Reyes Magos
        '2026-03-23', // San José
        '2026-04-02', // Jueves Santo
        '2026-04-03', // Viernes Santo
        '2026-05-01', // Día del Trabajo
        '2026-05-18', // Ascensión
        '2026-06-08', // Corpus Christi
        '2026-06-15', // Sagrado Corazón
        '2026-06-29', // San Pedro y San Pablo
        '2026-07-20', // Día de la Independencia
        '2026-08-07', // Batalla de Boyacá
        '2026-08-17', // Asunción de la Virgen
        '2026-10-12', // Día de la Raza
        '2026-11-02', // Todos los Santos
        '2026-11-16', // Independencia de Cartagena
        '2026-12-08', // Inmaculada Concepción
        '2026-12-25', // Navidad
    ],
];
