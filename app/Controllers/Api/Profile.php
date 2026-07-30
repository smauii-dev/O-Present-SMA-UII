<?php

namespace App\Controllers\Api;

use App\Models\PegawaiModel;
use App\Validation\PegawaiRules;

class Profile extends ApiBaseController
{
    protected PegawaiModel $pegawaiModel;

    public function __construct()
    {
        $this->pegawaiModel = new PegawaiModel();
    }

    public function index()
    {
        $userProfile = $this->getUserOrError();
        if (is_object($userProfile) === false) return $userProfile;

        return $this->success([
            'id' => $userProfile->userid ?? null,
            'nama' => $userProfile->nama ?? null,
            'nip' => $userProfile->nip ?? null,
            'email' => user()->email ?? null,
            'username' => user()->username ?? null,
            'foto' => $userProfile->foto ?? null,
            'foto_url' => $this->getProfilePhotoUrl($userProfile->foto ?? null),
            'no_handphone' => $userProfile->no_handphone ?? null,
            'alamat' => $userProfile->alamat ?? null,
            'jenis_kelamin' => $userProfile->jenis_kelamin ?? null,
            'jabatan' => $userProfile->jabatan ?? null,
            'lokasi_presensi' => $userProfile->lokasi_presensi ?? null,
        ]);
    }

    public function updateProfile()
    {
        $userProfile = $this->getUserOrError();
        if (is_object($userProfile) === false) return $userProfile;

        $input = $this->getAllInput();

        if (!$this->validate(PegawaiRules::profileUpdate())) {
            return $this->error('Validasi gagal', 422, $this->validator->getErrors());
        }

        $this->pegawaiModel->save([
            'id' => $userProfile->id_pegawai,
            'nama' => $input['nama'],
            'no_handphone' => $input['no_handphone'],
            'alamat' => $input['alamat'],
            'jenis_kelamin' => $input['jenis_kelamin'],
        ]);

        return $this->success(null, 'Profil berhasil diperbarui');
    }

    public function uploadPhoto()
    {
        $userProfile = $this->getUserOrError();
        if (is_object($userProfile) === false) return $userProfile;

        $fotoBase64 = $this->getInput('foto');
        if (empty($fotoBase64)) {
            return $this->error('Foto wajib diisi', 422);
        }

        try {
            $result = $this->photoService->uploadProfilePhoto($userProfile->username, $fotoBase64);
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        } catch (\Exception $e) {
            log_message('error', 'S3 upload profile photo failed: ' . $e->getMessage());
            return $this->error('Gagal menyimpan foto ke storage', 500);
        }

        // Delete old photo from S3 if not default
        if (!empty($userProfile->foto) && $userProfile->foto !== 'default.jpg') {
            $this->photoService->deleteProfilePhoto($userProfile->foto);
        }

        $this->pegawaiModel->save([
            'id' => $userProfile->id_pegawai,
            'foto' => $result['filename'],
        ]);

        return $this->success([
            'foto' => $result['filename'],
            'foto_url' => $result['url'],
        ], 'Foto profil berhasil diperbarui');
    }

    public function changePassword()
    {
        $userProfile = $this->getUserOrError();
        if (is_object($userProfile) === false) return $userProfile;

        $oldPassword = $this->getInput('old_password');
        $newPassword = $this->getInput('new_password');
        $confirmPassword = $this->getInput('confirm_password');

        if (empty($oldPassword) || empty($newPassword) || empty($confirmPassword)) {
            return $this->error('Semua field password wajib diisi', 422);
        }

        if ($newPassword !== $confirmPassword) {
            return $this->error('Password baru dan konfirmasi tidak cocok', 422);
        }

        if (strlen($newPassword) < 8) {
            return $this->error('Password baru minimal 8 karakter', 422);
        }

        $user = user();
        if (!password_verify(base64_encode(hash('sha384', $oldPassword, true)), $user->password_hash)) {
            return $this->error('Password lama salah', 422);
        }

        $this->usersModel->save([
            'id' => $user->id,
            'password_hash' => $this->usersModel->hashPassword($newPassword),
            'force_pass_reset' => 0,
        ]);

        return $this->success(null, 'Password berhasil diubah');
    }

    public function forceChangePassword()
    {
        $userProfile = $this->getUserOrError();
        if (is_object($userProfile) === false) return $userProfile;

        $newPassword = $this->getInput('new_password');
        $confirmPassword = $this->getInput('confirm_password');

        if (empty($newPassword) || empty($confirmPassword)) {
            return $this->error('Password baru dan konfirmasi wajib diisi', 422);
        }

        if ($newPassword !== $confirmPassword) {
            return $this->error('Password baru dan konfirmasi tidak cocok', 422);
        }

        if (strlen($newPassword) < 8) {
            return $this->error('Password baru minimal 8 karakter', 422);
        }

        $user = user();
        $this->usersModel->save([
            'id' => $user->id,
            'password_hash' => $this->usersModel->hashPassword($newPassword),
            'force_pass_reset' => 0,
        ]);

        return $this->success(null, 'Password berhasil diubah. Silakan lanjutkan.');
    }
}
