<!DOCTYPE html>
<html lang="es" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>API de Reservas — Gestión de Reservas</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-950 text-slate-200 antialiased">
<div class="mx-auto max-w-4xl px-6 py-12 md:py-16">

    {{-- Header --}}
    <header class="border-b border-slate-800 pb-6">
        <h1 class="text-3xl font-bold tracking-tight text-white">API de Reservas</h1>
        <p class="mt-1 text-slate-400">
            Servicio de gestión de creación y cancelación de reservas — Laravel {{ app()->version() }} · PHP {{ PHP_VERSION }}
        </p>
        <div class="mt-4 flex flex-wrap gap-2">
            @foreach (['REST / JSON', 'SQLite', 'Zona horaria: '.$config['timezone'], 'Sin autenticación'] as $tag)
                <span class="rounded-full border border-slate-700 px-3 py-1 text-xs text-slate-400">{{ $tag }}</span>
            @endforeach
        </div>
        <p class="mt-4 font-mono text-sm text-sky-400">Base URL: {{ url('/api') }}</p>
    </header>

    {{-- Endpoints --}}
    <h2 class="mb-4 mt-10 text-lg font-semibold text-white">Endpoints</h2>
    <div class="overflow-hidden rounded-xl border border-slate-800 bg-slate-900">
        @foreach ($endpoints as $ep)
            <div class="flex flex-wrap items-center gap-3 border-t border-slate-800 px-4 py-3.5 first:border-t-0 hover:bg-slate-800/40">
                <span @class([
                    'min-w-[3.25rem] rounded-md px-2.5 py-1 text-center font-mono text-xs font-bold',
                    'bg-green-500 text-slate-950' => $ep['method'] === 'GET',
                    'bg-blue-500 text-white' => $ep['method'] === 'POST',
                ])>{{ $ep['method'] }}</span>
                <span class="font-mono text-sm text-slate-200">{{ $ep['path'] }}</span>
                <span class="ml-auto text-right text-sm text-slate-400">{{ $ep['desc'] }}</span>
            </div>
        @endforeach
    </div>

    {{-- Ejemplo --}}
    <h2 class="mb-4 mt-10 text-lg font-semibold text-white">Ejemplo</h2>
    <div class="grid gap-4 md:grid-cols-2">
        <pre class="overflow-x-auto rounded-xl border border-slate-800 bg-slate-900 p-4 font-mono text-[13px] leading-relaxed text-slate-300"># Crear una reserva
curl -X POST {{ url('/api/reservations') }} \
  -H "Content-Type: application/json" \
  -d '{
    "user_id": 2,
    "service_id": 1,
    "starts_at": "2026-06-16 10:00"
  }'</pre>
        <pre class="overflow-x-auto rounded-xl border border-slate-800 bg-slate-900 p-4 font-mono text-[13px] leading-relaxed text-slate-300">// 201 Created
{
  "data": {
    "id": 4,
    "user_id": 2,
    "service_id": 1,
    "professional_id": 1,
    "starts_at": "2026-06-16T10:00:00-05:00",
    "ends_at": "2026-06-16T10:30:00-05:00",
    "status": "active",
    "price_cents": 3000000,
    "refund_cents": null
  }
}</pre>
    </div>

    {{-- Datos de ejemplo --}}
    @if ($users->isNotEmpty() || $services->isNotEmpty())
        <h2 class="mb-4 mt-10 text-lg font-semibold text-white">Datos de ejemplo (seed)</h2>
        <div class="grid gap-4 md:grid-cols-2">
            <div class="overflow-hidden rounded-xl border border-slate-800 bg-slate-900">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-xs uppercase tracking-wide text-slate-500">
                            <th class="px-4 py-2.5 text-left font-semibold">Usuario</th>
                            <th class="px-4 py-2.5 text-left font-semibold">Nombre</th>
                            <th class="px-4 py-2.5 text-left font-semibold">Plan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($users as $u)
                            <tr class="border-t border-slate-800 hover:bg-slate-800/40">
                                <td class="px-4 py-2.5 text-slate-400">#{{ $u->id }}</td>
                                <td class="px-4 py-2.5">{{ $u->name }}</td>
                                <td class="px-4 py-2.5">
                                    <span @class([
                                        'rounded-full px-2 py-0.5 text-xs font-semibold',
                                        'bg-amber-500/15 text-amber-400' => $u->plan->value === 'premium',
                                        'bg-slate-500/15 text-slate-400' => $u->plan->value === 'standard',
                                    ])>{{ $u->plan->value }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="overflow-hidden rounded-xl border border-slate-800 bg-slate-900">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-xs uppercase tracking-wide text-slate-500">
                            <th class="px-4 py-2.5 text-left font-semibold">Servicio</th>
                            <th class="px-4 py-2.5 text-left font-semibold">Nombre</th>
                            <th class="px-4 py-2.5 text-left font-semibold">Dur.</th>
                            <th class="px-4 py-2.5 text-left font-semibold">Precio</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($services as $s)
                            <tr class="border-t border-slate-800 hover:bg-slate-800/40">
                                <td class="px-4 py-2.5 text-slate-400">#{{ $s->id }}</td>
                                <td class="px-4 py-2.5">
                                    {{ $s->name }}
                                    @if ($s->non_refundable)
                                        <span class="ml-1 rounded-full bg-red-500/15 px-2 py-0.5 text-xs font-semibold text-red-400">no reemb.</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2.5 text-slate-400">{{ $s->duration_minutes }}m</td>
                                <td class="px-4 py-2.5">${{ number_format($s->price_cents / 100, 0) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Reglas --}}
    <h2 class="mb-4 mt-10 text-lg font-semibold text-white">Reglas de negocio</h2>
    <ul class="space-y-2 text-sm text-slate-400">
        <li><span class="text-slate-200">Horario:</span> lunes a sábado, {{ sprintf('%02d:00', $config['opening_hour']) }}–{{ sprintf('%02d:00', $config['closing_hour']) }} (hora de Bogotá). Sin domingos ni festivos de Colombia 2026.</li>
        <li><span class="text-slate-200">Anticipación mínima:</span> {{ $config['minimum_lead_time_hours'] }} horas antes del inicio.</li>
        <li><span class="text-slate-200">Sin solapamiento</span> entre reservas del mismo profesional.</li>
        <li><span class="text-slate-200">Reembolsos</span> — Estándar: 100% (&gt;24h), 50% (24–4h), 0% (&lt;4h). Premium: 100% (&gt;4h), 50% (4–1h), 0% (&lt;1h).</li>
        <li><span class="text-slate-200">Servicios no reembolsables:</span> nunca reembolsan, pero sí se pueden cancelar.</li>
        <li><span class="text-slate-200">Límite:</span> máximo {{ $config['max_active_reservations'] }} reservas activas por usuario.</li>
    </ul>

    {{-- Footer --}}
    <footer class="mt-12 border-t border-slate-800 pt-5 text-sm text-slate-500">
        Estado del servicio: <a class="text-sky-400 hover:underline" href="{{ url('/up') }}">/up</a>
        · Documentación: <code class="rounded bg-slate-800 px-1.5 py-0.5 font-mono text-xs">docs/api.md</code>
        · Pruebas: <code class="rounded bg-slate-800 px-1.5 py-0.5 font-mono text-xs">php artisan test</code>
    </footer>
</div>
</body>
</html>
