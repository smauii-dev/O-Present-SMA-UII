<?php

namespace Config;

use Myth\Auth\Config\Auth as MythAuthConfig;

class Auth extends MythAuthConfig
{
    /**
     * Default group for new users.
     *
     * @var list<string>|string
     */
    public $defaultUserGroup = ['pegawai'];

    /**
     * Auth page views — use Myth/Auth vendor views (App web views removed; SPA owns UI).
     *
     * @var array<string, string>
     */
    public $views = [
        'login'           => 'Myth\\Auth\\Views\\login',
        'register'        => 'Myth\\Auth\\Views\\register',
        'forgot'          => 'Myth\\Auth\\Views\\forgot',
        'reset'           => 'Myth\\Auth\\Views\\reset',
        'emailForgot'     => 'Myth\\Auth\\Views\\emails\\forgot',
        'emailActivation' => 'Myth\\Auth\\Views\\emails\\activation',
    ];

    /**
     * Hide registration forms.
     *
     * @var string|null
     */
    public $allowRegistration = null;
}
