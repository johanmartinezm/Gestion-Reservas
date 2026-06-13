<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\ReservationStatus;
use App\Enums\UserPlan;
use App\Models\Professional;
use App\Models\Reservation;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Throwable;

/**
 * Importa y normaliza los datos de data/seed.json hacia la base de datos.
 *
 * Los datos de origen contienen inconsistencias intencionales (fechas en
 * distintos formatos, campos faltantes). Esta clase define la política para
 * manejarlas: normalizar cuando es posible y descartar (reportando) cuando una
 * fila es irrecuperable, sin abortar la importación completa.
 */
class SeedImporter
{
    /** @var list<string> Formatos de fecha aceptados, en orden de preferencia. */
    private const DATE_FORMATS = [
        'Y-m-d H:i:s',
        'Y-m-d H:i',
        'Y-m-d\TH:i:sP',
        'Y-m-d\TH:i:s',
        'd/m/Y H:i',
        'd/m/Y',
    ];

    /** @var list<string> Mensajes de filas omitidas, para reporte. */
    private array $skipped = [];

    /** @var array<string, int> Conteo de registros importados por entidad. */
    private array $imported = [
        'professionals' => 0,
        'services' => 0,
        'users' => 0,
        'reservations' => 0,
    ];

    /**
     * @param  array<string, mixed>  $data  Contenido decodificado de seed.json.
     * @return array{imported: array<string,int>, skipped: list<string>}
     */
    public function import(array $data): array
    {
        $this->importProfessionals($data['professionals'] ?? []);
        $this->importServices($data['services'] ?? []);
        $this->importUsers($data['users'] ?? []);
        $this->importReservations($data['reservations'] ?? []);

        return [
            'imported' => $this->imported,
            'skipped' => $this->skipped,
        ];
    }

    private function importProfessionals(array $rows): void
    {
        foreach ($rows as $row) {
            if (empty($row['id']) || empty($row['name'])) {
                $this->skip('professional', $row, 'falta id o name');

                continue;
            }

            Professional::query()->updateOrCreate(
                ['id' => $row['id']],
                ['name' => $row['name']],
            );
            $this->imported['professionals']++;
        }
    }

    private function importServices(array $rows): void
    {
        foreach ($rows as $row) {
            if (empty($row['id'])) {
                $this->skip('service', $row, 'falta id');

                continue;
            }
            if (! isset($row['duration_minutes'])) {
                $this->skip('service', $row, 'falta duration_minutes');

                continue;
            }
            if (empty($row['professional_id'])) {
                $this->skip('service', $row, 'falta professional_id');

                continue;
            }
            if (! Professional::query()->whereKey($row['professional_id'])->exists()) {
                $this->skip('service', $row, "professional_id {$row['professional_id']} inexistente");

                continue;
            }

            Service::query()->updateOrCreate(
                ['id' => $row['id']],
                [
                    'name' => $row['name'] ?? 'Servicio sin nombre',
                    'duration_minutes' => (int) $row['duration_minutes'],
                    'price_cents' => $this->toCents($row['price'] ?? 0),
                    'non_refundable' => (bool) ($row['non_refundable'] ?? false),
                    'professional_id' => $row['professional_id'],
                ],
            );
            $this->imported['services']++;
        }
    }

    private function importUsers(array $rows): void
    {
        foreach ($rows as $row) {
            if (empty($row['id']) || empty($row['email'])) {
                $this->skip('user', $row, 'falta id o email');

                continue;
            }

            User::query()->updateOrCreate(
                ['id' => $row['id']],
                [
                    'name' => $row['name'] ?? 'Usuario',
                    'email' => $row['email'],
                    'password' => Hash::make('password'),
                    'plan' => $this->normalizePlan($row['plan'] ?? null),
                ],
            );
            $this->imported['users']++;
        }
    }

    private function importReservations(array $rows): void
    {
        foreach ($rows as $row) {
            if (empty($row['id']) || empty($row['user_id']) || empty($row['service_id'])) {
                $this->skip('reservation', $row, 'falta id, user_id o service_id');

                continue;
            }

            $service = Service::query()->find($row['service_id']);
            if ($service === null) {
                $this->skip('reservation', $row, "service_id {$row['service_id']} inexistente");

                continue;
            }
            if (! User::query()->whereKey($row['user_id'])->exists()) {
                $this->skip('reservation', $row, "user_id {$row['user_id']} inexistente");

                continue;
            }

            $startsAt = $this->parseDate($row['starts_at'] ?? null);
            if ($startsAt === null) {
                $this->skip('reservation', $row, 'starts_at no parseable: '.($row['starts_at'] ?? 'null'));

                continue;
            }

            Reservation::query()->updateOrCreate(
                ['id' => $row['id']],
                [
                    'user_id' => $row['user_id'],
                    'service_id' => $service->id,
                    'professional_id' => $row['professional_id'] ?? $service->professional_id,
                    'starts_at' => $startsAt,
                    'ends_at' => $startsAt->copy()->addMinutes($service->duration_minutes),
                    'status' => $this->normalizeStatus($row['status'] ?? null),
                    'price_cents' => $service->price_cents,
                ],
            );
            $this->imported['reservations']++;
        }
    }

    public function parseDate(mixed $value): ?Carbon
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $tz = config('reservations.timezone');

        foreach (self::DATE_FORMATS as $format) {
            try {
                $parsed = Carbon::createFromFormat($format, $value, $tz);
            } catch (Throwable) {
                continue;
            }
            if ($parsed instanceof Carbon && $parsed->format($format) === $value) {
                return $parsed;
            }
        }

        // Último recurso: parser flexible de Carbon (maneja offsets ISO).
        try {
            return Carbon::parse($value, $tz);
        } catch (Throwable) {
            return null;
        }
    }

    private function normalizePlan(?string $plan): UserPlan
    {
        return UserPlan::tryFrom((string) $plan) ?? UserPlan::Standard;
    }

    private function normalizeStatus(?string $status): ReservationStatus
    {
        return ReservationStatus::tryFrom((string) $status) ?? ReservationStatus::Active;
    }

    private function toCents(int|float|string $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }

    private function skip(string $entity, array $row, string $reason): void
    {
        $id = $row['id'] ?? '?';
        $this->skipped[] = "[{$entity} #{$id}] {$reason}";
    }
}
