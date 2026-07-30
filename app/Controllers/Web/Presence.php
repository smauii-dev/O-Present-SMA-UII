<?php

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Services\PresensiService;

class Presence extends BaseController
{
    protected PresensiService $presensiService;

    public function __construct()
    {
        $this->presensiService = new PresensiService();
    }

    /**
     * Clock-in / clock-out page — views/routes/presensi/index.twig
     */
    public function index()
    {
        $user = $this->getAuthUser();
        if (!$user) {
            return redirect()->to('/login');
        }

        $presensiModel = model('PresensiModel');
        $today = $presensiModel->cekPresensiMasuk($user->id_pegawai, date('Y-m-d'));

        $lokasiModel = model('LokasiPresensiModel');
        $lokasi = $lokasiModel->where('id', $user->id_lokasi_presensi)->get()->getFirstRow();

        $durasiKerja = null;
        $keterlambatan = null;

        if ($today && $today->jam_keluar && $lokasi) {
            $stats = PresensiService::calculateWorkStats(
                $today->tanggal_masuk,
                $today->jam_masuk,
                $today->tanggal_keluar ?: $today->tanggal_masuk,
                $today->jam_keluar,
                $lokasi->jam_masuk
            );
            $durasiKerja = $stats['jam_kerja'];
            $keterlambatan = $stats['keterlambatan'];
        }

        return $this->view('routes/presensi/index', [
            'pageTitle' => 'Presensi',
            'user' => $user,
            'lokasi' => $lokasi,
            'presensi' => [
                'sudah_masuk' => (bool) $today,
                'sudah_pulang' => $today ? (bool) $today->jam_keluar : false,
                'data' => $today,
            ],
            'durasi_kerja' => $durasiKerja,
            'keterlambatan' => $keterlambatan,
        ]);
    }

    public function clockIn()
    {
        $userProfile = $this->getAuthUser();
        if (!$userProfile) {
            return $this->response->setStatusCode(401)->setJSON(['message' => 'Belum login']);
        }

        $latitude = $this->request->getPost('latitude');
        $longitude = $this->request->getPost('longitude');
        $fotoBase64 = $this->request->getPost('foto');

        if (empty($latitude) || empty($longitude)) {
            return $this->hxError(422, 'Lokasi tidak terdeteksi. Mohon aktifkan GPS.');
        }
        if (empty($fotoBase64)) {
            return $this->hxError(422, 'Foto wajib diisi');
        }

        $presensiModel = model('PresensiModel');
        $existing = $presensiModel->cekPresensiMasuk($userProfile->id_pegawai, date('Y-m-d'));
        if ($existing) {
            return $this->hxError(409, 'Sudah melakukan presensi masuk hari ini');
        }

        $locationCheck = $this->presensiService->validateLocation(
            (float) $latitude,
            (float) $longitude,
            (string) ($userProfile->lokasi_presensi ?? '')
        );
        if (!$locationCheck['valid']) {
            return $this->hxError(403, $locationCheck['message']);
        }

        try {
            $photoService = new \App\Services\PhotoService();
            $fotoResult = $photoService->uploadPresensiPhoto('masuk', $userProfile->username, $fotoBase64);
        } catch (\Exception $e) {
            log_message('error', 'S3 upload clock-in failed: ' . $e->getMessage());

            return $this->hxError(500, 'Gagal menyimpan foto presensi');
        }

        $presensiModel->save([
            'id_pegawai' => $userProfile->id_pegawai,
            'tanggal_masuk' => date('Y-m-d'),
            'jam_masuk' => date('H:i:s'),
            'foto_masuk' => $fotoResult['filename'],
        ]);

        return $this->response
            ->setHeader('HX-Trigger', json_encode([
                'toast' => ['type' => 'success', 'message' => 'Presensi masuk berhasil'],
            ]))
            ->setBody('');
    }

    public function clockOut()
    {
        $userProfile = $this->getAuthUser();
        if (!$userProfile) {
            return $this->response->setStatusCode(401)->setJSON(['message' => 'Belum login']);
        }

        $presensiModel = model('PresensiModel');
        $presensi = $presensiModel->cekPresensiMasuk($userProfile->id_pegawai, date('Y-m-d'));
        if (!$presensi) {
            return $this->hxError(404, 'Belum ada presensi masuk hari ini');
        }

        if ($presensi->jam_keluar) {
            return $this->hxError(409, 'Sudah melakukan presensi keluar hari ini');
        }

        $presensiModel->save([
            'id' => $presensi->id,
            'tanggal_keluar' => date('Y-m-d'),
            'jam_keluar' => date('H:i:s'),
        ]);

        return $this->response
            ->setHeader('HX-Trigger', json_encode([
                'toast' => ['type' => 'success', 'message' => 'Presensi keluar berhasil'],
            ]))
            ->setBody('');
    }

    public function resetToday()
    {
        $userProfile = $this->getAuthUser();
        if (!$userProfile) {
            return $this->hxError(401, 'Belum login');
        }

        $presensiModel = model('PresensiModel');
        $presensi = $presensiModel->cekPresensiMasuk($userProfile->id_pegawai, date('Y-m-d'));
        
        if (!$presensi) {
            return $this->hxError(404, 'Belum ada presensi hari ini');
        }

        $presensiModel->delete($presensi->id);

        return $this->response
            ->setHeader('HX-Trigger', json_encode([
                'toast' => ['type' => 'success', 'message' => 'Presensi hari ini direset'],
            ]))
            ->setHeader('HX-Redirect', '/presensi');
    }

    private function hxError(int $status, string $message)
    {
        return $this->response
            ->setStatusCode($status)
            ->setHeader('HX-Trigger', json_encode([
                'toast' => ['type' => 'error', 'message' => $message],
            ]))
            ->setJSON(['message' => $message]);
    }
}
