<?php

namespace App\Controllers\Api\Admin;

use App\Controllers\Api\ApiBaseController;
use App\Models\LokasiPresensiModel;
use App\Services\LokasiService;
use App\Services\ExportService;

class LokasiPresensi extends ApiBaseController
{
    protected LokasiPresensiModel $lokasiModel;
    protected ExportService $exportService;

    public function __construct()
    {
        $this->lokasiModel = new LokasiPresensiModel();
        $this->exportService = new ExportService();
    }

    public function index()
    {
        $adminCheck = $this->requireAdmin();
        if ($adminCheck) return $adminCheck;

        $page = (int) ($this->getInput('page', 1));
        $perPage = 20;
        $search = $this->getInput('search');

        if ($search) {
            $builder = $this->db->table('lokasi_presensi');
            $builder->like('nama_lokasi', $search);
            $builder->orderBy('nama_lokasi', 'ASC');
            $allData = $builder->get()->getResultArray();
        } else {
            $data = $this->lokasiModel->getLokasi();
            $allData = $data['lokasi'] ?? [];
        }

        $total = count($allData);

        return $this->paginated($allData, $total, $page, $perPage);
    }

    public function detail($id = null)
    {
        $adminCheck = $this->requireAdmin();
        if ($adminCheck) return $adminCheck;

        if (!$id) {
            return $this->error('ID lokasi wajib diisi', 422);
        }

        $data = $this->lokasiModel->getWhere(['id' => $id])->getFirstRow();
        if (!$data) {
            return $this->error('Lokasi tidak ditemukan', 404);
        }

        return $this->success($data);
    }

    public function store()
    {
        $adminCheck = $this->requireAdmin();
        if ($adminCheck) {
            return $adminCheck;
        }

        $service  = new LokasiService($this->lokasiModel);
        $prepared = $service->preparePayload($this->getAllInput());

        if (! $prepared['ok']) {
            return $this->error('Validasi gagal', 422, $prepared['errors']);
        }

        try {
            $id = $service->save($prepared['data']);
        } catch (\Throwable $e) {
            log_message('error', 'API lokasi store: ' . $e->getMessage());

            return $this->error('Nama atau koordinat bentrok / gagal menyimpan', 409);
        }

        return $this->success([
            'id'          => $id,
            'nama_lokasi' => $prepared['data']['nama_lokasi'],
            'slug'        => $prepared['data']['slug'],
            'latitude'    => $prepared['data']['latitude'],
            'longitude'   => $prepared['data']['longitude'],
        ], 'Lokasi berhasil ditambahkan', 201);
    }

    public function update($id = null)
    {
        $adminCheck = $this->requireAdmin();
        if ($adminCheck) {
            return $adminCheck;
        }

        if (! $id) {
            return $this->error('ID lokasi wajib diisi', 422);
        }

        $existing = $this->lokasiModel->find($id);
        if (! $existing) {
            return $this->error('Lokasi tidak ditemukan', 404);
        }

        $service  = new LokasiService($this->lokasiModel);
        $prepared = $service->preparePayload($this->getAllInput(), (int) $id);

        if (! $prepared['ok']) {
            return $this->error('Validasi gagal', 422, $prepared['errors']);
        }

        try {
            $service->save($prepared['data'], (int) $id);
        } catch (\Throwable $e) {
            log_message('error', 'API lokasi update: ' . $e->getMessage());

            return $this->error('Nama atau koordinat bentrok / gagal menyimpan', 409);
        }

        return $this->success(null, 'Lokasi berhasil diperbarui');
    }

    public function delete($id = null)
    {
        $adminCheck = $this->requireAdmin();
        if ($adminCheck) {
            return $adminCheck;
        }

        if (! $id) {
            return $this->error('ID lokasi wajib diisi', 422);
        }

        $existing = $this->lokasiModel->find($id);
        if (! $existing) {
            return $this->error('Lokasi tidak ditemukan', 404);
        }

        helper('geo');
        $slug = is_array($existing) ? ($existing['slug'] ?? '') : ($existing->slug ?? '');
        $canonicalSlugs = array_column(canonical_sma_uii_locations(), 'slug');
        if (in_array($slug, $canonicalSlugs, true)) {
            return $this->error('Lokasi resmi SMA UII tidak boleh dihapus (boleh diedit)', 409);
        }

        $pegawaiCount = model('PegawaiModel')->where('id_lokasi_presensi', $id)->countAllResults();
        if ($pegawaiCount > 0) {
            return $this->error('Masih ada ' . $pegawaiCount . ' pegawai di lokasi ini', 409);
        }

        $this->lokasiModel->delete($id);

        return $this->success(null, 'Lokasi berhasil dihapus');
    }

    public function exportExcel()
    {
        if ($err = $this->requireAdmin()) return $err;

        $filter = [
            'keyword' => $this->getInput('keyword', ''),
            'tipe' => $this->getInput('tipe', ''),
            'waktu' => $this->getInput('waktu', ''),
        ];
        $dataLokasi = $this->lokasiModel->getLokasi(false, $filter, true)['lokasi'];

        return $this->exportService->exportLokasiPresensi($dataLokasi, $filter);
    }

}