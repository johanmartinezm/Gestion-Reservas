<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Support\SeedImporter;
use Illuminate\Database\Seeder;

class SeedJsonSeeder extends Seeder
{
    public function __construct(private readonly SeedImporter $importer) {}

    public function run(): void
    {
        $path = base_path('data/seed.json');

        if (! is_file($path)) {
            $this->command?->warn("No se encontró {$path}; se omite la siembra desde JSON.");

            return;
        }

        $data = json_decode((string) file_get_contents($path), true);

        if (! is_array($data)) {
            $this->command?->error('data/seed.json no contiene JSON válido.');

            return;
        }

        $report = $this->importer->import($data);

        foreach ($report['imported'] as $entity => $count) {
            $this->command?->info("Importados {$count} {$entity}.");
        }

        foreach ($report['skipped'] as $message) {
            $this->command?->warn("Omitido: {$message}");
        }
    }
}
