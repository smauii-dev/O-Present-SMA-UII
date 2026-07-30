<?php

namespace App\Services;

use App\Models\PresensiModel;
use App\Models\LokasiPresensiModel;
use App\Models\UsersModel;

class PresensiService
{
    protected PresensiModel $presensiModel;
    protected LokasiPresensiModel $lokasiModel;
    protected UsersModel $usersModel;
    protected PhotoService $photoService;

    public function __construct()
    {
        $this->presensiModel = new PresensiModel();
        $this->lokasiModel = new LokasiPresensiModel();
        $this->usersModel = new UsersModel();
        $this->photoService = new PhotoService();
    }

    /**
     * Validate that the user is within the allowed radius.
     * Returns ['valid' => bool, 'message' => string, 'distance' => float]
     */
    public function validateLocation(float $latPegawai, float $lonPegawai, string $lokasiPresensi): array
    {
        $lokasi = $this->lokasiModel->getWhere(['nama_lokasi' => $lokasiPresensi])->getFirstRow();

        if (!$lokasi) {
            return ['valid' => false, 'message' => 'Lokasi presensi tidak ditemukan', 'distance' => 0];
        }

        $latitudeKantor = $lokasi->latitude;
        $longitudeKantor = $lokasi->longitude;
        $radius = $lokasi->radius;

        if (empty($latitudeKantor) || empty($longitudeKantor)) {
            return ['valid' => false, 'message' => 'Koordinat lokasi kantor tidak valid', 'distance' => 0];
        }

        $distance = haversine_distance_meters($latPegawai, $lonPegawai, $latitudeKantor, $longitudeKantor);

        if ($distance > $radius) {
            return [
                'valid' => false,
                'message' => 'Anda berada di luar area kantor (' . round($distance) . 'm dari pusat)',
                'distance' => $distance,
            ];
        }

        return ['valid' => true, 'message' => '', 'distance' => $distance];
    }

    /**
     * Perform clock-in for a user.
     */
    public function clockIn(int $userId, string $base64Foto): array
    {
        $userProfile = $this->usersModel->getUserInfo($userId);
        if (!$userProfile) {
            throw new \RuntimeException('User tidak ditemukan');
        }

        $presensi = $this->presensiModel->cekPresensiMasuk($userProfile->id_pegawai, date('Y-m-d'));
        if ($presensi) {
            throw new \RuntimeException('Sudah melakukan presensi masuk hari ini');
        }

        $result = $this->photoService->uploadPresensiPhoto('masuk', $userProfile->username, $base64Foto);

        $this->presensiModel->save([
            'id_pegawai' => $userProfile->id_pegawai,
            'tanggal_masuk' => date('Y-m-d'),
            'jam_masuk' => date('H:i:s'),
            'foto_masuk' => $result['filename'],
        ]);

        return [
            'tanggal_masuk' => date('Y-m-d'),
            'jam_masuk' => date('H:i:s'),
            'foto' => $result['filename'],
        ];
    }

    /**
     * Perform clock-out for a user.
     */
    public function clockOut(int $userId, string $base64Foto): array
    {
        $userProfile = $this->usersModel->getUserInfo($userId);
        if (!$userProfile) {
            throw new \RuntimeException('User tidak ditemukan');
        }

        $presensi = $this->presensiModel->cekPresensiMasuk($userProfile->id_pegawai, date('Y-m-d'));
        if (!$presensi) {
            throw new \RuntimeException('Belum ada presensi masuk hari ini');
        }

        if ($presensi->jam_keluar) {
            throw new \RuntimeException('Sudah melakukan presensi keluar hari ini');
        }

        $result = $this->photoService->uploadPresensiPhoto('keluar', $userProfile->username, $base64Foto);

        $this->presensiModel->save([
            'id' => $presensi->id,
            'tanggal_keluar' => date('Y-m-d'),
            'jam_keluar' => date('H:i:s'),
            'foto_keluar' => $result['filename'],
        ]);

        return [
            'tanggal_keluar' => date('Y-m-d'),
            'jam_keluar' => date('H:i:s'),
            'foto' => $result['filename'],
        ];
    }

    /**
     * Get today's presensi data for a user, enriched with photo URLs.
     */
    public function getTodayPresensi(int $userId): ?array
    {
        $userProfile = $this->usersModel->getUserInfo($userId);
        if (!$userProfile) {
            return null;
        }

        $presensi = $this->presensiModel->cekPresensiMasuk($userProfile->id_pegawai, date('Y-m-d'));

        if (!$presensi) {
            return null;
        }

        $result = (array) $presensi;
        $result['foto_masuk_url'] = $presensi->foto_masuk
            ? $this->photoService->getPresensiPhotoUrl('masuk', $presensi->foto_masuk)
            : null;
        $result['foto_keluar_url'] = $presensi->foto_keluar
            ? $this->photoService->getPresensiPhotoUrl('keluar', $presensi->foto_keluar)
            : null;

        return $result;
    }

    /**
     * Get paginated rekap presensi for a user, enriched with photo URLs.
     */
    public function getRekapPresensi(int $userId, ?string $tanggalDari = null, ?string $tanggalSampai = null): array
    {
        $userProfile = $this->usersModel->getUserInfo($userId);
        if (!$userProfile) {
            return ['data' => [], 'total' => 0];
        }

        $data = $this->presensiModel->getDataPresensi($userProfile->id_pegawai, $tanggalDari, $tanggalSampai);
        $allData = $data['rekap-presensi'] ?? [];

        foreach ($allData as &$item) {
            if (is_object($item)) {
                $item = (array) $item;
            }
            if (!empty($item['foto_masuk'])) {
                $item['foto_masuk_url'] = $this->photoService->getPresensiPhotoUrl('masuk', $item['foto_masuk']);
            }
            if (!empty($item['foto_keluar']) && $item['foto_keluar'] !== '-') {
                $item['foto_keluar_url'] = $this->photoService->getPresensiPhotoUrl('keluar', $item['foto_keluar']);
            }
        }
        unset($item);

        return [
            'data' => $allData,
            'total' => $data['total'] ?? 0,
        ];
    }

    /**
     * Calculate work duration and lateness from presensi data.
     */
    public static function calculateWorkStats(string $tanggalMasuk, string $jamMasuk, string $tanggalKeluar, string $jamKeluar, string $jamMasukKantor): array
    {
        $datetimeMasuk = strtotime($tanggalMasuk . ' ' . $jamMasuk);
        $datetimeKeluar = strtotime($tanggalKeluar . ' ' . $jamKeluar);

        $selisih = $datetimeKeluar - $datetimeMasuk;

        $totalJamKerja = floor($selisih / 3600);
        $selisihMenitKerja = floor(($selisih % 3600) / 60);

        $jamKerjaFormat = $totalJamKerja < 0
            ? '0 Jam 0 Menit'
            : sprintf("%d Jam %d Menit", $totalJamKerja, $selisihMenitKerja);

        $timestampJamMasuk = strtotime(date('H:i:s', strtotime($jamMasuk)));
        $timestampJamMasukKantor = strtotime($jamMasukKantor);
        $terlambat = $timestampJamMasuk - $timestampJamMasukKantor;

        $totalKeterlambatan = floor($terlambat / 3600);
        $selisihMenitKeterlambatan = floor(($terlambat % 3600) / 60);

        $keterlambatanFormat = $totalKeterlambatan < 0
            ? 'On Time'
            : sprintf("%d Jam %d Menit", $totalKeterlambatan, $selisihMenitKeterlambatan);

        return [
            'jam_kerja' => $jamKerjaFormat,
            'keterlambatan' => $keterlambatanFormat,
        ];
    }

    public function __get($name)
    {
        return $this->$name ?? null;
    }
}
