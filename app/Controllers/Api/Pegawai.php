<?php

namespace App\Controllers\Api;

use App\Models\PegawaiModel;
use App\Models\JabatanModel;
use App\Models\LokasiPresensiModel;
use App\Validation\PegawaiRules;
use Myth\Auth\Models\GroupModel;
use App\Services\ExportService;

class Pegawai extends ApiBaseController
{
    protected PegawaiModel $pegawaiModel;
    protected JabatanModel $jabatanModel;
    protected GroupModel $usersRoleModel;
    protected ExportService $exportService;

    public function __construct()
    {
        $this->pegawaiModel = new PegawaiModel();
        $this->jabatanModel = new JabatanModel();
        $this->lokasiModel = new LokasiPresensiModel();
        $this->usersRoleModel = new GroupModel();
        $this->exportService = new ExportService();
    }

    public function index()
    {
        $page = (int) ($this->getInput('page', 1));
        $perPage = 20;
        $search = $this->getInput('search');

        $filter = $search ? ['keyword' => $search] : false;
        $data = $this->pegawaiModel->getPegawai(false, $filter);
        $allData = $data['pegawai'] ?? [];
        $total = $data['total'] ?? count($allData);

        foreach ($allData as &$item) {
            $item = (array) $item;
            $item['foto_url'] = $this->getProfilePhotoUrl($item['foto'] ?? null);
        }
        unset($item);

        return $this->paginated($allData, $total, $page, $perPage);
    }

    public function detail($id = null)
    {
        if (!$id) {
            return $this->error('ID pegawai wajib diisi', 422);
        }

        $data = $this->pegawaiModel->getPegawai($id);
        if (!$data || empty($data['pegawai'])) {
            return $this->error('Pegawai tidak ditemukan', 404);
        }

        $result = (array) $data['pegawai'];
        $result['foto_url'] = $this->getProfilePhotoUrl($result['foto'] ?? null);

        return $this->success($result);
    }

    public function store()
    {
        $adminCheck = $this->requireAdmin();
        if ($adminCheck) return $adminCheck;

        if (!$this->validate(PegawaiRules::create())) {
            return $this->error('Validasi gagal', 422, $this->validator->getErrors());
        }

        $input = $this->getAllInput();
        $nipBaru = $this->pegawaiModel->generateNIP();
        $passwordDefault = '123456';
        $passwordHash = $this->usersModel->hashPassword($passwordDefault);

        $db = \Config\Database::connect();
        $db->transStart();

        $this->pegawaiModel->save([
            'nip' => $nipBaru,
            'nama' => $input['nama'],
            'jenis_kelamin' => $input['jenis_kelamin'],
            'alamat' => $input['alamat'],
            'no_handphone' => $input['no_handphone'],
            'id_jabatan' => $input['jabatan'],
            'id_lokasi_presensi' => $input['lokasi_presensi'],
            'foto' => 'default.jpg',
        ]);

        $idPegawai = $this->pegawaiModel->insertID();

        $this->usersModel->save([
            'id_pegawai' => $idPegawai,
            'email' => $input['email'],
            'username' => $input['username'],
            'password_hash' => $passwordHash,
            'active' => 1,
        ]);

        $userId = $this->usersModel->insertID();

        $groupModel = new GroupModel();
        $groupModel->addUserToGroup($userId, (int) $input['role']);

        $db->transComplete();

        if ($db->transStatus() === false) {
            return $this->error('Gagal menyimpan data pegawai', 500);
        }

        return $this->success([
            'id_pegawai' => $idPegawai,
            'nip' => $nipBaru,
            'username' => $input['username'],
        ], 'Data pegawai berhasil ditambahkan', 201);
    }

    public function update($id = null)
    {
        $adminCheck = $this->requireAdmin();
        if ($adminCheck) return $adminCheck;

        if (!$id) {
            return $this->error('ID pegawai wajib diisi', 422);
        }

        $data = $this->pegawaiModel->getPegawai($id);
        if (!$data || empty($data['pegawai'])) {
            return $this->error('Pegawai tidak ditemukan', 404);
        }

        $input = $this->getAllInput();
        $pegawai = $data['pegawai'];

        $emailInput = $input['email'] ?? null;
        $emailDb = $pegawai->email ?? null;
        $usernameInput = $input['username'] ?? null;
        $usernameDb = $pegawai->username ?? null;

        $emailUnique = (bool) ($emailInput && $emailInput !== $emailDb);
        $usernameUnique = (bool) ($usernameInput && $usernameInput !== $usernameDb);

        if (!$this->validate(PegawaiRules::update($emailUnique, $usernameUnique))) {
            return $this->error('Validasi gagal', 422, $this->validator->getErrors());
        }

        $db = \Config\Database::connect();
        $db->transStart();

        $this->pegawaiModel->save([
            'id' => $id,
            'nip' => $input['nip'] ?? $pegawai->nip,
            'nama' => $input['nama'],
            'jenis_kelamin' => $input['jenis_kelamin'],
            'alamat' => $input['alamat'],
            'no_handphone' => $input['no_handphone'],
            'id_jabatan' => $input['jabatan'],
            'id_lokasi_presensi' => $input['lokasi_presensi'],
        ]);

        $this->usersModel->save([
            'id' => $pegawai->user_id,
            'id_pegawai' => $id,
            'email' => $emailInput,
            'username' => $usernameInput,
        ]);

        $roleInput = (int) ($input['role'] ?? 0);
        $roleDb = (int) ($pegawai->role_id ?? 0);
        if ($roleInput && $roleInput !== $roleDb) {
            $groupModel = new GroupModel();
            $groupModel->addUserToGroup($pegawai->user_id, $roleInput);
            $groupModel->removeUserFromGroup($pegawai->user_id, $roleDb);
        }

        $db->transComplete();

        if ($db->transStatus() === false) {
            return $this->error('Gagal memperbarui data pegawai', 500);
        }

        return $this->success(null, 'Data pegawai berhasil diperbarui');
    }

    public function delete($id = null)
    {
        $adminCheck = $this->requireAdmin();
        if ($adminCheck) return $adminCheck;

        if (!$id) {
            return $this->error('ID pegawai wajib diisi', 422);
        }

        $data = $this->pegawaiModel->getPegawai($id);
        if (!$data || empty($data['pegawai'])) {
            return $this->error('Pegawai tidak ditemukan', 404);
        }

        if (!empty($data['pegawai']->foto) && $data['pegawai']->foto !== 'default.jpg') {
            $this->photoService->deleteProfilePhoto($data['pegawai']->foto);
        }

        $this->pegawaiModel->delete($id);

        return $this->success(null, 'Data pegawai berhasil dihapus');
    }

    public function exportExcel()
    {
        if ($err = $this->requireAdmin()) return $err;

        $filter = [
            'keyword' => $this->getInput('keyword', ''),
            'jabatan' => $this->getInput('jabatan', ''),
            'role' => $this->getInput('role', ''),
            'status' => $this->getInput('status', ''),
            'jenis-kelamin' => $this->getInput('jenis-kelamin', ''),
            'lokasi-presensi' => $this->getInput('lokasi-presensi', ''),
        ];
        $dataPegawai = $this->pegawaiModel->getPegawai(false, $filter, true)['pegawai'];

        return $this->exportService->exportPegawai($dataPegawai, $filter);
    }

    public function importExcel()
    {
        if ($err = $this->requireAdmin()) return $err;

        $file = $this->request->getFile('file_excel');
        if (!$file || !$file->isValid() || !in_array($file->getExtension(), ['xls', 'xlsx'])) {
            return $this->error('Format file tidak valid. Gunakan file .xls atau .xlsx', 422);
        }

        $spreadsheet = IOFactory::load($file->getTempName());
        $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);

        $nipTerakhir = $this->pegawaiModel->getNIPPegawai();
        if (!empty($nipTerakhir)) {
            $parts = explode('-', $nipTerakhir);
            $nomorUrut = (int)$parts[1];
        } else {
            $nomorUrut = 0;
        }

        $db = \Config\Database::connect();
        $db->transStart();

        foreach ($sheetData as $index => $row) {
            if ($index == 1) continue;
            if (empty($row['A'])) break;

            $nomorUrut++;
            $nipBaru = 'PEG-' . str_pad($nomorUrut, 4, 0, STR_PAD_LEFT);
            $passwordHash = $this->usersModel->hashPassword((string)$row['J']);

            $this->pegawaiModel->save([
                'nip' => $nipBaru,
                'nama' => $row['A'],
                'jenis_kelamin' => $row['B'],
                'alamat' => $row['C'],
                'no_handphone' => $row['D'],
                'id_jabatan' => $row['E'],
                'id_lokasi_presensi' => $row['F'],
                'foto' => 'default.jpg',
            ]);
            $idPegawai = $this->pegawaiModel->insertID();

            $this->usersModel->save([
                'id_pegawai' => $idPegawai,
                'email' => $row['G'],
                'username' => $row['H'],
                'password_hash' => $passwordHash,
                'active' => 1,
                'activate_hash' => null,
            ]);
            $userId = $this->usersModel->insertID();

            $this->usersRoleModel->save([
                'group_id' => $row['I'],
                'user_id' => $userId,
            ]);
        }

        $db->transComplete();

        if ($db->transStatus() === false) {
            return $this->error('Terjadi kesalahan sistem. Data gagal diimport.', 500);
        }

        // Set HTMX headers to reload table and close modal
        $this->response->setHeader('HX-Trigger', json_encode([
            'toast' => ['type' => 'success', 'message' => 'Data pegawai berhasil diimport secara massal.'],
            'reloadPegawaiTable' => true,
            'closePegawai-importModal' => true,
            'closePegawaiImportModal' => true
        ]));

        return $this->success(null, 'Data pegawai berhasil diimport secara massal.', 201);
    }

    public function downloadTemplate()
    {
        if ($err = $this->requireAdmin()) return $err;

        return $this->exportService->downloadPegawaiTemplate();
    }
}
