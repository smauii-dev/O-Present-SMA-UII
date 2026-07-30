<?php

namespace App\Controllers\Api;

use App\Models\PresensiModel;
use App\Models\PegawaiModel;
use App\Models\KetidakhadiranModel;
use App\Models\LokasiPresensiModel;

class Dashboard extends ApiBaseController
{
    protected PresensiModel $presensiModel;
    protected PegawaiModel $pegawaiModel;
    protected KetidakhadiranModel $ketidakhadiranModel;

    public function __construct()
    {
        $this->presensiModel = new PresensiModel();
        $this->pegawaiModel = new PegawaiModel();
        $this->ketidakhadiranModel = new KetidakhadiranModel();
        $this->lokasiModel = new LokasiPresensiModel();
    }

    public function index()
    {
        $userProfile = $this->getUserOrError();
        if (is_object($userProfile) === false) return $userProfile;

        if ($this->isAdmin()) {
            return $this->renderAdmin($userProfile);
        }

        return $this->renderPegawai($userProfile);
    }

    private function renderAdmin($userProfile)
    {
        $jumlahPegawaiAktif = $this->pegawaiModel->getJumlahPegawaiAktif();
        $jumlahPegawaiHadir = $this->presensiModel->getDataPresensiHariIni();
        $jumlahPegawaiIzin = $this->ketidakhadiranModel->getDataIzinHariIni();
        $jumlahPegawaiAlpha = max(0, $jumlahPegawaiAktif - ($jumlahPegawaiHadir + $jumlahPegawaiIzin));

        return $this->success([
            'role' => 'admin',
            'user' => [
                'nama' => $userProfile->nama,
                'nip' => $userProfile->nip,
                'foto' => $userProfile->foto,
                'foto_url' => $this->getProfilePhotoUrl($userProfile->foto),
            ],
            'stats' => [
                'pegawai_aktif' => $jumlahPegawaiAktif,
                'pegawai_hadir' => $jumlahPegawaiHadir,
                'pegawai_izin' => $jumlahPegawaiIzin,
                'pegawai_alpha' => $jumlahPegawaiAlpha,
            ],
        ]);
    }

    private function renderPegawai($userProfile)
    {
        $userLokasi = $this->lokasiModel->getWhere(['nama_lokasi' => $userProfile->lokasi_presensi])->getFirstRow();
        $presensiMasuk = $this->presensiModel->cekPresensiMasuk($userProfile->id_pegawai, date('Y-m-d'));
        $jumlahPresensiMasuk = $this->presensiModel->cekPresensiMasuk($userProfile->id_pegawai, date('Y-m-d'), true);
        $statusKetidakhadiran = $this->ketidakhadiranModel->getDataIzinHariIni($userProfile->id_pegawai);

        $presensiData = null;
        if ($presensiMasuk) {
            $presensiData = [
                'id' => $presensiMasuk->id ?? null,
                'tanggal_masuk' => $presensiMasuk->tanggal_masuk ?? null,
                'jam_masuk' => $presensiMasuk->jam_masuk ?? null,
                'tanggal_keluar' => $presensiMasuk->tanggal_keluar ?? null,
                'jam_keluar' => $presensiMasuk->jam_keluar ?? null,
                'foto_masuk' => $presensiMasuk->foto_masuk ?? null,
                'foto_masuk_url' => $this->getPresensiPhotoUrl('masuk', $presensiMasuk->foto_masuk ?? null),
                'foto_keluar' => $presensiMasuk->foto_keluar ?? null,
                'foto_keluar_url' => $this->getPresensiPhotoUrl('keluar', $presensiMasuk->foto_keluar ?? null),
            ];
        }

        return $this->success([
            'role' => 'pegawai',
            'user' => [
                'nama' => $userProfile->nama,
                'nip' => $userProfile->nip,
                'foto' => $userProfile->foto,
                'foto_url' => $this->getProfilePhotoUrl($userProfile->foto),
            ],
            'lokasi' => [
                'nama_lokasi' => $userLokasi->nama_lokasi ?? null,
                'jam_masuk' => $userLokasi->jam_masuk ?? null,
                'jam_pulang' => $userLokasi->jam_pulang ?? null,
                'radius' => $userLokasi->radius ?? null,
                'latitude' => $userLokasi->latitude ?? null,
                'longitude' => $userLokasi->longitude ?? null,
            ],
            'presensi_hari_ini' => [
                'sudah_masuk' => $jumlahPresensiMasuk > 0,
                'data' => $presensiData,
            ],
            'status_ketidakhadiran' => $statusKetidakhadiran ? [
                'id' => $statusKetidakhadiran->id ?? null,
                'jenis' => $statusKetidakhadiran->jenis ?? null,
                'status' => $statusKetidakhadiran->status ?? null,
            ] : null,
        ]);
    }
}
