<?php

namespace App\Services;

use App\Models\LokasiPresensiModel;
use App\Models\PegawaiModel;
use CodeIgniter\Database\BaseConnection;

/**
 * Shared lokasi business rules for Web + API + seeders + cleanup.
 *
 * Invariants:
 *  - nama_lokasi is unique (case-insensitive trim)
 *  - (latitude, longitude) pair is unique after normalize_coordinate()
 *  - slug is unique and derived from nama
 */
class LokasiService
{
    public function __construct(
        protected ?LokasiPresensiModel $model = null,
        protected ?BaseConnection $db = null,
    ) {
        $this->model ??= model(LokasiPresensiModel::class);
        $this->db    ??= db_connect();
        helper('geo');
    }

    /**
     * Build a normalized payload from request input.
     * Accepts maps_url / google_maps_url to fill lat/lng.
     *
     * @param array<string, mixed> $input
     * @return array{ok: bool, data?: array<string, mixed>, errors?: array<string, string>}
     */
    public function preparePayload(array $input, ?int $excludeId = null): array
    {
        $errors = [];

        $nama = trim((string) ($input['nama_lokasi'] ?? ''));
        if ($nama === '') {
            $errors['nama_lokasi'] = 'Nama lokasi wajib diisi';
        }

        $mapsUrl = trim((string) ($input['maps_url'] ?? $input['google_maps_url'] ?? ''));
        $lat     = $input['latitude'] ?? null;
        $lng     = $input['longitude'] ?? null;

        if ($mapsUrl !== '') {
            $parsed = parse_google_maps_url($mapsUrl);
            if ($parsed === null) {
                $errors['maps_url'] = 'Link Google Maps tidak valid / koordinat tidak ditemukan';
            } else {
                $lat = $parsed['lat'];
                $lng = $parsed['lng'];
            }
        }

        $latNorm = normalize_coordinate($lat);
        $lngNorm = normalize_coordinate($lng);

        if ($latNorm === null || $lngNorm === null) {
            $errors['latitude']  = $errors['latitude'] ?? 'Latitude wajib diisi (angka atau lewat link Maps)';
            $errors['longitude'] = $errors['longitude'] ?? 'Longitude wajib diisi (angka atau lewat link Maps)';
        } else {
            if ((float) $latNorm < -90 || (float) $latNorm > 90) {
                $errors['latitude'] = 'Latitude harus antara -90 dan 90';
            }
            if ((float) $lngNorm < -180 || (float) $lngNorm > 180) {
                $errors['longitude'] = 'Longitude harus antara -180 dan 180';
            }
        }

        $radius = $input['radius'] ?? 100;
        if ($radius === '' || ! is_numeric($radius) || (float) $radius < 10) {
            $errors['radius'] = 'Radius minimal 10 meter';
        }

        $jamMasuk  = $this->normalizeTime((string) ($input['jam_masuk'] ?? '07:00'));
        $jamPulang = $this->normalizeTime((string) ($input['jam_pulang'] ?? '15:30'));
        if ($jamMasuk === null) {
            $errors['jam_masuk'] = 'Jam masuk tidak valid';
        }
        if ($jamPulang === null) {
            $errors['jam_pulang'] = 'Jam pulang tidak valid';
        }

        $zona = (string) ($input['zona_waktu'] ?? 'Asia/Jakarta');
        if ($zona === 'WIB') {
            $zona = 'Asia/Jakarta';
        }
        if (! in_array($zona, ['Asia/Jakarta', 'Asia/Makassar', 'Asia/Jayapura'], true)) {
            $errors['zona_waktu'] = 'Zona waktu tidak valid';
        }

        $tipe = (string) ($input['tipe_lokasi'] ?? 'Pusat');
        if (! in_array($tipe, ['Pusat', 'Cabang'], true)) {
            $errors['tipe_lokasi'] = 'Tipe lokasi harus Pusat atau Cabang';
        }

        $slug = url_title($nama !== '' ? $nama : 'lokasi', '-', true);

        if ($nama !== '' && $this->namaExists($nama, $excludeId)) {
            $errors['nama_lokasi'] = 'Nama lokasi sudah dipakai';
        }

        if ($latNorm !== null && $lngNorm !== null && $this->coordsExist($latNorm, $lngNorm, $excludeId)) {
            $errors['latitude']  = 'Koordinat ini sudah terdaftar pada lokasi lain';
            $errors['longitude'] = 'Koordinat ini sudah terdaftar pada lokasi lain';
        }

        if ($slug !== '' && $this->slugExists($slug, $excludeId)) {
            // Only surface if name itself wasn't already flagged
            $errors['nama_lokasi'] = $errors['nama_lokasi'] ?? 'Slug dari nama lokasi bentrok dengan data lain';
        }

        if ($errors !== []) {
            return ['ok' => false, 'errors' => $errors];
        }

        return [
            'ok'   => true,
            'data' => [
                'nama_lokasi'   => $nama,
                'slug'          => $slug,
                'alamat_lokasi' => trim((string) ($input['alamat_lokasi'] ?? '')) ?: '-',
                'tipe_lokasi'   => $tipe,
                'latitude'      => $latNorm,
                'longitude'     => $lngNorm,
                'radius'        => (int) $radius,
                'zona_waktu'    => $zona,
                'jam_masuk'     => $jamMasuk,
                'jam_pulang'    => $jamPulang,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public function save(array $data, ?int $id = null): int
    {
        if ($id) {
            $data['id'] = $id;
            $this->model->save($data);

            return $id;
        }

        $this->model->insert($data);

        return (int) $this->model->getInsertID();
    }

    public function namaExists(string $nama, ?int $excludeId = null): bool
    {
        $target = mb_strtolower(trim($nama));
        foreach ($this->db->table('lokasi_presensi')->get()->getResultArray() as $row) {
            if ($excludeId && (int) $row['id'] === $excludeId) {
                continue;
            }
            if (mb_strtolower(trim((string) $row['nama_lokasi'])) === $target) {
                return true;
            }
        }

        return false;
    }

    public function slugExists(string $slug, ?int $excludeId = null): bool
    {
        $builder = $this->db->table('lokasi_presensi')->where('slug', $slug);
        if ($excludeId) {
            $builder->where('id !=', $excludeId);
        }

        return $builder->countAllResults() > 0;
    }

    public function coordsExist(string $lat, string $lng, ?int $excludeId = null): bool
    {
        // Lokasi table is small — PHP compare after normalize avoids driver CAST quirks
        // and catches legacy unpadded strings ("-7.81433" vs "-7.8143300").
        foreach ($this->db->table('lokasi_presensi')->get()->getResultArray() as $row) {
            if ($excludeId && (int) $row['id'] === $excludeId) {
                continue;
            }
            $rLat = normalize_coordinate($row['latitude'] ?? null);
            $rLng = normalize_coordinate($row['longitude'] ?? null);
            if ($rLat === $lat && $rLng === $lng) {
                return true;
            }
        }

        return false;
    }

    /**
     * Upsert the three official SMA UII points. Safe for dev + prod.
     *
     * @return array{created: int, updated: int}
     */
    public function ensureCanonicalLocations(): array
    {
        $created = 0;
        $updated = 0;

        foreach (canonical_sma_uii_locations() as $row) {
            $existing = null;
            foreach ($this->db->table('lokasi_presensi')->get()->getResultArray() as $r) {
                if (($r['slug'] ?? '') === $row['slug']
                    || mb_strtolower(trim((string) ($r['nama_lokasi'] ?? ''))) === mb_strtolower($row['nama_lokasi'])
                ) {
                    $existing = $r;
                    break;
                }
            }

            if ($existing) {
                $this->model->update((int) $existing['id'], $row);
                $updated++;
            } else {
                // Avoid coord collision with junk rows — remove junk first if needed
                $this->removeCoordCollision($row['latitude'], $row['longitude']);
                $this->model->insert($row);
                $created++;
            }
        }

        return compact('created', 'updated');
    }

    /**
     * Deduplicate by name and by coordinates. Reassigns pegawai FKs to keeper.
     *
     * @return array{removed: int, reassigned: int}
     */
    public function deduplicate(bool $dryRun = false): array
    {
        $rows = $this->db->table('lokasi_presensi')->orderBy('id', 'ASC')->get()->getResultArray();
        $removed    = 0;
        $reassigned = 0;

        // --- by normalized name ---
        $byName = [];
        foreach ($rows as $row) {
            $key = mb_strtolower(trim((string) $row['nama_lokasi']));
            $byName[$key][] = $row;
        }

        $deleteIds = [];
        $remap     = []; // fromId => toId

        foreach ($byName as $group) {
            if (count($group) < 2) {
                continue;
            }
            $keeper = $this->pickKeeper($group);
            foreach ($group as $row) {
                if ((int) $row['id'] === (int) $keeper['id']) {
                    continue;
                }
                $deleteIds[]               = (int) $row['id'];
                $remap[(int) $row['id']] = (int) $keeper['id'];
            }
        }

        // Refresh remaining after name dedup plan, then coord dedup
        $surviving = array_filter($rows, static fn ($r) => ! in_array((int) $r['id'], $deleteIds, true));
        $byCoord   = [];
        foreach ($surviving as $row) {
            $lat = normalize_coordinate($row['latitude'] ?? null);
            $lng = normalize_coordinate($row['longitude'] ?? null);
            if ($lat === null || $lng === null) {
                continue;
            }
            $byCoord[$lat . ',' . $lng][] = $row;
        }

        foreach ($byCoord as $group) {
            if (count($group) < 2) {
                continue;
            }
            $keeper = $this->pickKeeper($group);
            foreach ($group as $row) {
                $id = (int) $row['id'];
                if ($id === (int) $keeper['id'] || in_array($id, $deleteIds, true)) {
                    continue;
                }
                $deleteIds[]  = $id;
                $remap[$id] = (int) $keeper['id'];
            }
        }

        $deleteIds = array_values(array_unique($deleteIds));

        if ($dryRun) {
            return ['removed' => count($deleteIds), 'reassigned' => count($remap)];
        }

        $this->db->transStart();

        $pegawai = model(PegawaiModel::class);
        foreach ($remap as $fromId => $toId) {
            $n = $this->db->table('pegawai')
                ->where('id_lokasi_presensi', $fromId)
                ->update(['id_lokasi_presensi' => $toId]);
            $reassigned += $n ? 1 : 0;
        }

        if ($deleteIds !== []) {
            $this->db->table('lokasi_presensi')->whereIn('id', $deleteIds)->delete();
            $removed = count($deleteIds);
        }

        // Normalize all remaining coordinates
        $left = $this->db->table('lokasi_presensi')->get()->getResultArray();
        foreach ($left as $row) {
            $nLat = normalize_coordinate($row['latitude'] ?? null);
            $nLng = normalize_coordinate($row['longitude'] ?? null);
            if ($nLat === null || $nLng === null) {
                continue;
            }
            if ($nLat !== (string) $row['latitude'] || $nLng !== (string) $row['longitude']) {
                $this->db->table('lokasi_presensi')->where('id', $row['id'])->update([
                    'latitude'  => $nLat,
                    'longitude' => $nLng,
                ]);
            }
        }

        $this->db->transComplete();

        return compact('removed', 'reassigned');
    }

    /**
     * @param list<array<string, mixed>> $group
     * @return array<string, mixed>
     */
    private function pickKeeper(array $group): array
    {
        // Prefer canonical SMA UII slugs, then most complete row, then lowest id
        $canonicalSlugs = array_column(canonical_sma_uii_locations(), 'slug');
        usort($group, static function ($a, $b) use ($canonicalSlugs) {
            $aCanon = in_array($a['slug'] ?? '', $canonicalSlugs, true) ? 0 : 1;
            $bCanon = in_array($b['slug'] ?? '', $canonicalSlugs, true) ? 0 : 1;
            if ($aCanon !== $bCanon) {
                return $aCanon <=> $bCanon;
            }
            $aSma = str_contains(mb_strtolower((string) ($a['nama_lokasi'] ?? '')), 'sma uii') ? 0 : 1;
            $bSma = str_contains(mb_strtolower((string) ($b['nama_lokasi'] ?? '')), 'sma uii') ? 0 : 1;
            if ($aSma !== $bSma) {
                return $aSma <=> $bSma;
            }

            return ((int) $a['id']) <=> ((int) $b['id']);
        });

        return $group[0];
    }

    private function removeCoordCollision(string $lat, string $lng): void
    {
        $rows = $this->db->table('lokasi_presensi')->get()->getResultArray();
        foreach ($rows as $row) {
            if (normalize_coordinate($row['latitude'] ?? null) === $lat
                && normalize_coordinate($row['longitude'] ?? null) === $lng
            ) {
                // Only delete if not referenced
                $used = $this->db->table('pegawai')->where('id_lokasi_presensi', $row['id'])->countAllResults();
                if ($used === 0) {
                    $this->db->table('lokasi_presensi')->where('id', $row['id'])->delete();
                }
            }
        }
    }

    private function normalizeTime(string $time): ?string
    {
        $time = trim($time);
        if ($time === '') {
            return null;
        }
        // HH:MM or HH:MM:SS
        if (preg_match('/^([01]\d|2[0-3]):([0-5]\d)(?::([0-5]\d))?$/', $time, $m)) {
            $sec = $m[3] ?? '00';

            return sprintf('%02d:%02d:%02d', (int) $m[1], (int) $m[2], (int) $sec);
        }

        return null;
    }
}
