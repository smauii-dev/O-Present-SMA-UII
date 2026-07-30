<?php

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Services\PresensiService;

class Overview extends BaseController
{
    /**
     * Public splash screen at root — then client redirects to /overview.
     */
    public function splash()
    {
        return $this->view('routes/splash/index', [
            'pageTitle' => 'O-Present',
            'redirectUrl' => site_url('overview'),
            'isAuthenticated' => (bool) (function_exists('user_id') && user_id()),
        ]);
    }

    /**
     * Main app entrypoint after splash / login.
     */
    public function index()
    {
        $user = $this->getAuthUser();
        if (!$user) {
            return redirect()->to('/login');
        }

        $data = [
            'user' => $user,
            'pageTitle' => 'Overview',
            'greeting' => $this->greeting(),
            'todayLabel' => $this->todayLabel(),
        ];

        $role = $user->role ?? '';
        if (in_array($role, ['admin', 'head'], true)) {
            $pegawaiModel = model('PegawaiModel');
            $presensiModel = model('PresensiModel');
            $ketidakhadiranModel = model('KetidakhadiranModel');

            $jumlahPegawaiAktif = $pegawaiModel->getJumlahPegawaiAktif();
            $jumlahPegawaiHadir = $presensiModel->getDataPresensiHariIni();
            $jumlahPegawaiIzin = $ketidakhadiranModel->getDataIzinHariIni();
            $jumlahPegawaiAlpha = max(0, $jumlahPegawaiAktif - ($jumlahPegawaiHadir + $jumlahPegawaiIzin));

            $data['stats'] = [
                'pegawai_aktif' => $jumlahPegawaiAktif,
                'pegawai_hadir' => $jumlahPegawaiHadir,
                'pegawai_izin' => $jumlahPegawaiIzin,
                'pegawai_alpha' => $jumlahPegawaiAlpha,
            ];

            return $this->view('routes/overview/index', $data);
        }

        $presensiModel = model('PresensiModel');
        $today = $presensiModel->where('id_pegawai', $user->id_pegawai)
            ->where('tanggal_masuk', date('Y-m-d'))
            ->get()->getFirstRow();

        $lokasiModel = model('LokasiPresensiModel');
        $lokasi = $lokasiModel->where('id', $user->id_lokasi_presensi)->get()->getFirstRow();

        $data['presensi'] = [
            'sudah_masuk' => (bool) $today,
            'sudah_pulang' => $today ? (bool) $today->jam_keluar : false,
            'data' => $today,
        ];
        $data['lokasi'] = $lokasi;
        $data['durasi_kerja'] = null;
        $data['keterlambatan'] = null;

        if ($today && $today->jam_keluar && $lokasi) {
            $stats = PresensiService::calculateWorkStats(
                $today->tanggal_masuk,
                $today->jam_masuk,
                $today->tanggal_keluar ?: $today->tanggal_masuk,
                $today->jam_keluar,
                $lokasi->jam_masuk ?? '07:00:00'
            );
            $data['durasi_kerja'] = $stats['jam_kerja'];
            $data['keterlambatan'] = $stats['keterlambatan'];
        }

        return $this->view('routes/overview/index', $data);
    }

    private function greeting(): string
    {
        $hour = (int) date('G');
        if ($hour < 11) {
            return 'Selamat pagi';
        }
        if ($hour < 15) {
            return 'Selamat siang';
        }
        if ($hour < 18) {
            return 'Selamat sore';
        }

        return 'Selamat malam';
    }

    private function todayLabel(): string
    {
        $days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        $w = (int) date('w');
        $d = (int) date('j');
        $m = (int) date('n');
        $y = date('Y');

        return $days[$w] . ', ' . $d . ' ' . $months[$m] . ' ' . $y;
    }

    public function health()
    {
        return 'OK';
    }
}
