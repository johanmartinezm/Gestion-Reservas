<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\UserPlan;
use App\Models\Service;
use App\Models\User;
use App\Support\SeedImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeedImporterTest extends TestCase
{
    use RefreshDatabase;

    public function test_parses_dates_in_several_formats(): void
    {
        $importer = new SeedImporter;

        $this->assertSame('2026-07-10 09:00', $importer->parseDate('2026-07-10 09:00')?->format('Y-m-d H:i'));
        $this->assertSame('2026-07-11 14:00', $importer->parseDate('11/07/2026 14:00')?->format('Y-m-d H:i'));
        $this->assertNotNull($importer->parseDate('2026-07-10T11:00:00-05:00'));
    }

    public function test_unparseable_date_returns_null(): void
    {
        $importer = new SeedImporter;

        $this->assertNull($importer->parseDate('fecha-invalida'));
        $this->assertNull($importer->parseDate(null));
        $this->assertNull($importer->parseDate(''));
    }

    public function test_user_without_plan_defaults_to_standard(): void
    {
        $report = (new SeedImporter)->import([
            'users' => [
                ['id' => 1, 'name' => 'Sin Plan', 'email' => 'sinplan@example.com'],
            ],
        ]);

        $this->assertSame(1, $report['imported']['users']);
        $this->assertSame(UserPlan::Standard, User::find(1)->plan);
    }

    public function test_skips_rows_with_missing_required_fields(): void
    {
        $report = (new SeedImporter)->import([
            'professionals' => [['id' => 1, 'name' => 'Ana']],
            'services' => [
                ['id' => 1, 'name' => 'Sin duración', 'price' => 1000, 'professional_id' => 1],
            ],
            'users' => [
                ['id' => 1, 'name' => 'Sin email'],
            ],
            'reservations' => [
                ['id' => 1, 'user_id' => 1, 'service_id' => 1, 'starts_at' => 'no-fecha'],
            ],
        ]);

        $this->assertSame(0, $report['imported']['services']);
        $this->assertSame(0, $report['imported']['users']);
        $this->assertSame(0, $report['imported']['reservations']);
        $this->assertNotEmpty($report['skipped']);
        $this->assertDatabaseCount('services', 0);
        $this->assertDatabaseCount('reservations', 0);
    }

    public function test_normalizes_price_to_cents(): void
    {
        (new SeedImporter)->import([
            'professionals' => [['id' => 1, 'name' => 'Ana']],
            'services' => [
                ['id' => 1, 'name' => 'Corte', 'duration_minutes' => 30, 'price' => 30000, 'professional_id' => 1],
            ],
        ]);

        $this->assertSame(3000000, Service::find(1)->price_cents);
    }
}
