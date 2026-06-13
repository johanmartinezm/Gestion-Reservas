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
        'users' => $users,
        'services' => $services,
        'config' => config('reservations'),
    ]);
});
