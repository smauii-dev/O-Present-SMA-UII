<?php

namespace App\Models;

use CodeIgniter\Model;

class PresensiModel extends Model
{
    protected $table = 'presensi';
    protected $primaryKey = 'id';
    protected $allowedFields = ['id_pegawai', 'tanggal_masuk', 'jam_masuk', 'foto_masuk', 'tanggal_keluar', 'jam_keluar', 'foto_keluar'];
    protected $useTimestamps = true;

    public function getDataPresensi($id_pegawai, $tanggal_dari = false, $tanggal_sampai = false, $print = false, $perPage = 10)
    {
        $pager = service('pager');
        $pager->setPath('rekap-presensi', 'rekap');

        $request = service('request');
        $page = (int) ($request->getGet('page_rekap') ?? 1);
        $offset = ($page - 1) * $perPage;

        $builder = $this->db->table('presensi');
        $builder->select('presensi.*, pegawai.nip, pegawai.nama, pegawai.alamat, pegawai.id_lokasi_presensi, lokasi_presensi.nama_lokasi as lokasi_presensi, lokasi_presensi.jam_masuk as jam_masuk_kantor');
        $builder->join('pegawai', 'pegawai.id = presensi.id_pegawai');
        $builder->join('lokasi_presensi', 'lokasi_presensi.id = pegawai.id_lokasi_presensi');
        $builder->where('presensi.id_pegawai', $id_pegawai);
        $builder->orderBy('presensi.tanggal_masuk', 'DESC');

        if ($tanggal_dari && $tanggal_sampai) {
            $builder->where('tanggal_masuk >=', $tanggal_dari);
            $builder->where('tanggal_masuk <=', $tanggal_sampai);
        } elseif ($tanggal_dari) {
            $builder->where('tanggal_masuk >=', $tanggal_dari);
        } elseif ($tanggal_sampai) {
            $builder->where('tanggal_masuk <=', $tanggal_sampai);
        }

        $countQuery = clone $builder;
        $total = $countQuery->countAllResults();

        if ($print) {
            $result = $builder->get()->getResult();
        } else {
            $result = $builder->get($perPage, $offset)->getResult();
        }

        return [
            'rekap-presensi' => $result,
            'links' => $pager->makeLinks($page, $perPage, $total, 'my_pagination', 0, 'rekap'),
            'total' => $total,
            'perPage' => $perPage,
            'page' => $page,
        ];
    }

    public function cekPresensiMasuk($id_pegawai, $tanggal_hari_ini, $hitung = false)
    {
        $condition = [
            'id_pegawai' => $id_pegawai,
            'tanggal_masuk' => $tanggal_hari_ini,
        ];

        if ($hitung) {
            return $this->where($condition)->countAllResults();
        } else {
            return $this->getWhere($condition)->getFirstRow();
        }
    }

    public function getDataPresensiHarian($tanggal_dari = false, $tanggal_sampai = false, $print = false, $perPage = 10)
    {
        $pager = service('pager');
        $pager->setPath('laporan-presensi-harian', 'harian');

        $request = service('request');
        $page = (int) ($request->getGet('page_harian') ?? 1);
        $offset = ($page - 1) * $perPage;

        $builder = $this->db->table('presensi');
        $builder->select('presensi.*, pegawai.nip, pegawai.nama, pegawai.alamat, pegawai.id_lokasi_presensi, lokasi_presensi.nama_lokasi as lokasi_presensi, lokasi_presensi.jam_masuk as jam_masuk_kantor');
        $builder->join('pegawai', 'pegawai.id = presensi.id_pegawai');
        $builder->join('lokasi_presensi', 'lokasi_presensi.id = pegawai.id_lokasi_presensi');
        $builder->orderBy('tanggal_masuk', 'DESC');

        if ($tanggal_dari && $tanggal_sampai) {
            $builder->where('presensi.tanggal_masuk >=', $tanggal_dari);
            $builder->where('presensi.tanggal_masuk <=', $tanggal_sampai);
        } elseif ($tanggal_dari) {
            $builder->where('presensi.tanggal_masuk >=', $tanggal_dari);
        } elseif ($tanggal_sampai) {
            $builder->where('presensi.tanggal_masuk <=', $tanggal_sampai);
        } else {
            $builder->where('presensi.tanggal_masuk', date('Y-m-d'));
        }

        $countQuery = clone $builder;
        $total = $countQuery->countAllResults();

        if ($print) {
            $result = $builder->get()->getResult();
        } else {
            $result = $builder->get($perPage, $offset)->getResult();
        }

        return [
            'laporan-harian' => $result,
            'links' => $pager->makeLinks($page, $perPage, $total, 'my_pagination', 0, 'harian'),
            'total' => $total,
            'perPage' => $perPage,
            'page' => $page,
        ];
    }

    public function getDataPresensiBulanan($filter_bulan = false, $filter_tahun = false, $print = false, $perPage = 10)
    {
        $pager = service('pager');
        $pager->setPath('laporan-presensi-bulanan', 'bulanan');

        $request = service('request');
        $page = (int) ($request->getGet('page_bulanan') ?? 1);
        $offset = ($page - 1) * $perPage;

        $builder = $this->db->table('presensi');
        $builder->select('presensi.*, pegawai.nip, pegawai.nama, pegawai.alamat, pegawai.id_lokasi_presensi, lokasi_presensi.nama_lokasi as lokasi_presensi, lokasi_presensi.jam_masuk as jam_masuk_kantor');
        $builder->join('pegawai', 'pegawai.id = presensi.id_pegawai');
        $builder->join('lokasi_presensi', 'lokasi_presensi.id = pegawai.id_lokasi_presensi');
        $builder->orderBy('tanggal_masuk', 'DESC');

        if ($filter_bulan && $filter_tahun) {
            $bulan_filter = $filter_tahun . '-' . $filter_bulan;
            $builder->where("TO_CHAR(presensi.tanggal_masuk, 'YYYY-MM')", $bulan_filter);
        } else {
            $builder->where("TO_CHAR(presensi.tanggal_masuk, 'YYYY-MM')", date('Y-m'));
        }

        $countQuery = clone $builder;
        $total = $countQuery->countAllResults();

        if ($print) {
            $result = $builder->get()->getResult();
        } else {
            $result = $builder->get($perPage, $offset)->getResult();
        }

        return [
            'laporan-bulanan' => $result,
            'links' => $pager->makeLinks($page, $perPage, $total, 'my_pagination', 0, 'bulanan'),
            'total' => $total,
            'perPage' => $perPage,
            'page' => $page,
        ];
    }

    public function getMinYear()
    {
        $builder = $this->db->table('presensi');
        $builder->selectMin('tanggal_masuk', 'min_date');
        $result = $builder->get()->getRow();
        return $result ? date('Y', strtotime($result->min_date)) : null;
    }

    public function getMinDate($id_pegawai = false)
    {
        $builder = $this->db->table('presensi');

        if ($id_pegawai) {
            $builder->where('presensi.id_pegawai', $id_pegawai);
        }

        $builder->selectMin('tanggal_masuk', 'min_date');
        $result = $builder->get()->getRow();

        return $result ? $result->min_date : null;
    }

    public function getDataPresensiHariIni()
    {
        $builder = $this->db->table('presensi');
        $builder->where('tanggal_masuk', date('Y-m-d'));
        return $builder->countAllResults();
    }
}
