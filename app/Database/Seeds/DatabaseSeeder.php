<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Ordered, idempotent bootstrap for a working app dataset.
 *
 *   php spark db:seed DatabaseSeeder
 *
 * Does NOT wipe tables. Safe-ish for production if underlying seeders
 * are idempotent (LokasiSeeder / Auth* are). Pegawai/Siswa may still
 * insert demo rows — prefer lokasi:normalize / db:cleanup on prod.
 */
class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // 1) Auth groups/permissions (skip if already present)
        $this->call('AuthGroupsSeeder');
        $this->call('AuthPermissionsSeeder');
        $this->call('AuthGroupsPermissionsSeeder');

        // 2) Locations FIRST — pegawai FKs depend on them
        $this->call('LokasiSeeder');

        // 3) Jabatan
        $this->call('JabatanSeeder');

        // 4) Admin user (if missing)
        $this->call('AdminUserSeeder');

        // 5) Demo pegawai/users only in development
        if (env('CI_ENVIRONMENT') === 'development') {
            $this->call('PegawaiSeeder');
            $this->call('UsersSeeder');
            $this->call('AuthGroupsUsersSeeder');
        }
    }
}
