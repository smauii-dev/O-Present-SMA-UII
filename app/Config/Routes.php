<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// ============================================================
// Web Routes (Twig server-rendered pages)
// ============================================================

// Public Routes
$routes->group('', ['namespace' => 'App\Controllers\Web'], static function ($routes) {
    // Splash entrypoint → client redirects to /overview
    $routes->get('/', 'Overview::splash');

    // Health check endpoint for integration testing
    $routes->get('health', 'Overview::health');

    // Auth
    $routes->get('login', 'Auth::login');
    $routes->post('login', 'Auth::loginAction');
    $routes->get('forgot-password', 'Auth::forgot');
    $routes->post('forgot-password', 'Auth::forgotAction');
    $routes->get('reset-password', 'Auth::reset');
    $routes->post('reset-password', 'Auth::resetAction');
    $routes->post('logout', 'Auth::logout');
});

// Profile Routes — uses forcePasswordReset filter (instead of login) so force_pass_reset
// users can access /profile to change their password without triggering Myth\Auth's broken
// RedirectException (CI4 4.7 removed the class Myth\Auth still references).
$routes->group('', ['namespace' => 'App\Controllers\Web', 'filter' => 'forcePasswordReset:requireAuth'], static function ($routes) {
    $routes->get('profile', 'Profile::index');
    $routes->post('profile/update', 'Profile::updateProfileAction');
    $routes->post('profile/photo', 'Profile::uploadPhotoAction');
    $routes->post('profile/password', 'Profile::changePasswordAction');
    $routes->post('profile/password/force', 'Profile::forceChangePasswordAction');
});

// Protected Routes (requires login)
$routes->group('', ['namespace' => 'App\Controllers\Web', 'filter' => 'login'], static function ($routes) {
    // Overview (app entrypoint after splash / login)
    $routes->get('overview', 'Overview::index');

    // Presence (Clock-in/Clock-out & Absence) — views/routes/presence
    $routes->get('presence', 'Presence::index');
    $routes->post('presence/clock-in', 'Presence::clockIn');
    $routes->post('presence/clock-out', 'Presence::clockOut');
    $routes->post('presence/reset', 'Presence::resetToday');

    // Absence — Izin/Cuti/Sakit
    $routes->get('absence', 'Absence::index');
    $routes->get('absence/fragment', 'Absence::fragment');
    $routes->get('absence/form', 'Absence::form');
    $routes->get('absence/form/(:num)', 'Absence::form/$1');
    $routes->post('absence', 'Absence::save');
    $routes->post('absence/(:num)', 'Absence::save/$1');
    $routes->delete('absence/(:num)', 'Absence::delete/$1');

    // Attendance Summary
    $routes->get('attendance-summary', 'AttendanceSummary::index');
    $routes->get('attendance-summary/fragment', 'AttendanceSummary::fragment');
    $routes->post('attendance-summary/export', 'AttendanceSummary::export');

    // Media Proxy
    $routes->get('media/(:any)', 'Media::serve/$1');

    // Admin Routes
    $routes->group('admin', ['filter' => 'role:admin,head'], static function ($routes) {
        // Pegawai
        $routes->get('pegawai', 'Pegawai::index');
        $routes->get('pegawai/fragment', 'Pegawai::fragment');
        $routes->get('pegawai/form', 'Pegawai::form');
        $routes->get('pegawai/form/(:num)', 'Pegawai::form/$1');
        $routes->get('pegawai/import-form', 'Pegawai::importForm');
        $routes->post('pegawai', 'Pegawai::save');
        $routes->post('pegawai/(:num)', 'Pegawai::save/$1');
        $routes->delete('pegawai/(:num)', 'Pegawai::delete/$1');
        $routes->post('pegawai/reset-password/(:num)', 'Pegawai::resetPassword/$1');

        // Jabatan
        $routes->get('jabatan', 'Jabatan::index');
        $routes->get('jabatan/fragment', 'Jabatan::fragment');
        $routes->get('jabatan/form', 'Jabatan::form');
        $routes->get('jabatan/form/(:num)', 'Jabatan::form/$1');
        $routes->post('jabatan', 'Jabatan::save');
        $routes->post('jabatan/(:num)', 'Jabatan::save/$1');
        $routes->delete('jabatan/(:num)', 'Jabatan::delete/$1');

        // Lokasi
        $routes->get('lokasi', 'Lokasi::index');
        $routes->get('lokasi/fragment', 'Lokasi::fragment');
        $routes->get('lokasi/form', 'Lokasi::form');
        $routes->get('lokasi/form/(:num)', 'Lokasi::form/$1');
        $routes->post('lokasi', 'Lokasi::save');
        $routes->post('lokasi/(:num)', 'Lokasi::save/$1');
        $routes->delete('lokasi/(:num)', 'Lokasi::delete/$1');

        // Absence Management (Admin)
        $routes->get('absence/admin', 'Absence::admin');
        $routes->get('absence/admin/fragment', 'Absence::adminFragment');
        $routes->post('absence/admin/status/(:num)', 'Absence::updateStatus/$1');
    });
});

// ============================================================
// API Routes (Pure JSON API)
// ============================================================
$routes->group('api', function ($routes) {
    // Public
    $routes->post('login', 'Api\Auth::login');
    $routes->post('forgot-password', 'Api\Auth::forgot');
    $routes->post('reset-password', 'Api\Auth::reset');

    // Protected — session-based auth
    $routes->group('', ['filter' => 'apiSessionAuth'], function ($routes) {
        // Auth
        $routes->post('logout', 'Api\Auth::logout');
        $routes->get('me', 'Api\Auth::me');

        // Dashboard
        $routes->get('dashboard', 'Api\Dashboard::index');

        // Presence (Clock in/out)
        $routes->get('presence/today', 'Api\Presence::today');
        $routes->post('presence/clock-in', 'Api\Presence::clockIn');
        $routes->post('presence/clock-out', 'Api\Presence::clockOut');
        $routes->get('presence/rekap', 'Api\Presence::rekap');
        $routes->post('presence/export/rekap', 'Api\Presence::exportRekap');
        $routes->post('presence/export/harian', 'Api\Presence::exportHarian');
        $routes->post('presence/export/bulanan', 'Api\Presence::exportBulanan');

        // Profile
        $routes->get('profile', 'Api\Profile::index');
        $routes->post('profile/update', 'Api\Profile::updateProfile');
        $routes->post('profile/photo', 'Api\Profile::uploadPhoto');
        $routes->post('profile/password', 'Api\Profile::changePassword');
        $routes->post('profile/password/force', 'Api\Profile::forceChangePassword');

        // Pegawai
        $routes->get('pegawai', 'Api\Pegawai::index');
        $routes->get('pegawai/(:num)', 'Api\Pegawai::detail/$1');
        $routes->post('pegawai', 'Api\Pegawai::store');
        $routes->put('pegawai/(:num)', 'Api\Pegawai::update/$1');
        $routes->delete('pegawai/(:num)', 'Api\Pegawai::delete/$1');
        $routes->post('pegawai/export', 'Api\Pegawai::exportExcel');
        $routes->post('pegawai/import', 'Api\Pegawai::importExcel');
        $routes->get('pegawai/template', 'Api\Pegawai::downloadTemplate');

        // Absence (Izin/Cuti/Sakit)
        $routes->get('absence', 'Api\Absence::index');
        $routes->get('absence/today', 'Api\Absence::today');
        $routes->post('absence', 'Api\Absence::store');
        $routes->get('absence/(:num)', 'Api\Absence::detail/$1');
        $routes->put('absence/(:num)', 'Api\Absence::update/$1');
        $routes->delete('absence/(:num)', 'Api\Absence::delete/$1');
        $routes->post('absence/export', 'Api\Absence::exportExcel');
    });

    // Admin-only
    $routes->group('admin', ['filter' => 'apiSessionAuth:admin,head'], function ($routes) {
        // Roles (auth_groups) for forms
        $routes->get('roles', 'Api\Admin\Role::index');

        // Jabatan
        $routes->get('jabatan', 'Api\Admin\Jabatan::index');
        $routes->get('jabatan/(:num)', 'Api\Admin\Jabatan::detail/$1');
        $routes->post('jabatan', 'Api\Admin\Jabatan::store');
        $routes->put('jabatan/(:num)', 'Api\Admin\Jabatan::update/$1');
        $routes->delete('jabatan/(:num)', 'Api\Admin\Jabatan::delete/$1');

        // Lokasi Presensi
        $routes->get('lokasi', 'Api\Admin\LokasiPresensi::index');
        $routes->get('lokasi/(:num)', 'Api\Admin\LokasiPresensi::detail/$1');
        $routes->post('lokasi', 'Api\Admin\LokasiPresensi::store');
        $routes->put('lokasi/(:num)', 'Api\Admin\LokasiPresensi::update/$1');
        $routes->delete('lokasi/(:num)', 'Api\Admin\LokasiPresensi::delete/$1');
        $routes->post('lokasi/export', 'Api\Admin\LokasiPresensi::exportExcel');

        // Absence (admin)
        $routes->get('absence/admin', 'Api\Admin\Absence::index');
        $routes->post('absence/admin/(:num)/status', 'Api\Admin\Absence::updateStatus/$1');
        $routes->post('absence/export', 'Api\Admin\Absence::exportExcel');
    });
});
