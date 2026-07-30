<?php

namespace App\Models;

use CodeIgniter\Model;

class KetidakhadiranModel extends Model
{
    protected $table = 'ketidakhadiran';
    protected $primaryKey = 'id';
    protected $allowedFields = ['id_pegawai', 'tipe_ketidakhadiran', 'tanggal_mulai', 'tanggal_berakhir', 'deskripsi', 'file', 'status_pengajuan'];
    protected $useTimestamps = true;

    public function getDataKetidakhadiran($id_pegawai = false, $filter = false, $print = false, $perPage = 10)
    {
        $pager = service('pager');
        $request = service('request');

        if ($id_pegawai === false) {
            $pager->setPath('kelola-ketidakhadiran', 'ketidakhadiran');
            $page = (int) ($request->getGet('page_ketidakhadiran') ?? 1);
        } else {
            $page = (int) ($request->getGet('page') ?? 1);
        }

        $offset = ($page - 1) * $perPage;

        $builder = $this->db->table('ketidakhadiran');
        $builder->select('ketidakhadiran.*, pegawai.nip, pegawai.nama');
        $builder->join('pegawai', 'pegawai.id = ketidakhadiran.id_pegawai');
        $builder->orderBy('ketidakhadiran.updated_at', 'DESC');

        if ($id_pegawai) {
            $builder->where('id_pegawai', $id_pegawai);
        }

        if ($filter) {
            if (!empty($filter['status'])) {
                $builder->where('status_pengajuan', $filter['status']);
            }
            if (!empty($filter['tipe'])) {
                $builder->where('tipe_ketidakhadiran', $filter['tipe']);
            }
            if (!empty($filter['keyword'])) {
                if ($id_pegawai) {
                    $builder->like('deskripsi', $filter['keyword']);
                } else {
                    $builder->groupStart()
                        ->like('nama', $filter['keyword'])
                        ->orLike('deskripsi', $filter['keyword'])
                        ->groupEnd();
                }
            }
            if (!empty($filter['bulan']) && !empty($filter['tahun'])) {
                $bulan_filter = $filter['tahun'] . '-' . $filter['bulan'];
                $builder->groupStart()
                    ->where("TO_CHAR(tanggal_mulai, 'YYYY-MM')", $bulan_filter)
                    ->orWhere("TO_CHAR(tanggal_berakhir, 'YYYY-MM')", $bulan_filter)
                    ->groupEnd();
            }
        }

        $countQuery = clone $builder;
        $total = $countQuery->countAllResults();

        if ($print) {
            $result = $builder->get()->getResult();
        } else {
            $result = $builder->get($perPage, $offset)->getResult();
        }

        $group = ($id_pegawai === false) ? 'ketidakhadiran' : '';

        return [
            'ketidakhadiran' => $result,
            'links' => $pager->makeLinks($page, $perPage, $total, 'my_pagination', 0, $group),
            'total' => $total,
            'perPage' => $perPage,
            'page' => $page,
        ];
    }

    public function findDataKetidakhadiran($id)
    {
        $builder = $this->db->table('ketidakhadiran');
        $builder->select('ketidakhadiran.*, pegawai.nip, pegawai.nama');
        $builder->join('pegawai', 'pegawai.id = ketidakhadiran.id_pegawai');
        $builder->where('ketidakhadiran.id', $id);
        $builder->orderBy('ketidakhadiran.updated_at', 'DESC');
        return $builder->get()->getRow();
    }

    public function getDataIzinHariIni($id_pegawai = false, $startDate = false, $endDate = false)
    {
        $builder = $this->db->table('ketidakhadiran');

        if ($id_pegawai) {
            $builder->where('id_pegawai', $id_pegawai);
        }

        if ($startDate && $endDate) {
            $builder->where('tanggal_mulai <=', $startDate)
                ->where('tanggal_berakhir >=', $endDate)
                ->where('status_pengajuan', 'APPROVED');
        } else {
            $builder->where('tanggal_mulai <=', date('Y-m-d'))
                ->where('tanggal_berakhir >=', date('Y-m-d'))
                ->where('status_pengajuan', 'APPROVED');
        }

        return $builder->countAllResults();
    }

    public function getMinYear()
    {
        $builder = $this->db->table('ketidakhadiran');
        $builder->selectMin('tanggal_mulai', 'min_date');
        $result = $builder->get()->getRow();
        return $result && $result->min_date ? date('Y', strtotime($result->min_date)) : null;
    }

    public function checkAndUpdateStatus(): void
    {
        $builder = $this->db->table('ketidakhadiran');
        $builder->where('tanggal_mulai <', date('Y-m-d'));
        $builder->where('status_pengajuan', 'PENDING');
        $builder->update(['status_pengajuan' => 'REJECTED']);
    }
}
