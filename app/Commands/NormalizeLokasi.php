<?php

namespace App\Commands;

use App\Services\LokasiService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Normalize lokasi_presensi for BOTH development and production.
 *
 *   php spark lokasi:normalize
 *   php spark lokasi:normalize --dry-run
 *   php spark lokasi:normalize --seed-only
 *
 * Does NOT drop tables or wipe attendance history — only cleans lokasi rows
 * and reassigns pegawai.id_lokasi_presensi when merging duplicates.
 */
class NormalizeLokasi extends BaseCommand
{
    protected $group       = 'Database';
    protected $name        = 'lokasi:normalize';
    protected $description = 'Deduplicate lokasi, enforce SMA UII points, optional unique re-check';
    protected $usage       = 'lokasi:normalize [--dry-run] [--seed-only]';
    protected $options     = [
        '--dry-run'    => 'Report what would change without writing',
        '--seed-only'  => 'Only upsert canonical SMA UII locations (no dedupe)',
    ];

    public function run(array $params)
    {
        helper('geo');
        $dryRun   = CLI::getOption('dry-run') !== null || array_key_exists('dry-run', $params);
        $seedOnly = CLI::getOption('seed-only') !== null || array_key_exists('seed-only', $params);

        $env = env('CI_ENVIRONMENT', 'production');
        CLI::write('Environment: ' . $env, 'yellow');
        CLI::write('Mode: ' . ($dryRun ? 'DRY-RUN' : 'APPLY'), $dryRun ? 'light_gray' : 'green');

        $service = new LokasiService();

        if (! $seedOnly) {
            $result = $service->deduplicate(dryRun: $dryRun);
            CLI::write(sprintf(
                'Dedupe → would_remove/removed=%d, reassigned_groups=%d',
                $result['removed'],
                $result['reassigned']
            ), 'white');
        }

        if ($dryRun) {
            $canonical = canonical_sma_uii_locations();
            CLI::write('Canonical locations that would be upserted:', 'white');
            foreach ($canonical as $row) {
                CLI::write(sprintf(
                    '  - %s (%s) @ %s,%s r=%dm',
                    $row['nama_lokasi'],
                    $row['slug'],
                    $row['latitude'],
                    $row['longitude'],
                    $row['radius']
                ), 'light_gray');
            }
            CLI::write('Dry-run complete. Re-run without --dry-run to apply.', 'yellow');

            return;
        }

        $seed = $service->ensureCanonicalLocations();
        CLI::write(sprintf(
            'Canonical upsert → created=%d, updated=%d',
            $seed['created'],
            $seed['updated']
        ), 'green');

        $db   = db_connect();
        $rows = $db->table('lokasi_presensi')->orderBy('id')->get()->getResultArray();
        CLI::newLine();
        CLI::write('Current lokasi_presensi:', 'white');
        foreach ($rows as $r) {
            CLI::write(sprintf(
                '  #%d  %-28s  %s,%s  r=%s',
                $r['id'],
                $r['nama_lokasi'],
                $r['latitude'],
                $r['longitude'],
                $r['radius']
            ));
        }

        CLI::write('Done.', 'green');
    }
}
