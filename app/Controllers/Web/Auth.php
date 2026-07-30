<?php

namespace App\Controllers\Web;

use App\Controllers\BaseController;

class Auth extends BaseController
{
    public function login()
    {
        if (user_id()) {
return redirect()->to('/overview');
        }

        return $this->view('routes/auth/login', [
            'pageTitle' => 'Masuk',
        ]);
    }

    public function forgot()
    {
        return $this->view('routes/auth/forgot', [
            'pageTitle' => 'Lupa Password',
        ]);
    }

    public function reset()
    {
        return $this->view('routes/auth/reset', [
            'pageTitle' => 'Reset Password',
            'token'     => $this->request->getGet('token'),
            'email'     => $this->request->getGet('email'),
        ]);
    }

    public function logout()
    {
        $auth = service('authentication');
        $auth->logout();

        return redirect()->to('/login');
    }
    public function loginAction()
    {
        $credentials = [
            'login'    => $this->request->getPost('login') ?? $this->request->getPost('email'),
            'password' => $this->request->getPost('password'),
        ];

        if (empty($credentials['login']) || empty($credentials['password'])) {
            return redirect()->back()->withInput()->with('error', 'Username/Email dan password wajib diisi');
        }

        $authUserModel = new \Myth\Auth\Models\UserModel();
        $login = $credentials['login'];
        $user = $authUserModel->where('email', $login)
                              ->orWhere('username', $login)
                              ->first();

        if (!$user) {
            return redirect()->back()->withInput()->with('error', 'Username/Email atau password salah');
        }

        if (!$user->active) {
            return redirect()->back()->withInput()->with('error', 'Akun belum aktif. Silakan cek email untuk aktivasi.');
        }

        if (!\Myth\Auth\Password::verify($credentials['password'], $user->password_hash)) {
            return redirect()->back()->withInput()->with('error', 'Username/Email atau password salah');
        }

        $authenticate = service('authentication');
        $authenticate->login($user);

        // Force session to write before redirect
        session()->close();

        if ($this->request->hasHeader('HX-Request')) {
            $this->response->setHeader('HX-Redirect', '/');
            return $this->response;
        }

        return redirect()->to('/');
    }

    public function forgotAction()
    {
        $config = config('Auth');
        if ($config->activeResetter === null) {
            return redirect()->back()->with('error', 'Fitur reset password dinonaktifkan');
        }

        $email = $this->request->getPost('email');
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return redirect()->back()->withInput()->with('error', 'Email tidak valid');
        }

        $authUserModel = new \Myth\Auth\Models\UserModel();
        $user = $authUserModel->where('email', $email)->first();
        if ($user) {
            $user->generateResetHash();
            $authUserModel->save($user);

            $resetter = service('resetter');
            $sent = $resetter->send($user);
            if (!$sent) {
                log_message('error', 'Password reset email failed: ' . ($resetter->error() ?? 'unknown'));
            }
        }

        return redirect()->back()->with('success', 'Jika email terdaftar, instruksi reset password telah dikirim.');
    }

    public function resetAction()
    {
        $config = config('Auth');
        if ($config->activeResetter === null) {
            return redirect()->back()->with('error', 'Fitur reset password dinonaktifkan');
        }

        $token = $this->request->getPost('token');
        $email = $this->request->getPost('email');
        $password = $this->request->getPost('password');
        $passConfirm = $this->request->getPost('password_confirm');

        if (empty($token) || empty($email) || empty($password) || empty($passConfirm)) {
            return redirect()->back()->withInput()->with('error', 'Semua field wajib diisi');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return redirect()->back()->withInput()->with('error', 'Email tidak valid');
        }

        if ($password !== $passConfirm) {
            return redirect()->back()->withInput()->with('error', 'Password dan konfirmasi tidak cocok');
        }

        if (strlen($password) < 8) {
            return redirect()->back()->withInput()->with('error', 'Password minimal 8 karakter');
        }

        $authUserModel = new \Myth\Auth\Models\UserModel();
        $authUserModel->logResetAttempt(
            $email,
            $token,
            $this->request->getIPAddress(),
            (string) $this->request->getUserAgent()
        );

        $user = $authUserModel
            ->where('email', $email)
            ->where('reset_hash', $token)
            ->first();

        if (!$user) {
            return redirect()->back()->withInput()->with('error', 'Token tidak valid atau email tidak cocok');
        }

        if (!empty($user->reset_expires) && time() > $user->reset_expires->getTimestamp()) {
            return redirect()->back()->withInput()->with('error', 'Token reset sudah kedaluwarsa');
        }

        $user->password = $password;
        $user->reset_hash = null;
        $user->reset_at = date('Y-m-d H:i:s');
        $user->reset_expires = null;
        $user->force_pass_reset = false;
        $authUserModel->save($user);

        if ($this->request->hasHeader('HX-Request')) {
            $this->response->setHeader('HX-Redirect', '/login');
            session()->setFlashdata('success', 'Password berhasil direset. Silakan login.');
            return $this->response;
        }

        return redirect()->to('/login')->with('success', 'Password berhasil direset. Silakan login.');
    }
}
