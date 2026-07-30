<?php

namespace App\Controllers\Api;

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

    // ========== USER ENDPOINTS ==========

    public function index()
    {
        $userProfile = $this->getUserOrError();
        if (is_object($userProfile) === false) return $userProfile;

        $page = (int) ($this->getInput('page', 1));
        $perPage = 10;

        $filter = $this->buildFilter();
        $data = $this->ketidakhadiranModel->getDataKetidakhadiran($userProfile->id_pegawai, $filter ?: false);
        $allData = $data['ketidakhadiran'] ?? [];
        $total = $data['total'] ?? 0;

        return $this->paginated($allData, $total, $page, $perPage);
    }

    public function today()
    {
        $userProfile = $this->getUserOrError();
        if (is_object($userProfile) === false) return $userProfile;

        $builder = db_connect()->table('ketidakhadiran');
        $builder->select('ketidakhadiran.*, pegawai.nip, pegawai.nama');
        $builder->join('pegawai', 'pegawai.id = ketidakhadiran.id_pegawai');
        $builder->where('ketidakhadiran.id_pegawai', $userProfile->id_pegawai);
        $builder->where('tanggal_mulai <=', date('Y-m-d'));
        $builder->where('tanggal_berakhir >=', date('Y-m-d'));
        $builder->where('status_pengajuan', 'APPROVED');
        $result = $builder->get()->getRow();

        return $this->success($result);
    }

    public function store()
    {
        $userProfile = $this->getUserOrError();
        if (is_object($userProfile) === false) return $userProfile;

        $rules = [
            'tipe_ketidakhadiran' => 'required|in_list[CUTI,IZIN,SAKIT]',
            'tanggal_mulai' => 'required',
            'tanggal_berakhir' => 'required',
            'deskripsi' => 'required',
        ];

        if (!$this->validate($rules)) {
            return $this->error('Validasi gagal', 422, $this->validator->getErrors());
        }

        $tipe = $this->getInput('tipe_ketidakhadiran');
        $status = ($tipe === 'SAKIT') ? 'APPROVED' : 'PENDING';

        $this->ketidakhadiranModel->save([
            'id_pegawai' => $userProfile->id_pegawai,
            'tipe_ketidakhadiran' => $tipe,
            'tanggal_mulai' => $this->getInput('tanggal_mulai'),
            'tanggal_berakhir' => $this->getInput('tanggal_berakhir'),
            'deskripsi' => $this->getInput('deskripsi'),
            'status_pengajuan' => $status,
        ]);

        return $this->success([
            'id' => $this->ketidakhadiranModel->insertID(),
        ], 'Pengajuan ketidakhadiran berhasil diajukan', 201);
    }

    public function detail($id = null)
    {
        $userProfile = $this->getUserOrError();
        if (is_object($userProfile) === false) {
            return $userProfile;
        }

        if (!$id) {
            return $this->error('ID ketidakhadiran wajib diisi', 422);
        }

        $row = $this->ketidakhadiranModel->find($id);
        if (!$row || (int) $row['id_pegawai'] !== (int) $userProfile->id_pegawai) {
            return $this->error('Data tidak ditemukan', 404);
        }

        return $this->success($row);
    }

    /**
     * User may only edit their own PENDING submissions.
     */
    public function update($id = null)
    {
        $userProfile = $this->getUserOrError();
        if (is_object($userProfile) === false) {
            return $userProfile;
        }

        if (!$id) {
            return $this->error('ID ketidakhadiran wajib diisi', 422);
        }

        $existing = $this->ketidakhadiranModel->find($id);
        if (!$existing || (int) $existing['id_pegawai'] !== (int) $userProfile->id_pegawai) {
            return $this->error('Data tidak ditemukan', 404);
        }

        if (($existing['status_pengajuan'] ?? '') !== 'PENDING') {
            return $this->error('Hanya pengajuan berstatus PENDING yang dapat diubah', 403);
        }

        $rules = [
            'tipe_ketidakhadiran' => 'required|in_list[CUTI,IZIN,SAKIT]',
            'tanggal_mulai' => 'required',
            'tanggal_berakhir' => 'required',
            'deskripsi' => 'required',
        ];

        if (!$this->validate($rules)) {
            return $this->error('Validasi gagal', 422, $this->validator->getErrors());
        }

        $tipe = $this->getInput('tipe_ketidakhadiran');
        $status = ($tipe === 'SAKIT') ? 'APPROVED' : 'PENDING';

        $this->ketidakhadiranModel->save([
            'id' => $id,
            'tipe_ketidakhadiran' => $tipe,
            'tanggal_mulai' => $this->getInput('tanggal_mulai'),
            'tanggal_berakhir' => $this->getInput('tanggal_berakhir'),
            'deskripsi' => $this->getInput('deskripsi'),
            'status_pengajuan' => $status,
        ]);

        return $this->success(null, 'Pengajuan berhasil diperbarui');
    }

    /**
     * User may only delete their own PENDING submissions.
     */
    public function delete($id = null)
    {
        $userProfile = $this->getUserOrError();
        if (is_object($userProfile) === false) {
            return $userProfile;
        }

        if (!$id) {
            return $this->error('ID ketidakhadiran wajib diisi', 422);
        }

        $existing = $this->ketidakhadiranModel->find($id);
        if (!$existing || (int) $existing['id_pegawai'] !== (int) $userProfile->id_pegawai) {
            return $this->error('Data tidak ditemukan', 404);
        }

        if (($existing['status_pengajuan'] ?? '') !== 'PENDING') {
            return $this->error('Hanya pengajuan berstatus PENDING yang dapat dihapus', 403);
        }

        $this->ketidakhadiranModel->delete($id);

        return $this->success(null, 'Pengajuan berhasil dihapus');
    }

    public function exportExcel()
    {
        $profile = $this->getUserOrError();
        if (!$profile) return $this->error('User profile not found', 404);

        $filter = [
            'keyword' => $this->getInput('keyword', ''),
            'bulan' => $this->getInput('bulan', '') ?: date('m'),
            'tahun' => $this->getInput('tahun', '') ?: date('Y'),
            'status' => $this->getInput('status', ''),
            'tipe' => $this->getInput('tipe', ''),
        ];
        $data = $this->ketidakhadiranModel->getDataKetidakhadiran($profile->id_pegawai, $filter, true)['ketidakhadiran'];

        return $this->exportService->exportKetidakhadiran($data, [
            'nama' => $profile->nama,
            'nip' => $profile->nip,
            'username' => $profile->username,
        ], $filter);
    }

    // ========== ADMIN ENDPOINTS ==========

    public function adminIndex()
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

    public function adminExportExcel()
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

    // ========== SHARED HELPERS ==========

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