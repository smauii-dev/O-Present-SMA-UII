<?php

namespace App\Models;

use CodeIgniter\Model;

class LokasiPresensiModel extends Model
{
    protected $table = 'lokasi_presensi';
    protected $primaryKey = 'id';
    protected $allowedFields = ['nama_lokasi', 'slug', 'alamat_lokasi', 'tipe_lokasi', 'latitude', 'longitude', 'radius', 'zona_waktu', 'jam_masuk', 'jam_pulang'];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    public function getLokasi($slug = false, $filter = false, $print = false, $perPage = 10)
    {
        $pager = service('pager');
        $pager->setPath('lokasi-presensi', 'lokasi-presensi');

        $request = service('request');
        $page = (int) ($request->getGet('page_lokasi-presensi') ?? 1);
        $offset = ($page - 1) * $perPage;

        $builder = $this->db->table('lokasi_presensi');
        $builder->orderBy('tipe_lokasi', 'DESC');

        if ($slug) {
            $builder->where('slug', $slug);
        } elseif ($filter) {
            if (!empty($filter['waktu'])) {
                $builder->where('zona_waktu', $filter['waktu']);
            }
            if (!empty($filter['tipe'])) {
                $builder->where('tipe_lokasi', $filter['tipe']);
            }
            if (!empty($filter['keyword'])) {
                $builder->groupStart()
                    ->like('nama_lokasi', $filter['keyword'])
                    ->orLike('alamat_lokasi', $filter['keyword'])
                    ->groupEnd();
            }
        }

        $countQuery = clone $builder;
        $total = $countQuery->countAllResults();

        if ($print) {
            $result = $builder->get()->getResult();
        } elseif ($slug) {
            $result = $builder->get()->getRowArray();
        } else {
            $result = $builder->get($perPage, $offset)->getResult();
        }

        return [
            'lokasi' => $result,
            'links' => $pager->makeLinks($page, $perPage, $total, 'my_pagination', 0, 'lokasi-presensi'),
            'total' => $total,
            'perPage' => $perPage,
            'page' => $page,
        ];
    }
}
