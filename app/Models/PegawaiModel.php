<?php

namespace App\Models;

use CodeIgniter\Model;

class PegawaiModel extends Model
{
    protected $table = 'pegawai';
    protected $primaryKey = 'id';
    protected $allowedFields = ['nip', 'nama', 'jenis_kelamin', 'alamat', 'no_handphone', 'id_jabatan', 'id_lokasi_presensi', 'foto'];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    public function getPegawai($username = false, $filter = false, $print = false, $perPage = 10)
    {
        $pager = service('pager');
        $pager->setPath('data-pegawai', 'pegawai');

        $request = service('request');
        $page = (int) ($request->getGet('page_pegawai') ?? 1);
        $offset = ($page - 1) * $perPage;

        $builder = $this->db->table('pegawai');
        $builder->select('pegawai.*, users.id as id_user, users.id_pegawai, users.username, users.active, users.email, auth_groups.name as role, auth_groups.id as role_id, jabatan.jabatan, lokasi_presensi.nama_lokasi as lokasi_presensi');
        $builder->join('users', 'users.id_pegawai = pegawai.id');
        $builder->join('auth_groups_users', 'auth_groups_users.user_id = users.id');
        $builder->join('auth_groups', 'auth_groups.id = auth_groups_users.group_id');
        $builder->join('jabatan', 'jabatan.id = pegawai.id_jabatan');
        $builder->join('lokasi_presensi', 'lokasi_presensi.id = pegawai.id_lokasi_presensi');
        $builder->orderBy('nip', 'ASC');

        // $username accepts numeric pegawai.id or string username (legacy).
        if ($username !== false && $username !== null && $username !== '') {
            if (is_numeric($username)) {
                $builder->where('pegawai.id', (int) $username);
            } else {
                $builder->where('users.username', (string) $username);
            }
        } elseif ($filter) {
            if (!empty($filter['role'])) {
                $builder->where('auth_groups.name', $filter['role']);
            }
            if (isset($filter['status']) && ($filter['status'] == 0 || $filter['status'] == 1)) {
                $builder->where('users.active', $filter['status']);
            }
            if (!empty($filter['jenis-kelamin'])) {
                $builder->where('jenis_kelamin', $filter['jenis-kelamin']);
            }
            if (!empty($filter['jabatan'])) {
                $builder->where('jabatan', $filter['jabatan']);
            }
            if (!empty($filter['lokasi-presensi'])) {
                $builder->where('lokasi_presensi.nama_lokasi', $filter['lokasi-presensi']);
            }
            if (!empty($filter['keyword'])) {
                $builder->groupStart()
                    ->like('nama', $filter['keyword'])
                    ->orLike('users.username', $filter['keyword'])
                    ->orLike('users.email', $filter['keyword'])
                    ->groupEnd();
            }
        }

        $countQuery = clone $builder;
        $total = $countQuery->countAllResults();

        if ($print) {
            $result = $builder->get()->getResult();
        } elseif ($username) {
            $result = $builder->get()->getRow();
        } else {
            $result = $builder->get($perPage, $offset)->getResult();
        }

        $data = [
            'pegawai' => $result,
            'total' => $total,
            'perPage' => $perPage,
            'page' => $page,
        ];

        // Only add pagination links when listing (not when fetching single record)
        if (!$username && !$print) {
            $data['links'] = $pager->makeLinks($page, $perPage, $total, 'my_pagination');
        }

        return $data;
    }

    public function getNIPPegawai()
    {
        $builder = $this->db->table('pegawai');
        $builder->selectMax('nip', 'latest_nip');
        $result = $builder->get()->getRow();
        return $result->latest_nip ?? null;
    }

    public function generateNIP(): string
    {
        $latest = $this->getNIPPegawai();
        if ($latest) {
            $parts = explode('-', $latest);
            $number = (int) ($parts[1] ?? 0) + 1;
        } else {
            $number = 1;
        }
        return 'PEG-' . str_pad($number, 4, '0', STR_PAD_LEFT);
    }

    public function getJumlahPegawaiAktif(): int
    {
        $builder = $this->db->table('pegawai');
        $builder->join('users', 'users.id_pegawai = pegawai.id');
        $builder->join('auth_groups_users', 'auth_groups_users.user_id = users.id');
        $builder->join('auth_groups', 'auth_groups.id = auth_groups_users.group_id');
        $builder->where('users.active', 1);
        $builder->where('auth_groups.name', 'pegawai');
        return $builder->countAllResults();
    }
}
