<?php

namespace App\Controllers\Web;

use App\Controllers\BaseController;

class Absence extends BaseController
{
    public function index()
    {

        return $this->view('routes/absensi/index', ['pageTitle' => 'Ketidakhadiran']);
    }

    public function fragment()
    {

        $user = $this->getAuthUser();

        $model = model('KetidakhadiranModel');
        $ketidakhadiran = $model->where('id_pegawai', $user->id_pegawai)
            ->orderBy('created_at', 'DESC')
            ->get()
            ->getResult();

        return $this->view('routes/absensi/fragment', [
            'ketidakhadiran' => $ketidakhadiran,
            'isAdmin' => false,
        ]);
    }

    public function admin()
    {

        return $this->view('routes/absensi/admin', ['pageTitle' => 'Review Ketidakhadiran']);
    }

    public function adminFragment()
    {

        $status = $this->request->getGet('status');

        $model = model('KetidakhadiranModel');
        $builder = $model->builder();
        $builder->select('ketidakhadiran.*, pegawai.nama');
        $builder->join('pegawai', 'pegawai.id = ketidakhadiran.id_pegawai', 'left');

        if ($status) {
            $builder->where('status_pengajuan', $status);
        }

        $ketidakhadiran = $builder->orderBy('created_at', 'DESC')->get()->getResult();

        return $this->view('routes/absensi/fragment', [
            'ketidakhadiran' => $ketidakhadiran,
            'isAdmin' => true,
        ]);
    }

    public function form($id = null)
    {

        $userProfile = $this->getAuthUser();

        $data = ['ketidakhadiran' => null];
        if ($id) {
            $data['ketidakhadiran'] = model('KetidakhadiranModel')->find($id);
            if (!$data['ketidakhadiran'] || (int)$data['ketidakhadiran']['id_pegawai'] !== (int)$userProfile->id_pegawai) {
                return $this->response->setStatusCode(404)->setBody('Data tidak ditemukan');
            }
            if (!empty($data['ketidakhadiran']['file'])) {
                $photoService = new \App\Services\PhotoService();
                $data['ketidakhadiran']['file_url'] = $photoService->getPresensiPhotoUrl('bukti', $data['ketidakhadiran']['file']);
            }
            if ($data['ketidakhadiran']['status_pengajuan'] !== 'PENDING') {
                return $this->response->setStatusCode(403)->setBody('Hanya pengajuan PENDING yang dapat diedit');
            }
        }

        return $this->view('routes/absensi/form_modal', $data);
    }

    public function save($id = null)
    {

        $userProfile = $this->getAuthUser();
        $input = $this->request->getPost();
        $file = $this->request->getFile('bukti');
        
        if (empty($input['tipe_ketidakhadiran']) || empty($input['tanggal_mulai']) || empty($input['tanggal_berakhir']) || empty($input['deskripsi'])) {
            return $this->response->setStatusCode(422)
                ->setHeader('HX-Trigger', json_encode(['toast' => ['type' => 'error', 'message' => 'Semua bidang wajib diisi']]))
                ->setBody('Validasi gagal');
        }

        $model = model('KetidakhadiranModel');
        
        if ($id) {
            $existing = $model->find($id);
            if (!$existing || (int)$existing['id_pegawai'] !== (int)$userProfile->id_pegawai) {
                return $this->response->setStatusCode(404)->setBody('Not found');
            }
            if ($existing['status_pengajuan'] !== 'PENDING') {
                return $this->response->setStatusCode(403)
                    ->setHeader('HX-Trigger', json_encode(['toast' => ['type' => 'error', 'message' => 'Hanya PENDING yang dapat diubah']]))
                    ->setBody('Forbidden');
            }
        }

        $tipe = $input['tipe_ketidakhadiran'];
        $status = ($tipe === 'SAKIT') ? 'APPROVED' : 'PENDING';

        // Handle file upload if provided
        $buktiFilename = null;
        if ($file && $file->isValid() && !$file->hasMoved()) {
            $photoService = new \App\Services\PhotoService();
            try {
                // Read file content and encode as base64 for PhotoService
                $fileContent = file_get_contents($file->getTempName());
                $base64 = base64_encode($fileContent);
                $result = $photoService->uploadPresensiPhoto('bukti', $userProfile->username, $base64);
                $buktiFilename = $result['filename'];
            } catch (\Exception $e) {
                log_message('error', 'S3 upload bukti failed: ' . $e->getMessage());
                return $this->response->setStatusCode(500)
                    ->setHeader('HX-Trigger', json_encode(['toast' => ['type' => 'error', 'message' => 'Gagal mengunggah bukti']]))
                    ->setJSON(['message' => 'Gagal mengunggah bukti']);
            }
        }

        $saveData = [
            'id_pegawai' => $userProfile->id_pegawai,
            'tipe_ketidakhadiran' => $tipe,
            'tanggal_mulai' => $input['tanggal_mulai'],
            'tanggal_berakhir' => $input['tanggal_berakhir'],
            'deskripsi' => $input['deskripsi'],
            'status_pengajuan' => $status
        ];

        if ($buktiFilename) {
            $saveData['file'] = $buktiFilename;
        }

        if ($id) $saveData['id'] = $id;

        if (!$model->save($saveData)) {
            return $this->response->setStatusCode(500)
                ->setHeader('HX-Trigger', json_encode(['toast' => ['type' => 'error', 'message' => 'Gagal menyimpan pengajuan']]))
                ->setBody('Error');
        }

        return $this->response->setHeader('HX-Trigger', json_encode([
            'toast' => ['type' => 'success', 'message' => $id ? 'Pengajuan diupdate' : 'Pengajuan dikirim'],
            'reloadKetidakhadiranTable' => true,
                'close-modal' => true
        ]))->setBody('');
    }

    public function delete($id = null)
    {

        $userProfile = $this->getAuthUser();
        
        if (!$id) return $this->response->setStatusCode(422)->setBody('ID dibutuhkan');

        $model = model('KetidakhadiranModel');
        $existing = $model->find($id);
        
        if (!$existing || (int)$existing['id_pegawai'] !== (int)$userProfile->id_pegawai) {
            return $this->response->setStatusCode(404)->setBody('Not found');
        }

        if ($existing['status_pengajuan'] !== 'PENDING') {
            return $this->response->setStatusCode(403)
                ->setHeader('HX-Trigger', json_encode(['toast' => ['type' => 'error', 'message' => 'Hanya PENDING yang dapat dihapus']]))
                ->setBody('Forbidden');
        }

        $model->delete($id);

        return $this->response->setHeader('HX-Trigger', json_encode([
            'toast' => ['type' => 'success', 'message' => 'Pengajuan dihapus'],
            'reloadKetidakhadiranTable' => true
        ]))->setBody('');
    }

    public function updateStatus($id = null)
    {

        if (!$id) return $this->response->setStatusCode(422)->setBody('ID dibutuhkan');

        $status = $this->request->getPost('status_pengajuan');
        if (!in_array($status, ['APPROVED', 'REJECTED', 'PENDING'])) {
            return $this->response->setStatusCode(422)
                ->setHeader('HX-Trigger', json_encode(['toast' => ['type' => 'error', 'message' => 'Status tidak valid']]))
                ->setBody('Invalid status');
        }

        $model = model('KetidakhadiranModel');
        $existing = $model->find($id);
        
        if (!$existing) {
            return $this->response->setStatusCode(404)->setBody('Not found');
        }

        $model->save(['id' => $id, 'status_pengajuan' => $status]);

        return $this->response->setHeader('HX-Trigger', json_encode([
            'toast' => ['type' => 'success', 'message' => 'Status berhasil diubah menjadi ' . $status],
            'reloadKetidakhadiranTable' => true
        ]))->setBody('');
    }
}
