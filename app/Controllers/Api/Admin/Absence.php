<?php

namespace App\Controllers\Api\Admin;

use App\Controllers\Api\ApiBaseController;
use App\Models\KetidakhadiranModel;
use App\Services\ExportService;

class Absence extends ApiBaseController
{
    protected KetidakhadiranModel $ketidakhadiranModel;
    protected ExportService $exportService;

    public function __construct()
    {
        $this->ketidakhadiranModel = new KetidakhadiranModel();
        $this->exportService = new ExportService();
    }

    public function index()
    {
        $adminCheck = $this->requireAdmin();
        if ($adminCheck) return $adminCheck;

        $page = (int) ($this->getInput('page', 1));
        $perPage = 20;

        $filter = $this->buildFilter();
        $data = $this->ketidakhadiranModel->getDataKetidakhadiran(null, $filter ?: false);
        $allData = $data['ketidakhadiran'] ?? [];
        $total = $data['total'] ?? 0;

        return $this->paginated($allData, $total, $page, $perPage);
    }

    public function updateStatus($id = null)
    {
        $adminCheck = $this->requireAdmin();
        if ($adminCheck) return $adminCheck;

        if (!$id) {
            return $this->error('ID ketidakhadiran wajib diisi', 422);
        }

        $rules = [
            'status_pengajuan' => 'required|in_list[PENDING,APPROVED,REJECTED]',
        ];

        if (!$this->validate($rules)) {
            return $this->error('Validasi gagal', 422, $this->validator->getErrors());
        }

        $existing = $this->ketidakhadiranModel->find($id);
        if (!$existing) {
            return $this->error('Data ketidakhadiran tidak ditemukan', 404);
        }

        $this->ketidakhadiranModel->save([
            'id' => $id,
            'status_pengajuan' => $this->getInput('status_pengajuan'),
        ]);

        return $this->success(null, 'Status pengajuan ketidakhadiran berhasil diupdate');
    }

    public function exportExcel()
    {
        if ($err = $this->requireAdmin()) return $err;

        $filter = [
            'keyword' => $this->getInput('keyword', ''),
            'bulan' => $this->getInput('bulan', '') ?: date('m'),
            'tahun' => $this->getInput('tahun', '') ?: date('Y'),
            'status' => $this->getInput('status', ''),
            'tipe' => $this->getInput('tipe', ''),
        ];
        $data = $this->ketidakhadiranModel->getDataKetidakhadiran(false, $filter, true)['ketidakhadiran'];

        return $this->exportService->exportKetidakhadiranAdmin($data, $filter);
    }

    private function buildFilter(): ?array
    {
        $keyword = $this->getInput('keyword');
        $status = $this->getInput('status');
        $tipe = $this->getInput('tipe');
        $bulan = $this->getInput('bulan');
        $tahun = $this->getInput('tahun');

        if (!$keyword && !$status && !$tipe && !$bulan && !$tahun) {
            return null;
        }

        return [
            'keyword' => $keyword ?: null,
            'bulan' => $bulan ?: null,
            'tahun' => $tahun ?: null,
            'status' => $status ?: null,
            'tipe' => $tipe ?: null,
        ];
    }
}
