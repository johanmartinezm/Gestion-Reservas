<?php

use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $endpoints = [
        ['method' => 'POST', 'path' => '/api/reservations', 'desc' => 'Crear una reserva'],
        ['method' => 'POST', 'path' => '/api/reservations/{id}/cancel', 'desc' => 'Cancelar y calcular reembolso'],
        ['method' => 'GET', 'path' => '/api/reservations/{id}', 'desc' => 'Ver una reserva'],
        ['method' => 'GET', 'path' => '/api/users/{id}/reservations?from=&to=', 'desc' => 'Listar reservas de un usuario por rango'],
        ['method' => 'GET', 'path' => '/api/professionals/{id}/availability?date=&service_id=', 'desc' => 'Horarios libres de un profesional'],
    ];

    $api = url('/api');

    $examples = [
        [
            'method' => 'POST',
            'path' => '/api/reservations',
            'title' => 'Crear una reserva',
            'request' => "curl -X POST {$api}/reservations \\\n  -H \"Content-Type: application/json\" \\\n  -d '{\n    \"user_id\": 2,\n    \"service_id\": 1,\n    \"starts_at\": \"2026-06-16 10:00\"\n  }'",
            'response' => "// 201 Created\n{\n  \"data\": {\n    \"id\": 4,\n    \"user_id\": 2,\n    \"service_id\": 1,\n    \"professional_id\": 1,\n    \"starts_at\": \"2026-06-16T10:00:00-05:00\",\n    \"ends_at\": \"2026-06-16T10:30:00-05:00\",\n    \"status\": \"active\",\n    \"price_cents\": 3000000,\n    \"refund_cents\": null\n  }\n}",
        ],
        [
            'method' => 'POST',
            'path' => '/api/reservations/{id}/cancel',
            'title' => 'Cancelar y calcular reembolso',
            'request' => "curl -X POST {$api}/reservations/1/cancel",
            'response' => "// 200 OK\n{\n  \"data\": {\n    \"id\": 1,\n    \"status\": \"cancelled\",\n    \"price_cents\": 3000000,\n    \"refund_cents\": 3000000,\n    \"cancelled_at\": \"2026-06-13T15:00:00-05:00\"\n  }\n}",
        ],
        [
            'method' => 'GET',
            'path' => '/api/reservations/{id}',
            'title' => 'Ver una reserva',
            'request' => "curl {$api}/reservations/1",
            'response' => "// 200 OK\n{\n  \"data\": {\n    \"id\": 1,\n    \"user_id\": 1,\n    \"service_id\": 1,\n    \"status\": \"active\",\n    \"starts_at\": \"2026-07-10T09:00:00-05:00\",\n    \"ends_at\": \"2026-07-10T09:30:00-05:00\",\n    \"price_cents\": 3000000,\n    \"refund_cents\": null\n  }\n}",
        ],
        [
            'method' => 'GET',
            'path' => '/api/users/{id}/reservations',
            'title' => 'Listar reservas por rango',
            'request' => "curl \"{$api}/users/1/reservations\\\n  ?from=2026-01-01&to=2026-12-31\"",
            'response' => "// 200 OK\n{\n  \"data\": [\n    {\n      \"id\": 1,\n      \"status\": \"active\",\n      \"starts_at\": \"2026-07-10T09:00:00-05:00\",\n      \"price_cents\": 3000000\n    }\n  ]\n}",
        ],
        [
            'method' => 'GET',
            'path' => '/api/professionals/{id}/availability',
            'title' => 'Horarios libres de un profesional',
            'request' => "curl \"{$api}/professionals/1/availability\\\n  ?date=2026-06-16&service_id=1\"",
            'response' => "// 200 OK\n{\n  \"data\": {\n    \"professional_id\": 1,\n    \"service_id\": 1,\n    \"date\": \"2026-06-16\",\n    \"duration_minutes\": 30,\n    \"slots\": [\n      \"2026-06-16T07:00:00-05:00\",\n      \"2026-06-16T07:30:00-05:00\"\n    ]\n  }\n}",
        ],
    ];

    $errorCodes = [
        ['code' => 'insufficient_lead_time', 'http' => 422, 'desc' => 'Menos de 2 h de anticipación.'],
        ['code' => 'outside_operating_hours', 'http' => 422, 'desc' => 'Domingo, festivo o fuera de 7:00–19:00.'],
        ['code' => 'overlapping_reservation', 'http' => 422, 'desc' => 'El profesional ya está ocupado en ese rango.'],
        ['code' => 'active_reservation_limit', 'http' => 422, 'desc' => 'El usuario ya tiene 3 reservas activas.'],
        ['code' => 'reservation_not_cancellable', 'http' => 409, 'desc' => 'La reserva ya está cancelada.'],
    ];

    // Datos sembrados (si la BD está migrada); si no, se omiten sin romper la vista.
    try {
        $users = User::query()->get(['id', 'name', 'plan']);
        $services = Service::query()->get(['id', 'name', 'duration_minutes', 'price_cents', 'non_refundable', 'professional_id']);
    } catch (Throwable) {
        $users = collect();
        $services = collect();
    }

    return view('api-index', [
        'endpoints' => $endpoints,
        'examples' => $examples,
        'errorCodes' => $errorCodes,
        'users' => $users,
        'services' => $services,
        'config' => config('reservations'),
    ]);
});
