<?php

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Models\PresensiModel;
use App\Models\LokasiPresensiModel;
use App\Services\ExportService;

class AttendanceSummary extends BaseController
{
    protected ExportService $exportService;

    public function __construct()
    {
        $this->exportService = new ExportService();
    }

    /**
     * Rekap page — views/routes/attendance-summary/index.twig
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
            'pageTitle' => 'Attendance Summary',
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
                $masuk = strtotime($row->tanggal_masuk . ' ' . $row->jam_masuk);
                $keluar = strtotime(($row->tanggal_keluar ?: $row->tanggal_masuk) . ' ' . $row->jam_keluar);
                $diff = max(0, $keluar - $masuk);
                $jam = (int) floor($diff / 3600);
                $menit = (int) floor(($diff % 3600) / 60);
                $item->durasi_kerja = sprintf('%d jam %d menit', $jam, $menit);
            }

            if ($jamMasukKantor && $row->jam_masuk && $row->jam_masuk > $jamMasukKantor) {
                $telat = strtotime((string) $row->jam_masuk) - strtotime((string) $jamMasukKantor);
                $tJam = (int) floor($telat / 3600);
                $tMenit = (int) floor(($telat % 3600) / 60);
                $item->keterlambatan = $tJam > 0
                    ? sprintf('%d jam %d menit', $tJam, $tMenit)
                    : sprintf('%d menit', $tMenit);
            }

            $rekap[] = $item;
        }

        return $this->view('routes/rekap/rekap_fragment', [
            'rekap' => $rekap,
        ]);
    }

    public function export()
    {
        $user = $this->getAuthUser();
        if (!$user) {
            return redirect()->to('/login');
        }

        $dari = $this->request->getPost('dari') ?: date('Y-m-d', strtotime('-30 days'));
        $sampai = $this->request->getPost('sampai') ?: date('Y-m-d');

        $model = model('PresensiModel');
        $data = $model->getDataPresensi($user->id_pegawai, $dari, $sampai, true)['rekap-presensi'];

        // Enrich with computed fields
        $lokasiModel = model('LokasiPresensiModel');
        $lokasi = $lokasiModel->where('id', $user->id_lokasi_presensi)->get()->getFirstRow();
        $jamMasukKantor = $lokasi->jam_masuk ?? '07:00:00';

        $enrichedData = array_map(function ($row) use ($jamMasukKantor) {
            $item = (array) $row;
            $item['_nomor'] = 1; // Will be overridden by export service

            // Calculate work duration
            if (!empty($item['jam_masuk']) && !empty($item['jam_keluar'])) {
                $masuk = strtotime($item['tanggal_masuk'] . ' ' . $item['jam_masuk']);
                $keluar = strtotime(($item['tanggal_keluar'] ?: $item['tanggal_masuk']) . ' ' . $item['jam_keluar']);
                $diff = max(0, $keluar - $masuk);
                $jam = (int) floor($diff / 3600);
                $menit = (int) floor(($diff % 3600) / 60);
                $item['jam_kerja'] = sprintf('%d Jam %d Menit', $jam, $menit);
            } else {
                $item['jam_kerja'] = '-';
            }

            // Calculate lateness
            if (!empty($item['jam_masuk']) && $item['jam_masuk'] > $jamMasukKantor) {
                $telat = strtotime($item['jam_masuk']) - strtotime($jamMasukKantor);
                $tJam = (int) floor($telat / 3600);
                $tMenit = (int) floor(($telat % 3600) / 60);
                $item['keterlambatan'] = $tJam > 0
                    ? sprintf('%d Jam %d Menit', $tJam, $tMenit)
                    : sprintf('%d Menit', $tMenit);
            } else {
                $item['keterlambatan'] = 'On Time';
            }

            return $item;
        }, $data);

        return $this->exportService->exportPresensiRekap(
            $enrichedData,
            ['nama' => $user->nama, 'nip' => $user->nip],
            $dari,
            $sampai
        );
    }
}