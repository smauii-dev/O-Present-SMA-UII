<?php

namespace App\Commands;

use App\Services\LokasiService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Environment-aware database hygiene.
 *
 * Development:
 *   php spark db:cleanup --dev
 *     → dedupe lokasi, ensure canonical points, optional soft-reset of
 *       non-canonical seed junk (never drops auth/presensi tables)
 *
 * Production:
 *   php spark db:cleanup --prod
 *     → same lokasi normalize only (safe). Refuses destructive flags.
 *
 *   php spark db:cleanup --prod --dry-run
 *
 * Root problem this addresses: migrations mixed with seed data + no UNIQUE
 * constraints caused dev/prod lokasi tables to drift (duplicate names/coords).
 * Cleanup is the operational twin of migration NormalizeLokasiPresensi.
 */
class DbCleanup extends BaseCommand
{
    protected $group       = 'Database';
    protected $name        = 'db:cleanup';
    protected $description = 'Normalize lokasi (and related) for dev or production';
    protected $usage       = 'db:cleanup --dev|--prod [--dry-run]';
    protected $options     = [
        '--dev'     => 'Development profile (normalize + report)',
        '--prod'    => 'Production profile (normalize only, safer messaging)',
        '--dry-run' => 'Report only',
    ];

    public function run(array $params)
    {
        $isDev  = CLI::getOption('dev') !== null || array_key_exists('dev', $params);
        $isProd = CLI::getOption('prod') !== null || array_key_exists('prod', $params);
        $dryRun = CLI::getOption('dry-run') !== null || array_key_exists('dry-run', $params);

        $env = env('CI_ENVIRONMENT', 'production');

        if (! $isDev && ! $isProd) {
            // Infer from CI_ENVIRONMENT
            $isDev  = $env === 'development';
            $isProd = ! $isDev;
            CLI::write("No --dev/--prod flag; inferred from CI_ENVIRONMENT={$env}", 'yellow');
        }

        if ($isDev && $isProd) {
            CLI::error('Use either --dev or --prod, not both.');

            return;
        }

        $profile = $isProd ? 'production' : 'development';
        CLI::write("db:cleanup profile={$profile} env={$env} dryRun=" . ($dryRun ? 'yes' : 'no'), 'yellow');

        if ($isProd && $env === 'development') {
            CLI::write('Warning: --prod on a development CI_ENVIRONMENT. Continuing (explicit flag wins).', 'yellow');
        }

        if ($isDev && $env === 'production') {
            CLI::error('Refusing --dev against CI_ENVIRONMENT=production. Use --prod.');

            return;
        }

        helper('geo');
        $service = new LokasiService();

        // 1) Always: lokasi dedupe + canonical (the actual drift source)
        $dedupe = $service->deduplicate(dryRun: $dryRun);
        CLI::write(sprintf(
            '[lokasi] dedupe removed=%d reassigned=%d',
            $dedupe['removed'],
            $dedupe['reassigned']
        ), 'white');

        if (! $dryRun) {
            $seed = $service->ensureCanonicalLocations();
            CLI::write(sprintf(
                '[lokasi] canonical created=%d updated=%d',
                $seed['created'],
                $seed['updated']
            ), 'green');
        } else {
            CLI::write('[lokasi] canonical upsert skipped (dry-run)', 'light_gray');
        }

        // 2) Dev-only: dedupe jabatan by slug (keep lowest id, reassign pegawai)
        if ($isDev) {
            $db = db_connect();
            $this->dedupeJabatan($db, $dryRun);
        }

        if ($isProd) {
            CLI::write('[prod] Skipped destructive resets (presensi/users untouched).', 'green');
        }

        CLI::write('db:cleanup finished.', 'green');
    }

    private function dedupeJabatan($db, bool $dryRun): void
    {
        $rows = $db->table('jabatan')->orderBy('id', 'ASC')->get()->getResultArray();
        $bySlug = [];
        foreach ($rows as $r) {
            $key = strtolower(trim((string) ($r['slug'] ?? $r['jabatan'])));
            $bySlug[$key][] = $r;
        }

        $removed = 0;
        foreach ($bySlug as $group) {
            if (count($group) < 2) {
                continue;
            }
            $keeper = (int) $group[0]['id'];
            for ($i = 1, $n = count($group); $i < $n; $i++) {
                $dupId = (int) $group[$i]['id'];
                if ($dryRun) {
                    $removed++;
                    continue;
                }
                $db->table('pegawai')->where('id_jabatan', $dupId)->update(['id_jabatan' => $keeper]);
                $db->table('jabatan')->where('id', $dupId)->delete();
                $removed++;
            }
        }

        CLI::write(sprintf('[dev] jabatan dedupe removed=%d%s', $removed, $dryRun ? ' (dry-run)' : ''), $removed ? 'yellow' : 'green');
    }
}
