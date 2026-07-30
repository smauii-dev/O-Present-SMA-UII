<?php

namespace App\Database\Seeds;

use App\Services\LokasiService;
use CodeIgniter\Database\Seeder;

/**
 * Idempotent lokasi seed — safe to re-run on dev and production.
 * Uses LokasiService::ensureCanonicalLocations() (SMA UII campus + gedung).
 */
class LokasiSeeder extends Seeder
{
    public function run()
    {
        helper('geo');

        $service = new LokasiService();
        // Clean name/coord duplicates first so UNIQUE constraints stay happy
        $dedupe = $service->deduplicate(dryRun: false);
        $seed   = $service->ensureCanonicalLocations();

        $msg = sprintf(
            'LokasiSeeder: removed=%d reassigned=%d created=%d updated=%d',
            $dedupe['removed'],
            $dedupe['reassigned'],
            $seed['created'],
            $seed['updated']
        );
        log_message('info', $msg);
        if (is_cli()) {
            echo $msg . PHP_EOL;
        }
    }
}
