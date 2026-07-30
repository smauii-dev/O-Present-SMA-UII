<?php

namespace App\Controllers\Web;

use App\Controllers\BaseController;

class Jabatan extends BaseController
{
    public function index()
    {

        return $this->view('routes/jabatan/index', ['pageTitle' => 'Jabatan']);
    }

    public function fragment()
    {

        $model = model('JabatanModel');
        $jabatan = $model->select('jabatan.*, COUNT(pegawai.id) as total_pegawai')
            ->join('pegawai', 'pegawai.id_jabatan = jabatan.id', 'left')
            ->groupBy('jabatan.id')
            ->orderBy('jabatan.jabatan', 'ASC')
            ->get()
            ->getResult();

        return $this->view('routes/jabatan/fragment', ['jabatan' => $jabatan]);
    }

    public function form($id = null)
    {

        $data = ['jabatan' => null];
        if ($id) {
            $data['jabatan'] = model('JabatanModel')->find($id);
            if (!$data['jabatan']) {
                return $this->response->setStatusCode(404)->setBody('Data tidak ditemukan');
            }
        }

        return $this->view('routes/jabatan/form_modal', $data);
    }

    public function save($id = null)
    {

        $input = $this->request->getPost();
        if (empty($input['jabatan'])) {
            return $this->response->setStatusCode(422)
                ->setHeader('HX-Trigger', json_encode(['toast' => ['type' => 'error', 'message' => 'Nama jabatan wajib diisi']]))
                ->setBody('Validasi gagal');
        }

        $model = model('JabatanModel');
        $saveData = ['jabatan' => $input['jabatan']];
        if ($id) {
            $saveData['id'] = $id;
        }

        if (!$model->save($saveData)) {
            return $this->response->setStatusCode(500)
                ->setHeader('HX-Trigger', json_encode(['toast' => ['type' => 'error', 'message' => 'Gagal menyimpan jabatan']]))
                ->setBody('Error');
        }

        return $this->response->setHeader('HX-Trigger', json_encode([
            'toast' => ['type' => 'success', 'message' => $id ? 'Jabatan berhasil diupdate' : 'Jabatan berhasil ditambahkan'],
            'reloadJabatanTable' => true,
                'close-modal' => true
        ]))->setBody('');
    }

    public function delete($id = null)
    {

        if (!$id) return $this->response->setStatusCode(422)->setBody('ID dibutuhkan');

        $model = model('JabatanModel');
        if (!$model->find($id)) {
            return $this->response->setStatusCode(404)->setBody('Not found');
        }

        // Check if there are employees with this position
        $pegawaiCount = model('PegawaiModel')->where('id_jabatan', $id)->countAllResults();
        if ($pegawaiCount > 0) {
            return $this->response->setStatusCode(409)
                ->setHeader('HX-Trigger', json_encode(['toast' => ['type' => 'error', 'message' => 'Gagal: Masih ada ' . $pegawaiCount . ' pegawai dengan jabatan ini']]))
                ->setBody('Conflict');
        }

        $model->delete($id);

        return $this->response->setHeader('HX-Trigger', json_encode([
            'toast' => ['type' => 'success', 'message' => 'Jabatan berhasil dihapus'],
            'reloadJabatanTable' => true
        ]))->setBody('');
    }
}
