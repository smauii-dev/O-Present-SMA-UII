<?php

namespace App\Controllers\Api;

use Myth\Auth\Password;
use Myth\Auth\Models\UserModel;

class Auth extends ApiBaseController
{
    protected UserModel $authUserModel;

    public function __construct()
    {
        $this->authUserModel = new UserModel();
    }

    public function login()
    {
        $loginInput = $this->getInput('login') ?? $this->getInput('email');
        $credentials = [
            'login'    => $loginInput,
            'password' => $this->getInput('password'),
        ];

        if (empty($credentials['login']) || empty($credentials['password'])) {
            return $this->error('Username/Email dan password wajib diisi', 422);
        }

        $user = $this->authUserModel->where('email', $credentials['login'])
                                    ->orWhere('username', $credentials['login'])
                                    ->first();

        if (!$user) {
            return $this->error('Username/Email atau password salah', 401);
        }

        if (!$user->active) {
            return $this->error('Akun belum aktif. Silakan cek email untuk aktivasi.', 403);
        }

        if (!Password::verify($credentials['password'], $user->password_hash)) {
            return $this->error('Username/Email atau password salah', 401);
        }

        $authenticate = service('authentication');
        $authenticate->login($user);

        $userProfile = $this->getUsersModel()->getUserInfo($user->id);
        $foto = $userProfile->foto ?? null;

        return $this->success([
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'username' => $user->username,
                'group' => array_values($user->getRoles())[0] ?? null,
                'nama' => $userProfile->nama ?? null,
                'nip' => $userProfile->nip ?? null,
                'foto' => $foto,
                'foto_url' => $this->getProfilePhotoUrl($foto),
                'id_pegawai' => $userProfile->id_pegawai ?? null,
                'lokasi_presensi' => $userProfile->lokasi_presensi ?? null,
            ],
        ], 'Login berhasil');
    }

    public function logout()
    {
        $authenticate = service('authentication');
        $authenticate->logout();

        return $this->success(null, 'Logout berhasil');
    }

    public function me()
    {
        $authenticate = service('authentication');
        if (!$authenticate->check()) {
            return $this->error('Belum login', 401);
        }

        $userProfile = $this->getCurrentUserProfile();
        if (!$userProfile) {
            return $this->error('User tidak ditemukan', 404);
        }

        $foto = $userProfile->foto ?? null;

        return $this->success([
            'user' => [
                'id' => $userProfile->userid ?? null,
                'email' => $userProfile->email ?? null,
                'username' => $userProfile->username ?? null,
                'group' => $userProfile->role ?? null,
                'nama' => $userProfile->nama ?? null,
                'nip' => $userProfile->nip ?? null,
                'foto' => $foto,
                'foto_url' => $this->getProfilePhotoUrl($foto),
                'id_pegawai' => $userProfile->id_pegawai ?? null,
                'lokasi_presensi' => $userProfile->lokasi_presensi ?? null,
            ],
        ]);
    }

    /**
     * Request password reset email (public).
     * Always returns a generic success message to avoid email enumeration.
     */
    public function forgot()
    {
        $config = config('Auth');
        if ($config->activeResetter === null) {
            return $this->error('Fitur reset password dinonaktifkan', 503);
        }

        $email = $this->getInput('email');
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->error('Email tidak valid', 422);
        }

        $user = $this->authUserModel->where('email', $email)->first();
        if ($user) {
            $user->generateResetHash();
            $this->authUserModel->save($user);

            $resetter = service('resetter');
            $sent = $resetter->send($user);
            if (!$sent) {
                log_message('error', 'Password reset email failed: ' . ($resetter->error() ?? 'unknown'));
                // Still return generic message in production-facing API
            }
        }

        return $this->success(
            null,
            'Jika email terdaftar, instruksi reset password telah dikirim.'
        );
    }

    /**
     * Complete password reset with token from email (public).
     */
    public function reset()
    {
        $config = config('Auth');
        if ($config->activeResetter === null) {
            return $this->error('Fitur reset password dinonaktifkan', 503);
        }

        $token = $this->getInput('token');
        $email = $this->getInput('email');
        $password = $this->getInput('password');
        $passConfirm = $this->getInput('pass_confirm');

        if (empty($token) || empty($email) || empty($password) || empty($passConfirm)) {
            return $this->error('Semua field wajib diisi', 422);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->error('Email tidak valid', 422);
        }

        if ($password !== $passConfirm) {
            return $this->error('Password dan konfirmasi tidak cocok', 422);
        }

        if (strlen($password) < 8) {
            return $this->error('Password minimal 8 karakter', 422);
        }

        $this->authUserModel->logResetAttempt(
            $email,
            $token,
            $this->request->getIPAddress(),
            (string) $this->request->getUserAgent()
        );

        $user = $this->authUserModel
            ->where('email', $email)
            ->where('reset_hash', $token)
            ->first();

        if (!$user) {
            return $this->error('Token tidak valid atau email tidak cocok', 400);
        }

        if (!empty($user->reset_expires) && time() > $user->reset_expires->getTimestamp()) {
            return $this->error('Token reset sudah kedaluwarsa', 400);
        }

        $user->password = $password;
        $user->reset_hash = null;
        $user->reset_at = date('Y-m-d H:i:s');
        $user->reset_expires = null;
        $user->force_pass_reset = false;
        $this->authUserModel->save($user);

        return $this->success(null, 'Password berhasil direset. Silakan login.');
    }
}
