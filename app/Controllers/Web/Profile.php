<?php

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Models\PegawaiModel;
use App\Models\UsersModel;
use App\Services\PhotoService;

class Profile extends BaseController
{
    public function index()
    {

        return $this->view('routes/profile/index', ['pageTitle' => 'Profil']);
    }

    public function updateProfileAction()
    {

        $userProfile = $this->getAuthUser();
        
        $rules = [
            'nama' => 'required',
            'jenis_kelamin' => 'required|in_list[Laki-laki,Perempuan]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', 'Validasi gagal, pastikan nama dan jenis kelamin diisi');
        }

        $pegawaiModel = new PegawaiModel();
        $pegawaiModel->save([
            'id' => $userProfile->id_pegawai,
            'nama' => $this->request->getPost('nama'),
            'no_handphone' => $this->request->getPost('no_handphone'),
            'alamat' => $this->request->getPost('alamat'),
            'jenis_kelamin' => $this->request->getPost('jenis_kelamin'),
        ]);

        return redirect()->back()->with('success', 'Profil berhasil diperbarui');
    }

    public function uploadPhotoAction()
    {

        $userProfile = $this->getAuthUser();
        $file = $this->request->getFile('foto');
        
        if (!$file || !$file->isValid()) {
            return redirect()->back()->with('error', 'Foto wajib diisi dan valid');
        }

        $photoService = new PhotoService();
        $pegawaiModel = new PegawaiModel();

        try {
            $base64 = 'data:' . $file->getMimeType() . ';base64,' . base64_encode(file_get_contents($file->getTempName()));
            $result = $photoService->uploadProfilePhoto($userProfile->username, $base64);

            if (!empty($userProfile->foto) && $userProfile->foto !== 'default.jpg') {
                $photoService->deleteProfilePhoto($userProfile->foto);
            }

            $pegawaiModel->save([
                'id' => $userProfile->id_pegawai,
                'foto' => $result['filename'],
            ]);

            return redirect()->back()->with('success', 'Foto profil berhasil diperbarui');
        } catch (\Exception $e) {
            log_message('error', 'Upload failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal menyimpan foto: ' . $e->getMessage());
        }
    }

    public function changePasswordAction()
    {

        $oldPassword = $this->request->getPost('password_lama');
        $newPassword = $this->request->getPost('password_baru');
        $confirmPassword = $this->request->getPost('password_confirm');

        if (empty($oldPassword) || empty($newPassword) || empty($confirmPassword)) {
            return redirect()->back()->with('error', 'Semua field password wajib diisi');
        }

        if ($newPassword !== $confirmPassword) {
            return redirect()->back()->with('error', 'Password baru dan konfirmasi tidak cocok');
        }

        if (strlen($newPassword) < 8) {
            return redirect()->back()->with('error', 'Password baru minimal 8 karakter');
        }

        $auth = service('authentication');
        if (! $auth->isLoggedIn()) {
            return redirect()->to('/login');
        }

        $usersModel = new UsersModel();
        $user = $usersModel->getUserInfo($auth->id());
        if (! $user || ! \Myth\Auth\Password::verify($oldPassword, $user->password_hash)) {
            return redirect()->back()->with('error', 'Password lama salah');
        }

        $usersModel->save([
            'id' => $auth->id(),
            'password_hash' => $usersModel->hashPassword($newPassword),
            'force_pass_reset' => 0,
        ]);

        return redirect()->back()->with('success', 'Password berhasil diubah');
    }

    public function forceChangePasswordAction()
    {
        $newPassword = $this->request->getPost('password_baru');
        $confirmPassword = $this->request->getPost('password_confirm');

        if (empty($newPassword) || empty($confirmPassword)) {
            return redirect()->back()->with('error', 'Password baru dan konfirmasi wajib diisi');
        }

        if ($newPassword !== $confirmPassword) {
            return redirect()->back()->with('error', 'Password baru dan konfirmasi tidak cocok');
        }

        if (strlen($newPassword) < 8) {
            return redirect()->back()->with('error', 'Password baru minimal 8 karakter');
        }

        // Use isLoggedIn()+id() instead of user() helper — user() calls check()
        // which throws RedirectException for force_pass_reset users.
        $auth = service('authentication');
        if (! $auth->isLoggedIn()) {
            return redirect()->to('/login');
        }

        $usersModel = new UsersModel();
        $usersModel->save([
            'id' => $auth->id(),
            'password_hash' => $usersModel->hashPassword($newPassword),
            'force_pass_reset' => 0,
        ]);

        if ($this->request->hasHeader('HX-Request')) {
            $this->response->setHeader('HX-Redirect', '/overview');
            session()->setFlashdata('success', 'Password berhasil diubah. Silakan lanjutkan.');
            return $this->response;
        }

        return redirect()->to('/overview')->with('success', 'Password berhasil diubah. Selamat datang!');
    }
}
