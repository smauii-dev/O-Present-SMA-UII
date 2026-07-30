<?php

namespace App\Controllers\Web;

use App\Controllers\BaseController;

class Rekap extends BaseController
{
    /**
     * Rekap page — views/routes/rekap/rekap.twig
     */
    public function index()
    {
        $user = $this->getAuthUser();
        if (!$user) {
            return redirect()->to('/login');
        }

        $dari = $this->request->getGet('dari') ?: date('Y-m-d', strtotime('-30 days'));
        $sampai = $this->request->getGet('sampai') ?: date('Y-m-d');

        return $this->view('routes/rekap/rekap', [
            'pageTitle' => 'Rekap Presensi',
            'tanggal_dari' => $dari,
            'tanggal_sampai' => $sampai,
        ]);
    }

    /**
     * HTMX fragment — views/routes/rekap/rekap_fragment.twig
     */
    public function fragment()
    {
        $user = $this->getAuthUser();
        if (!$user) {
            return $this->response->setStatusCode(401)->setBody('Unauthorized');
        }

        $dari = $this->request->getGet('dari') ?: date('Y-m-d', strtotime('-30 days'));
        $sampai = $this->request->getGet('sampai') ?: date('Y-m-d');

        $model = model('PresensiModel');
        $rows = $model->where('id_pegawai', $user->id_pegawai)
            ->where('tanggal_masuk >=', $dari)
            ->where('tanggal_masuk <=', $sampai)
            ->orderBy('tanggal_masuk', 'DESC')
            ->get()
            ->getResult();

        $lokasiModel = model('LokasiPresensiModel');
        $lokasi = $lokasiModel->where('id', $user->id_lokasi_presensi)->get()->getFirstRow();
        $jamMasukKantor = $lokasi->jam_masuk ?? null;

        $rekap = [];
        foreach ($rows as $row) {
            $item = (object) [
                'tanggal' => $row->tanggal_masuk,
                'jam_masuk' => $row->jam_masuk ? substr((string) $row->jam_masuk, 0, 5) : null,
                'jam_keluar' => $row->jam_keluar ? substr((string) $row->jam_keluar, 0, 5) : null,
                'durasi_kerja' => null,
                'keterlambatan' => null,
            ];

            if ($row->jam_masuk && $row->jam_keluar) {
                $stats = \App\Services\PresensiService::calculateWorkStats(
                    $row->tanggal_masuk,
                    $row->jam_masuk,
                    $row->tanggal_keluar ?: $row->tanggal_masuk,
                    $row->jam_keluar,
                    $jamMasukKantor ?? '07:00:00'
                );
                $item->durasi_kerja = $stats['jam_kerja'];
                $item->keterlambatan = $stats['keterlambatan'];
            }

            $rekap[] = $item;
        }

        return $this->view('routes/rekap/rekap_fragment', [
            'rekap' => $rekap,
        ]);
    }

    public function export()
    {
        return redirect()->to('/rekap')->with('info', 'Export akan tersedia segera');
    }
}
