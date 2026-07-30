<?php

namespace App\Database\Migrations;

use App\Services\LokasiService;
use CodeIgniter\Database\Migration;

/**
 * Root-cause fix for lokasi_presensi integrity:
 *
 * 1. Historical junk: AddAdminUser + non-idempotent seeds created duplicate
 *    "Gedung Pusat" rows (incl. Jakarta dummy coords) and duplicate SMA UII rows.
 * 2. No UNIQUE constraints → re-seed / partial migrate always dirtied the table.
 * 3. Web save path never set slug / never checked uniqueness.
 *
 * This migration:
 *  - deduplicates by name + coordinates (reassigns pegawai FKs)
 *  - normalizes lat/lng strings
 *  - ensures canonical SMA UII points
 *  - adds UNIQUE(nama_lokasi), UNIQUE(slug), UNIQUE(latitude, longitude)
 *
 * Safe on both development and production (data-preserving).
 */
class NormalizeLokasiPresensi extends Migration
{
    public function up()
    {
        helper('geo');

        $service = new LokasiService();
        $service->deduplicate(dryRun: false);
        $service->ensureCanonicalLocations();

        // Drop legacy dummy "Gedung Pusat" if still present and unused / not canonical
        $this->purgeDummyGedungPusat();

        // Enforce uniqueness at DB level (after data is clean)
        $this->addUniqueIfMissing('lokasi_presensi_nama_lokasi_key', 'nama_lokasi');
        $this->addUniqueIfMissing('lokasi_presensi_slug_key', 'slug');
        $this->addUniqueIfMissing('lokasi_presensi_coords_key', ['latitude', 'longitude']);
    }

    public function down()
    {
        try {
            $this->forge->dropKey('lokasi_presensi', 'lokasi_presensi_nama_lokasi_key', false);
        } catch (\Throwable $e) {
        }
        try {
            $this->forge->dropKey('lokasi_presensi', 'lokasi_presensi_slug_key', false);
        } catch (\Throwable $e) {
        }
        try {
            $this->forge->dropKey('lokasi_presensi', 'lokasi_presensi_coords_key', false);
        } catch (\Throwable $e) {
        }
    }

    private function addUniqueIfMissing(string $name, string|array $columns): void
    {
        $exists = false;
        try {
            if ($this->db->DBDriver === 'Postgre') {
                $row = $this->db->query(
                    'SELECT 1 AS ok FROM pg_indexes WHERE schemaname = current_schema() AND indexname = ?',
                    [$name]
                )->getRow();
                $exists = (bool) $row;
            } else {
                // MySQL
                $row = $this->db->query(
                    'SELECT 1 AS ok FROM information_schema.statistics WHERE table_schema = DATABASE() AND index_name = ? LIMIT 1',
                    [$name]
                )->getRow();
                $exists = (bool) $row;
            }
        } catch (\Throwable $e) {
            $exists = false;
        }

        if ($exists) {
            return;
        }

        try {
            $this->db->query(
                $this->buildUniqueSql('lokasi_presensi', $name, (array) $columns)
            );
        } catch (\Throwable $e) {
            // If still colliding, leave unconstrained but log — operator can re-run spark lokasi:normalize
            log_message('error', 'NormalizeLokasiPresensi unique index failed [' . $name . ']: ' . $e->getMessage());
        }
    }

    /**
     * @param list<string> $columns
     */
    private function buildUniqueSql(string $table, string $index, array $columns): string
    {
        $cols = implode(', ', array_map(static fn ($c) => '"' . $c . '"', $columns));
        if ($this->db->DBDriver === 'Postgre') {
            return 'CREATE UNIQUE INDEX IF NOT EXISTS "' . $index . '" ON "' . $table . '" (' . $cols . ')';
        }

        // MySQL has no IF NOT EXISTS for indexes pre-8.0 in all modes — we checked existence above
        return 'CREATE UNIQUE INDEX `' . $index . '` ON `' . $table . '` (' . implode(', ', array_map(static fn ($c) => '`' . $c . '`', $columns)) . ')';
    }

    private function purgeDummyGedungPusat(): void
    {
        $rows = $this->db->table('lokasi_presensi')
            ->like('nama_lokasi', 'Gedung Pusat', 'none')
            ->get()
            ->getResultArray();

        // Fallback: exact / case-insensitive
        if ($rows === []) {
            foreach ($this->db->table('lokasi_presensi')->get()->getResultArray() as $r) {
                if (mb_strtolower(trim((string) $r['nama_lokasi'])) === 'gedung pusat') {
                    $rows[] = $r;
                }
            }
        }

        $canonicalSlugs = array_column(canonical_sma_uii_locations(), 'slug');
        $fallbackId     = null;
        $fallback       = $this->db->table('lokasi_presensi')->where('slug', 'sma-uii-yogyakarta')->get()->getRowArray();
        if ($fallback) {
            $fallbackId = (int) $fallback['id'];
        }

        foreach ($rows as $row) {
            if (in_array($row['slug'] ?? '', $canonicalSlugs, true)) {
                continue;
            }
            // Jakarta dummy or generic "Gedung Pusat" from old migration
            $lat = (float) ($row['latitude'] ?? 0);
            $isJakartaDummy = abs($lat - (-6.2)) < 0.05;
            $isGenericName  = mb_strtolower(trim((string) $row['nama_lokasi'])) === 'gedung pusat';

            if (! $isJakartaDummy && ! $isGenericName) {
                continue;
            }

            $id = (int) $row['id'];
            if ($fallbackId) {
                $this->db->table('pegawai')
                    ->where('id_lokasi_presensi', $id)
                    ->update(['id_lokasi_presensi' => $fallbackId]);
            }

            $stillUsed = $this->db->table('pegawai')->where('id_lokasi_presensi', $id)->countAllResults();
            if ($stillUsed === 0) {
                $this->db->table('lokasi_presensi')->where('id', $id)->delete();
            }
        }
    }
}
