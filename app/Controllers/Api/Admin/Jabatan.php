<?php

namespace App\Controllers\Api\Admin;

use App\Controllers\Api\ApiBaseController;
use App\Models\JabatanModel;

class Jabatan extends ApiBaseController
{
    protected JabatanModel $jabatanModel;

    public function __construct()
    {
        $this->jabatanModel = new JabatanModel();
    }

    public function index()
    {
        $adminCheck = $this->requireAdmin();
        if ($adminCheck) return $adminCheck;

        $page = (int) ($this->getInput('page', 1));
        $perPage = 20;
        $search = $this->getInput('search');

        if ($search) {
            $builder = $this->db->table('jabatan');
            $builder->select('jabatan.*, COUNT(pegawai.id) as total_pegawai');
            $builder->join('pegawai', 'pegawai.id_jabatan = jabatan.id', 'left');
            $builder->groupBy('jabatan.id');
            $builder->like('jabatan.jabatan', $search);
            $builder->orderBy('jabatan.jabatan', 'ASC');
            $allData = $builder->get()->getResultArray();
        } else {
            $data = $this->jabatanModel->getJabatan();
            $allData = $data['jabatan'] ?? [];
        }

        $total = count($allData);

        return $this->paginated($allData, $total, $page, $perPage);
    }

    public function detail($id = null)
    {
        $adminCheck = $this->requireAdmin();
        if ($adminCheck) return $adminCheck;

        if (!$id) {
            return $this->error('ID jabatan wajib diisi', 422);
        }

        $data = $this->jabatanModel->find($id);
        if (!$data) {
            return $this->error('Jabatan tidak ditemukan', 404);
        }

        return $this->success($data);
    }

    public function store()
    {
        $adminCheck = $this->requireAdmin();
        if ($adminCheck) return $adminCheck;

        $rules = [
            'jabatan' => 'required|is_unique[jabatan.jabatan]',
        ];

        if (!$this->validate($rules)) {
            return $this->error('Validasi gagal', 422, $this->validator->getErrors());
        }

        $jabatan = $this->getInput('jabatan');
        $slug = url_title($jabatan, '-', true);

        $this->jabatanModel->save([
            'jabatan' => $jabatan,
            'slug' => $slug,
        ]);

        return $this->success([
            'id' => $this->jabatanModel->insertID(),
            'jabatan' => $jabatan,
            'slug' => $slug,
        ], 'Jabatan berhasil ditambahkan', 201);
    }

    public function update($id = null)
    {
        $adminCheck = $this->requireAdmin();
        if ($adminCheck) return $adminCheck;

        if (!$id) {
            return $this->error('ID jabatan wajib diisi', 422);
        }

        $existing = $this->jabatanModel->find($id);
        if (!$existing) {
            return $this->error('Jabatan tidak ditemukan', 404);
        }

        $jabatanInput = $this->getInput('jabatan');

        if ($jabatanInput && $jabatanInput !== $existing['jabatan']) {
            $rules = ['jabatan' => 'required|is_unique[jabatan.jabatan]'];
        } else {
            $rules = ['jabatan' => 'required'];
        }

        if (!$this->validate($rules)) {
            return $this->error('Validasi gagal', 422, $this->validator->getErrors());
        }

        $slug = url_title($jabatanInput, '-', true);

        $this->jabatanModel->save([
            'id' => $id,
            'jabatan' => $jabatanInput,
            'slug' => $slug,
        ]);

        return $this->success(null, 'Jabatan berhasil diperbarui');
    }

    public function delete($id = null)
    {
        $adminCheck = $this->requireAdmin();
        if ($adminCheck) return $adminCheck;

        if (!$id) {
            return $this->error('ID jabatan wajib diisi', 422);
        }

        $existing = $this->jabatanModel->find($id);
        if (!$existing) {
            return $this->error('Jabatan tidak ditemukan', 404);
        }

        $this->jabatanModel->delete($id);

        return $this->success(null, 'Jabatan berhasil dihapus');
    }
}
