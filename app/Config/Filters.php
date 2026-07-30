<?php

namespace Config;

use CodeIgniter\Config\Filters as BaseFilters;
use CodeIgniter\Filters\Cors;
use CodeIgniter\Filters\CSRF;
use CodeIgniter\Filters\DebugToolbar;
use CodeIgniter\Filters\ForceHTTPS;
use CodeIgniter\Filters\Honeypot;
use CodeIgniter\Filters\InvalidChars;
use CodeIgniter\Filters\PageCache;
use CodeIgniter\Filters\PerformanceMetrics;
use CodeIgniter\Filters\SecureHeaders;

class Filters extends BaseFilters
{
    public array $aliases = [
        'csrf'          => CSRF::class,
        'toolbar'       => DebugToolbar::class,
        'honeypot'      => Honeypot::class,
        'invalidchars'  => InvalidChars::class,
        'secureheaders' => SecureHeaders::class,
        'cors'          => Cors::class,
        'forcehttps'    => ForceHTTPS::class,
        'pagecache'     => PageCache::class,
        'performance'   => PerformanceMetrics::class,
        'login'         => \Myth\Auth\Filters\LoginFilter::class,
        'role'          => \App\Filters\RoleFilter::class,
        'permission'    => \Myth\Auth\Filters\PermissionFilter::class,
        'apiCors'       => \App\Filters\ApiCors::class,
        'apiSessionAuth' => \App\Filters\ApiSessionAuth::class,
        'debugbarXhrGuard' => \App\Filters\DebugbarXhrGuard::class,
        'trustProxy'    => \App\Filters\TrustProxy::class,
        'forcePasswordReset' => \App\Filters\ForcePasswordReset::class,
    ];

    public array $required = [
        'before' => [
            'pagecache',
        ],
        'after' => [
            'pagecache',
            'performance',
            'toolbar',
            'debugbarXhrGuard',
        ],
    ];

    public array $globals = [
        'before' => [
            'trustProxy',
            'apiCors',
            'forcePasswordReset',
        ],
        'after' => [
            'apiCors',
        ],
    ];

    public array $methods = [];

    public array $filters = [];
}
