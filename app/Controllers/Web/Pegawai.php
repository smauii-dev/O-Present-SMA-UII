<?php

namespace App\Controllers\Web;

use App\Controllers\BaseController;

class Pegawai extends BaseController
{
    public function index()
    {

        return $this->view('routes/pegawai/index', [
            'pageTitle' => 'Data Pegawai',
            'jabatanList' => model('JabatanModel')->get()->getResult(),
            'lokasiList' => model('LokasiPresensiModel')->get()->getResult(),
            'roles' => model('Myth\Auth\Models\GroupModel')->get()->getResult(),
        ]);
    }

    public function fragment()
    {

        $search = $this->request->getGet('search');
        $model = model('PegawaiModel');

        $builder = $model->builder();
        $builder->select('pegawai.*, jabatan.jabatan, lokasi_presensi.nama_lokasi');
        $builder->join('jabatan', 'jabatan.id = pegawai.id_jabatan', 'left');
        $builder->join('lokasi_presensi', 'lokasi_presensi.id = pegawai.id_lokasi_presensi', 'left');

        if ($search) {
            $builder->like('pegawai.nama', $search);
            $builder->orLike('pegawai.nip', $search);
            $builder->orLike('jabatan.jabatan', $search);
        }

        $builder->orderBy('pegawai.nama', 'ASC');

        $perPage = 20;
        $page = max(1, (int) $this->request->getGet('page'));
        $total = $builder->countAllResults(false);
        $pegawai = $builder->limit($perPage, ($page - 1) * $perPage)->get()->getResult();

        $photoService = new \App\Services\PhotoService();
        foreach ($pegawai as &$p) {
            $p->foto_url = (!empty($p->foto) && $p->foto !== 'default.jpg') ? $photoService->getProfilePhotoUrl($p->foto) : base_url('images/default-avatar.svg');
        }

        return $this->view('routes/pegawai/fragment', [
            'pegawai' => $pegawai,
            'totalHalaman' => (int) ceil($total / $perPage),
            'halaman' => $page,
            'search' => $search,
        ]);
    }

    public function form($id = null)
    {

        $data = ['pegawai' => null];
        if ($id) {
            $pegawaiModel = model('PegawaiModel');
            $res = $pegawaiModel->getPegawai($id);
            if (!$res || empty($res['pegawai'])) {
                return $this->response->setStatusCode(404)->setBody('Data tidak ditemukan');
            }
            $data['pegawai'] = $res['pegawai'];
        }

        $data['jabatanList'] = model('JabatanModel')->get()->getResult();
        $data['lokasiList'] = model('LokasiPresensiModel')->get()->getResult();
        $data['roles'] = model('Myth\Auth\Models\GroupModel')->get()->getResult();

        return $this->view('routes/pegawai/form_modal', $data);
    }

    public function importForm()
    {
        return $this->view('routes/pegawai/import_modal');
    }

    public function save($id = null)
    {

        $input = $this->request->getPost();
        $pegawaiModel = model('PegawaiModel');
        $usersModel = model('UsersModel');
        $groupModel = new \Myth\Auth\Models\GroupModel();

        // Very basic validation (should use proper validation rules)
        if (empty($input['nama'])) {
            return $this->response->setStatusCode(422)
                ->setHeader('HX-Trigger', json_encode(['toast' => ['type' => 'error', 'message' => 'Nama wajib diisi']]))
                ->setBody('Validasi Gagal');
        }

        $db = \Config\Database::connect();
        $db->transStart();

        if ($id) {
            // Update
            $res = $pegawaiModel->getPegawai($id);
            if (!$res || empty($res['pegawai'])) {
                return $this->response->setStatusCode(404)->setBody('Not found');
            }
            $pegawai = $res['pegawai'];

            $pegawaiModel->save([
                'id' => $id,
                'nama' => $input['nama'],
                'jenis_kelamin' => $input['jenis_kelamin'],
                'alamat' => $input['alamat'],
                'no_handphone' => $input['no_handphone'],
                'id_jabatan' => $input['id_jabatan'],
                'id_lokasi_presensi' => $input['id_lokasi_presensi'],
            ]);

            $usersModel->save([
                'id' => $pegawai->user_id,
                'email' => $input['email'] ?: null,
            ]);

            $db->transComplete();

            if ($db->transStatus() === false) {
                return $this->response->setStatusCode(500)
                    ->setHeader('HX-Trigger', json_encode(['toast' => ['type' => 'error', 'message' => 'Gagal memperbarui data']]))
                    ->setBody('Error');
            }

            return $this->response->setHeader('HX-Trigger', json_encode([
                'toast' => ['type' => 'success', 'message' => 'Pegawai berhasil diupdate'],
                'reloadPegawaiTable' => true,
                'close-modal' => true
            ]))->setBody('');
        } else {
            // Create
            if (empty($input['email']) || empty($input['role'])) {
                return $this->response->setStatusCode(422)
                    ->setHeader('HX-Trigger', json_encode(['toast' => ['type' => 'error', 'message' => 'Email dan Role wajib diisi']]))
                    ->setBody('Validasi Gagal');
            }

            $nipBaru = $pegawaiModel->generateNIP();
            $passwordHash = $usersModel->hashPassword('123456');

            // extract username from email
            $username = explode('@', $input['email'])[0];

            $pegawaiModel->save([
                'nip' => $nipBaru,
                'nama' => $input['nama'],
                'jenis_kelamin' => $input['jenis_kelamin'],
                'alamat' => $input['alamat'],
                'no_handphone' => $input['no_handphone'],
                'id_jabatan' => $input['id_jabatan'],
                'id_lokasi_presensi' => $input['id_lokasi_presensi'],
                'foto' => 'default.jpg',
            ]);
            $idPegawai = $pegawaiModel->insertID();

            $usersModel->save([
                'id_pegawai' => $idPegawai,
                'email' => $input['email'],
                'username' => $username . time(), // ensure unique
                'password_hash' => $passwordHash,
                'active' => 1,
            ]);
            $userId = $usersModel->insertID();

            $groupModel->addUserToGroup($userId, (int) $input['role']);

            $db->transComplete();

            if ($db->transStatus() === false) {
                return $this->response->setStatusCode(500)
                    ->setHeader('HX-Trigger', json_encode(['toast' => ['type' => 'error', 'message' => 'Gagal menambahkan pegawai']]))
                    ->setBody('Error');
            }

            return $this->response->setHeader('HX-Trigger', json_encode([
                'toast' => ['type' => 'success', 'message' => 'Pegawai berhasil ditambahkan'],
                'reloadPegawaiTable' => true,
                'close-modal' => true
            ]))->setBody('');
        }
    }

    public function delete($id = null)
    {

        if (!$id) return $this->response->setStatusCode(422)->setBody('ID dibutuhkan');

        $pegawaiModel = model('PegawaiModel');
        $data = $pegawaiModel->getPegawai($id);
        if (!$data || empty($data['pegawai'])) {
            return $this->response->setStatusCode(404)->setBody('Not found');
        }

        // The Users account will cascade delete or needs to be deleted depending on FK setup.
        // PegawaiModel delete is handled there.
        if (!empty($data['pegawai']->foto) && $data['pegawai']->foto !== 'default.jpg') {
            service('s3')->delete('profile/' . $data['pegawai']->foto);
        }

        $pegawaiModel->delete($id);

        return $this->response->setHeader('HX-Trigger', json_encode([
            'toast' => ['type' => 'success', 'message' => 'Data pegawai berhasil dihapus'],
            'reloadPegawaiTable' => true
        ]))->setBody('');
    }

    public function resetPassword($id = null)
    {
        if (!$id) return $this->response->setStatusCode(422)->setBody('ID dibutuhkan');

        $pegawaiModel = model('PegawaiModel');
        $data = $pegawaiModel->getPegawai($id);
        if (!$data || empty($data['pegawai'])) {
            return $this->response->setStatusCode(404)->setBody('Not found');
        }

        $pegawai = $data['pegawai'];
        if (empty($pegawai->user_id)) {
            return $this->response->setStatusCode(422)
                ->setHeader('HX-Trigger', json_encode(['toast' => ['type' => 'error', 'message' => 'Akun user tidak ditemukan']]))
                ->setBody('User tidak terhubung');
        }

        $usersModel = model('UsersModel');
        $usersModel->save([
            'id' => $pegawai->user_id,
            'force_pass_reset' => 1,
        ]);

        return $this->response->setHeader('HX-Trigger', json_encode([
            'toast' => ['type' => 'success', 'message' => 'Password direset. User wajib ganti password saat login berikutnya'],
            'reloadPegawaiTable' => true
        ]))->setBody('');
    }
}
