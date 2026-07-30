<?php

namespace App\Controllers\Api;

use App\Models\PresensiModel;
use App\Models\LokasiPresensiModel;
use App\Services\ExportService;
use App\Services\PresensiService;

class Presence extends ApiBaseController
{
    protected PresensiModel $presensiModel;
    protected LokasiPresensiModel $lokasiModel;
    protected ExportService $exportService;
    protected PresensiService $presensiService;

    public function __construct()
    {
        $this->presensiModel = new PresensiModel();
        $this->lokasiModel = new LokasiPresensiModel();
        $this->exportService = new ExportService();
        $this->presensiService = new PresensiService();
    }

    public function clockIn()
    {
        $userProfile = $this->getUserOrError();
        if (is_object($userProfile) === false) return $userProfile;

        $latitude = $this->getInput('latitude');
        $longitude = $this->getInput('longitude');
        $fotoBase64 = $this->getInput('foto');

        if (empty($latitude) || empty($longitude)) {
            return $this->error('Lokasi tidak terdeteksi. Mohon aktifkan GPS.', 422);
        }

        if (empty($fotoBase64)) {
            return $this->error('Foto wajib diisi', 422);
        }

        // Check if already clocked in today
        $existing = $this->presensiModel->cekPresensiMasuk($userProfile->id_pegawai, date('Y-m-d'));
        if ($existing) {
            return $this->error('Sudah melakukan presensi masuk hari ini', 409);
        }

        // Validate location
        $locationCheck = $this->presensiService->validateLocation(
            (float) $latitude,
            (float) $longitude,
            $userProfile->lokasi_presensi
        );
        if (!$locationCheck['valid']) {
            return $this->error($locationCheck['message'], 403);
        }

        // Upload photo to S3
        try {
            $fotoResult = $this->getPhotoService()->uploadPresensiPhoto('masuk', $userProfile->username, $fotoBase64);
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        } catch (\Exception $e) {
            log_message('error', 'S3 upload clock-in failed: ' . $e->getMessage());
            return $this->error('Gagal menyimpan foto presensi', 500);
        }

        $this->presensiModel->save([
            'id_pegawai' => $userProfile->id_pegawai,
            'tanggal_masuk' => date('Y-m-d'),
            'jam_masuk' => date('H:i:s'),
            'foto_masuk' => $fotoResult['filename'],
        ]);

        return $this->success([
            'tanggal_masuk' => date('Y-m-d'),
            'jam_masuk' => date('H:i:s'),
            'foto' => $fotoResult['filename'],
            'jarak_meter' => round($locationCheck['distance']),
        ], 'Presensi masuk berhasil');
    }

    public function clockOut()
    {
        $userProfile = $this->getUserOrError();
        if (is_object($userProfile) === false) return $userProfile;

        $fotoBase64 = $this->getInput('foto');
        if (empty($fotoBase64)) {
            return $this->error('Foto wajib diisi', 422);
        }

        $presensi = $this->presensiModel->cekPresensiMasuk($userProfile->id_pegawai, date('Y-m-d'));
        if (!$presensi) {
            return $this->error('Belum ada presensi masuk hari ini', 404);
        }

        if ($presensi->jam_keluar) {
            return $this->error('Sudah melakukan presensi keluar hari ini', 409);
        }

        try {
            $fotoResult = $this->getPhotoService()->uploadPresensiPhoto('keluar', $userProfile->username, $fotoBase64);
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        } catch (\Exception $e) {
            log_message('error', 'S3 upload clock-out failed: ' . $e->getMessage());
            return $this->error('Gagal menyimpan foto presensi', 500);
        }

        $this->presensiModel->save([
            'id' => $presensi->id,
            'tanggal_keluar' => date('Y-m-d'),
            'jam_keluar' => date('H:i:s'),
            'foto_keluar' => $fotoResult['filename'],
        ]);

        return $this->success([
            'tanggal_keluar' => date('Y-m-d'),
            'jam_keluar' => date('H:i:s'),
            'foto' => $fotoResult['filename'],
        ], 'Presensi keluar berhasil');
    }

    public function today()
    {
        $userProfile = $this->getUserOrError();
        if (is_object($userProfile) === false) return $userProfile;

        $presensi = $this->presensiModel->cekPresensiMasuk($userProfile->id_pegawai, date('Y-m-d'));

        $result = null;
        if ($presensi) {
            $result = (array) $presensi;
            $result['foto_masuk_url'] = $this->getPresensiPhotoUrl('masuk', $presensi->foto_masuk ?? null);
            $result['foto_keluar_url'] = $this->getPresensiPhotoUrl('keluar', $presensi->foto_keluar ?? null);
        }

        return $this->success(['presensi_hari_ini' => $result]);
    }

    public function rekap()
    {
        $userProfile = $this->getUserOrError();
        if (is_object($userProfile) === false) return $userProfile;

        $page = (int) ($this->getInput('page', 1));
        $perPage = 10;
        $tanggalDari = $this->getInput('tanggal_dari');
        $tanggalSampai = $this->getInput('tanggal_sampai');

        $data = $this->presensiModel->getDataPresensi($userProfile->id_pegawai, $tanggalDari, $tanggalSampai);
        $allData = $data['rekap-presensi'] ?? [];
        $total = $data['total'] ?? 0;

        $allData = array_map([$this, 'enrichPresensiData'], $allData);

        return $this->paginated($allData, $total, $page, $perPage);
    }

    public function exportRekap()
    {
        $profile = $this->getUserOrError();
        if (!$profile) return $this->error('User profile not found', 404);

        $tanggalAwal = $this->getInput('tanggal_awal', '');
        $tanggalAkhir = $this->getInput('tanggal_akhir', '');
        if ($tanggalAwal === '') {
            $minDate = $this->presensiModel->getMinDate($profile->id_pegawai);
            $tanggalAwal = $minDate ?? date('Y-m-d', strtotime('-30 days'));
        }
        if ($tanggalAkhir === '') {
            $tanggalAkhir = date('Y-m-d');
        }

        // Fetch data with computed fields
        $dataPresensi = $this->presensiModel->getDataPresensi($profile->id_pegawai, $tanggalAwal, $tanggalAkhir, true)['rekap-presensi'];
        $enrichedData = array_map([$this, 'enrichPresensiDataForExport'], $dataPresensi);

        return $this->exportService->exportPresensiRekap($enrichedData, [
            'nama' => $profile->nama,
            'nip' => $profile->nip,
        ], $tanggalAwal, $tanggalAkhir);
    }

    protected function enrichPresensiDataForExport($item): array
    {
        if (is_object($item)) {
            $item = (array) $item;
        }
        
        $stats = \App\Services\PresensiService::calculateWorkStats(
            $item['tanggal_masuk'],
            $item['jam_masuk'],
            $item['tanggal_keluar'] ?? $item['tanggal_masuk'],
            $item['jam_keluar'] ?? '00:00:00',
            $item['jam_masuk_kantor'] ?? '07:00:00'
        );
        
        $item['jam_kerja'] = $stats['jam_kerja'];
        $item['keterlambatan'] = $stats['keterlambatan'];
        $item['_nomor'] = 1; // Will be overridden by export service

        return $item;
    }

    public function exportHarian()
    {
        if ($err = $this->requireAdmin()) return $err;

        $tanggalAwal = $this->getInput('tanggal_awal', '') ?: date('Y-m-d');
        $tanggalAkhir = $this->getInput('tanggal_akhir', '') ?: date('Y-m-d');
        $dataPresensi = $this->presensiModel->getDataPresensiHarian($tanggalAwal, $tanggalAkhir, true)['laporan-harian'];
        $enrichedData = array_map([$this, 'enrichPresensiDataForExport'], $dataPresensi);

        return $this->exportService->exportPresensiHarian($enrichedData, $tanggalAwal, $tanggalAkhir);
    }

    public function exportBulanan()
    {
        if ($err = $this->requireAdmin()) return $err;

        $filterBulan = $this->getInput('filter_bulan', '') ?: date('m');
        $filterTahun = $this->getInput('filter_tahun', '') ?: date('Y');
        $dataPresensi = $this->presensiModel->getDataPresensiBulanan($filterBulan, $filterTahun, true)['laporan-bulanan'];
        $enrichedData = array_map([$this, 'enrichPresensiDataForExport'], $dataPresensi);

        return $this->exportService->exportPresensiBulanan($enrichedData, $filterBulan, $filterTahun);
    }
}