<?php

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Services\LokasiService;

class Lokasi extends BaseController
{
    public function index()
    {
        return $this->view('routes/lokasi/index', ['pageTitle' => 'Lokasi Presensi']);
    }

    public function fragment()
    {
        $lokasi = model('LokasiPresensiModel')
            ->orderBy('nama_lokasi', 'ASC')
            ->findAll();

        return $this->view('routes/lokasi/fragment', ['lokasi' => $lokasi]);
    }

    public function form($id = null)
    {
        $data = ['lokasi' => null];
        if ($id) {
            $data['lokasi'] = model('LokasiPresensiModel')->find($id);
            if (! $data['lokasi']) {
                return $this->response->setStatusCode(404)->setBody('Data tidak ditemukan');
            }
        }

        return $this->view('routes/lokasi/form_modal', $data);
    }

    public function save($id = null)
    {
        $id      = $id !== null ? (int) $id : null;
        $service = new LokasiService();
        $prepared = $service->preparePayload($this->request->getPost() ?? [], $id);

        if (! $prepared['ok']) {
            $msg = implode(', ', array_values($prepared['errors']));

            return $this->response->setStatusCode(422)
                ->setHeader('HX-Trigger', json_encode([
                    'toast' => ['type' => 'error', 'message' => $msg],
                ]))
                ->setJSON(['errors' => $prepared['errors']]);
        }

        try {
            $service->save($prepared['data'], $id);
        } catch (\Throwable $e) {
            log_message('error', 'Lokasi save failed: ' . $e->getMessage());

            // Unique violation from DB (race / legacy)
            $friendly = str_contains(strtolower($e->getMessage()), 'unique')
                || str_contains(strtolower($e->getMessage()), 'duplicate')
                ? 'Nama atau koordinat lokasi bentrok dengan data yang sudah ada'
                : 'Gagal menyimpan lokasi';

            return $this->response->setStatusCode(409)
                ->setHeader('HX-Trigger', json_encode([
                    'toast' => ['type' => 'error', 'message' => $friendly],
                ]))
                ->setBody('Conflict');
        }

        return $this->response->setHeader('HX-Trigger', json_encode([
            'toast'             => [
                'type'    => 'success',
                'message' => $id ? 'Lokasi berhasil diupdate' : 'Lokasi berhasil ditambahkan',
            ],
            'reloadLokasiTable' => true,
            'close-modal'       => true,
        ]))->setBody('');
    }

    public function delete($id = null)
    {
        if (! $id) {
            return $this->response->setStatusCode(422)->setBody('ID dibutuhkan');
        }

        $model = model('LokasiPresensiModel');
        if (! $model->find($id)) {
            return $this->response->setStatusCode(404)->setBody('Not found');
        }

        $pegawaiCount = model('PegawaiModel')->where('id_lokasi_presensi', $id)->countAllResults();
        if ($pegawaiCount > 0) {
            return $this->response->setStatusCode(409)
                ->setHeader('HX-Trigger', json_encode([
                    'toast' => [
                        'type'    => 'error',
                        'message' => 'Gagal: Masih ada ' . $pegawaiCount . ' pegawai di lokasi ini',
                    ],
                ]))
                ->setBody('Conflict');
        }

        // Protect all canonical SMA UII attendance points
        $row  = $model->find($id);
        $slug = is_array($row) ? ($row['slug'] ?? '') : (string) ($row->slug ?? '');
        helper('geo');
        $canonicalSlugs = array_column(canonical_sma_uii_locations(), 'slug');
        if (in_array($slug, $canonicalSlugs, true)) {
            return $this->response->setStatusCode(409)
                ->setHeader('HX-Trigger', json_encode([
                    'toast' => [
                        'type'    => 'error',
                        'message' => 'Lokasi resmi SMA UII tidak boleh dihapus (boleh diedit koordinat/radius)',
                    ],
                ]))
                ->setBody('Conflict');
        }

        $model->delete($id);

        return $this->response->setHeader('HX-Trigger', json_encode([
            'toast'             => ['type' => 'success', 'message' => 'Lokasi berhasil dihapus'],
            'reloadLokasiTable' => true,
            'close-modal'       => true,
        ]))->setBody('');
    }
}
