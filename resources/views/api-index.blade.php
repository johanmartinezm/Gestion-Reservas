<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>API de Reservas — Gestión de Reservas</title>
    <style>
        :root {
            --bg: #0f172a; --card: #1e293b; --card2: #172033; --line: #334155;
            --text: #e2e8f0; --muted: #94a3b8; --accent: #38bdf8;
            --get: #22c55e; --post: #3b82f6; --warn: #f59e0b;
            --mono: ui-monospace, SFMono-Regular, "SF Mono", Menlo, Consolas, monospace;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0; background: var(--bg); color: var(--text);
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            line-height: 1.55;
        }
        .wrap { max-width: 960px; margin: 0 auto; padding: 48px 24px 80px; }
        header { border-bottom: 1px solid var(--line); padding-bottom: 24px; margin-bottom: 32px; }
        h1 { margin: 0 0 6px; font-size: 28px; letter-spacing: -.02em; }
        h2 { font-size: 18px; margin: 40px 0 16px; color: var(--text); }
        .sub { color: var(--muted); margin: 0; }
        .tags { margin-top: 16px; display: flex; gap: 8px; flex-wrap: wrap; }
        .tag { font-size: 12px; padding: 4px 10px; border: 1px solid var(--line); border-radius: 999px; color: var(--muted); }
        .baseurl { margin-top: 16px; font-family: var(--mono); font-size: 13px; color: var(--accent); }
        .card { background: var(--card); border: 1px solid var(--line); border-radius: 12px; overflow: hidden; }
        .ep { display: flex; align-items: center; gap: 14px; padding: 14px 18px; border-top: 1px solid var(--line); }
        .ep:first-child { border-top: 0; }
        .method { font-family: var(--mono); font-size: 12px; font-weight: 700; padding: 4px 9px; border-radius: 6px; min-width: 52px; text-align: center; color: #04121f; }
        .method.GET { background: var(--get); }
        .method.POST { background: var(--post); color: #fff; }
        .path { font-family: var(--mono); font-size: 14px; color: var(--text); }
        .ep .desc { color: var(--muted); margin-left: auto; font-size: 13px; text-align: right; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th, td { text-align: left; padding: 10px 14px; border-top: 1px solid var(--line); }
        th { color: var(--muted); font-weight: 600; font-size: 12px; text-transform: uppercase; letter-spacing: .04em; }
        tbody tr:hover { background: var(--card2); }
        .pill { font-size: 11px; padding: 2px 8px; border-radius: 999px; font-weight: 600; }
        .pill.premium { background: rgba(245,158,11,.15); color: var(--warn); }
        .pill.standard { background: rgba(148,163,184,.15); color: var(--muted); }
        .pill.nr { background: rgba(239,68,68,.15); color: #f87171; }
        pre { background: var(--card2); border: 1px solid var(--line); border-radius: 10px; padding: 16px; overflow-x: auto; font-family: var(--mono); font-size: 13px; color: #cbd5e1; margin: 0; }
        .grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        @media (max-width: 720px) { .grid2 { grid-template-columns: 1fr; } .ep { flex-wrap: wrap; } .ep .desc { margin-left: 0; text-align: left; width: 100%; } }
        .rules li { color: var(--muted); margin-bottom: 6px; }
        .rules strong { color: var(--text); }
        code { font-family: var(--mono); background: var(--card2); padding: 1px 6px; border-radius: 5px; font-size: 13px; }
        footer { margin-top: 48px; padding-top: 20px; border-top: 1px solid var(--line); color: var(--muted); font-size: 13px; }
        a { color: var(--accent); }
    </style>
</head>
<body>
<div class="wrap">
    <header>
        <h1>API de Reservas</h1>
        <p class="sub">Servicio de gestión de creación y cancelación de reservas — Laravel {{ app()->version() }} · PHP {{ PHP_VERSION }}</p>
        <div class="tags">
            <span class="tag">REST / JSON</span>
            <span class="tag">SQLite</span>
            <span class="tag">Zona horaria: {{ $config['timezone'] }}</span>
            <span class="tag">Sin autenticación</span>
        </div>
        <div class="baseurl">Base URL: {{ url('/api') }}</div>
    </header>

    <h2>Endpoints</h2>
    <div class="card">
        @foreach ($endpoints as $ep)
            <div class="ep">
                <span class="method {{ $ep['method'] }}">{{ $ep['method'] }}</span>
                <span class="path">{{ $ep['path'] }}</span>
                <span class="desc">{{ $ep['desc'] }}</span>
            </div>
        @endforeach
    </div>

    <h2>Ejemplo</h2>
    <div class="grid2">
        <div>
            <pre># Crear una reserva
curl -X POST {{ url('/api/reservations') }} \
  -H "Content-Type: application/json" \
  -d '{
    "user_id": 2,
    "service_id": 1,
    "starts_at": "2026-06-16 10:00"
  }'</pre>
        </div>
        <div>
            <pre>// 201 Created
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
    </div>

    @if ($users->isNotEmpty() || $services->isNotEmpty())
        <h2>Datos de ejemplo (seed)</h2>
        <div class="grid2">
            <div class="card">
                <table>
                    <thead><tr><th>Usuario</th><th>Nombre</th><th>Plan</th></tr></thead>
                    <tbody>
                    @foreach ($users as $u)
                        <tr>
                            <td>#{{ $u->id }}</td>
                            <td>{{ $u->name }}</td>
                            <td><span class="pill {{ $u->plan->value }}">{{ $u->plan->value }}</span></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card">
                <table>
                    <thead><tr><th>Servicio</th><th>Nombre</th><th>Dur.</th><th>Precio</th></tr></thead>
                    <tbody>
                    @foreach ($services as $s)
                        <tr>
                            <td>#{{ $s->id }}</td>
                            <td>{{ $s->name }} @if($s->non_refundable)<span class="pill nr">no reemb.</span>@endif</td>
                            <td>{{ $s->duration_minutes }}m</td>
                            <td>${{ number_format($s->price_cents / 100, 0) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <h2>Reglas de negocio</h2>
    <ul class="rules">
        <li><strong>Horario:</strong> lunes a sábado, {{ sprintf('%02d:00', $config['opening_hour']) }}–{{ sprintf('%02d:00', $config['closing_hour']) }} (hora de Bogotá). Sin domingos ni festivos de Colombia 2026.</li>
        <li><strong>Anticipación mínima:</strong> {{ $config['minimum_lead_time_hours'] }} horas antes del inicio.</li>
        <li><strong>Sin solapamiento</strong> entre reservas del mismo profesional.</li>
        <li><strong>Reembolsos</strong> — Estándar: 100% (&gt;24h), 50% (24–4h), 0% (&lt;4h). Premium: 100% (&gt;4h), 50% (4–1h), 0% (&lt;1h).</li>
        <li><strong>Servicios no reembolsables:</strong> nunca reembolsan, pero sí se pueden cancelar.</li>
        <li><strong>Límite:</strong> máximo {{ $config['max_active_reservations'] }} reservas activas por usuario.</li>
    </ul>

    <footer>
        Estado del servicio: <a href="{{ url('/up') }}">/up</a> ·
        Documentación: <code>docs/api.md</code> ·
        Pruebas: <code>php artisan test</code>
    </footer>
</div>
</body>
</html>
