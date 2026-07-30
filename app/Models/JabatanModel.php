<?php

namespace App\Models;

use CodeIgniter\Model;

class JabatanModel extends Model
{
    protected $table = 'jabatan';
    protected $primaryKey = 'id';
    protected $allowedFields = ['jabatan', 'slug'];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    public function getJabatan($slug = false, $keyword = false, $perPage = 10)
    {
        $pager = service('pager');
        $pager->setPath('jabatan', 'jabatan');

        $request = service('request');
        $page = (int) ($request->getGet('page_jabatan') ?? 1);
        $offset = ($page - 1) * $perPage;

        $builder = $this->db->table('jabatan');
        $builder->select('jabatan.*, COUNT(pegawai.id) as total_pegawai');
        $builder->join('pegawai', 'pegawai.id_jabatan = jabatan.id', 'left');
        $builder->groupBy('jabatan.id');
        $builder->orderBy('jabatan', 'ASC');

        if ($slug) {
            $builder->where('slug', $slug);
        } elseif ($keyword) {
            $builder->like('jabatan', $keyword);
        }

        $countQuery = clone $builder;
        $total = $countQuery->countAllResults();

        if ($slug) {
            $result = $builder->get()->getRowArray();
        } else {
            $result = $builder->get($perPage, $offset)->getResult();
        }

        return [
            'jabatan' => $result,
            'links' => $pager->makeLinks($page, $perPage, $total, 'my_pagination', 0, 'jabatan'),
            'total' => $total,
            'perPage' => $perPage,
            'page' => $page,
        ];
    }
}
