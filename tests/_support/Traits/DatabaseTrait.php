<?php
namespace Tests\Support\Traits;

trait DatabaseTrait
{
    protected function createTestTables(): void
    {
        $db = \Config\Database::connect();

        $db->query('CREATE TABLE IF NOT EXISTS jabatan (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            jabatan VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL,
            deleted_at TIMESTAMP NULL
        )');

        $db->query('CREATE TABLE IF NOT EXISTS lokasi_presensi (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nama_lokasi VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL,
            alamat_lokasi VARCHAR(255) NOT NULL,
            tipe_lokasi VARCHAR(50) NOT NULL,
            latitude VARCHAR(50) NOT NULL,
            longitude VARCHAR(50) NOT NULL,
            radius INTEGER NOT NULL,
            zona_waktu VARCHAR(100) NOT NULL,
            jam_masuk TIME NOT NULL,
            jam_pulang TIME NOT NULL,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL,
            deleted_at TIMESTAMP NULL
        )');

        $db->query('CREATE TABLE IF NOT EXISTS pegawai (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nip VARCHAR(50) NOT NULL UNIQUE,
            id_jabatan INTEGER NOT NULL,
            id_lokasi_presensi INTEGER NOT NULL,
            nama VARCHAR(255) NOT NULL,
            jenis_kelamin VARCHAR(20) NOT NULL,
            alamat VARCHAR(255) NOT NULL,
            no_handphone VARCHAR(255) NOT NULL,
            foto VARCHAR(255) NOT NULL,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL,
            deleted_at TIMESTAMP NULL,
            FOREIGN KEY (id_jabatan) REFERENCES jabatan(id) ON DELETE CASCADE ON UPDATE CASCADE,
            FOREIGN KEY (id_lokasi_presensi) REFERENCES lokasi_presensi(id) ON DELETE CASCADE ON UPDATE CASCADE
        )');

        $db->query('CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            id_pegawai INTEGER,
            email VARCHAR(255) NOT NULL UNIQUE,
            username VARCHAR(30) UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            reset_hash VARCHAR(255),
            reset_at TIMESTAMP NULL,
            reset_expires TIMESTAMP NULL,
            activate_hash VARCHAR(255),
            status VARCHAR(255),
            status_message VARCHAR(255),
            active SMALLINT NOT NULL DEFAULT 0,
            force_pass_reset SMALLINT NOT NULL DEFAULT 0,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL,
            deleted_at TIMESTAMP NULL,
            FOREIGN KEY (id_pegawai) REFERENCES pegawai(id) ON DELETE CASCADE
        )');

        $db->query('CREATE TABLE IF NOT EXISTS presensi (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            id_pegawai INTEGER NOT NULL,
            tanggal_masuk DATE NOT NULL,
            jam_masuk TIME NOT NULL,
            foto_masuk VARCHAR(255) NOT NULL,
            tanggal_keluar DATE,
            jam_keluar TIME,
            foto_keluar VARCHAR(255),
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL,
            deleted_at TIMESTAMP NULL,
            FOREIGN KEY (id_pegawai) REFERENCES pegawai(id) ON DELETE CASCADE ON UPDATE CASCADE
        )');

        $db->query('CREATE TABLE IF NOT EXISTS ketidakhadiran (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            id_pegawai INTEGER NOT NULL,
            tipe_ketidakhadiran VARCHAR(50) NOT NULL,
            tanggal_mulai DATE NOT NULL,
            tanggal_berakhir DATE NOT NULL,
            deskripsi VARCHAR(255) NOT NULL,
            file VARCHAR(255),
            status_pengajuan VARCHAR(20) NOT NULL,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL,
            deleted_at TIMESTAMP NULL,
            FOREIGN KEY (id_pegawai) REFERENCES pegawai(id) ON DELETE CASCADE ON UPDATE CASCADE
        )');

        $db->query('CREATE TABLE IF NOT EXISTS auth_logins (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            ip_address VARCHAR(255),
            email VARCHAR(255),
            user_id INTEGER,
            date TIMESTAMP NOT NULL,
            success SMALLINT NOT NULL
        )');

        $db->query('CREATE TABLE IF NOT EXISTS auth_tokens (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            selector VARCHAR(255) NOT NULL,
            hashedValidator VARCHAR(255) NOT NULL,
            user_id INTEGER NOT NULL,
            expires TIMESTAMP NOT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )');

        $db->query('CREATE TABLE IF NOT EXISTS auth_reset_attempts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            email VARCHAR(255) NOT NULL,
            ip_address VARCHAR(255) NOT NULL,
            user_agent VARCHAR(255) NOT NULL,
            token VARCHAR(255),
            created_at TIMESTAMP NOT NULL
        )');

        $db->query('CREATE TABLE IF NOT EXISTS auth_activation_attempts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            ip_address VARCHAR(255) NOT NULL,
            user_agent VARCHAR(255) NOT NULL,
            token VARCHAR(255),
            created_at TIMESTAMP NOT NULL
        )');

        $db->query('CREATE TABLE IF NOT EXISTS auth_groups (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(255) NOT NULL,
            description VARCHAR(255) NOT NULL
        )');

        $db->query('CREATE TABLE IF NOT EXISTS auth_permissions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(255) NOT NULL,
            description VARCHAR(255) NOT NULL
        )');

        $db->query('CREATE TABLE IF NOT EXISTS auth_groups_users (
            group_id INTEGER NOT NULL,
            user_id INTEGER NOT NULL,
            PRIMARY KEY (group_id, user_id),
            FOREIGN KEY (group_id) REFERENCES auth_groups(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )');

        $db->query('CREATE TABLE IF NOT EXISTS auth_groups_permissions (
            group_id INTEGER NOT NULL,
            permission_id INTEGER NOT NULL,
            PRIMARY KEY (group_id, permission_id),
            FOREIGN KEY (group_id) REFERENCES auth_groups(id) ON DELETE CASCADE,
            FOREIGN KEY (permission_id) REFERENCES auth_permissions(id) ON DELETE CASCADE
        )');
    }

    protected function seedTestData(): void
    {
        $this->truncateAll();
        $this->createTestTables();
        $db = \Config\Database::connect();

        $db->table('jabatan')->insert(['jabatan' => 'Chief Executive Officer', 'slug' => 'chief-executive-officer', 'created_at' => date('Y-m-d H:i:s')]);
        $db->table('jabatan')->insert(['jabatan' => 'Sales Lead', 'slug' => 'sales-lead', 'created_at' => date('Y-m-d H:i:s')]);
        $db->table('jabatan')->insert(['jabatan' => 'Student', 'slug' => 'student', 'created_at' => date('Y-m-d H:i:s')]);

        $db->table('lokasi_presensi')->insert([
            'nama_lokasi' => 'SMA UII Yogyakarta',
            'slug' => 'sma-uii-yogyakarta',
            'alamat_lokasi' => 'Jl. Sisingamangaraja No. 58, Yogyakarta',
            'tipe_lokasi' => 'Main Office',
            'latitude' => '-7.7364',
            'longitude' => '110.4431',
            'radius' => 500,
            'zona_waktu' => 'Asia/Jakarta',
            'jam_masuk' => '08:00',
            'jam_pulang' => '15:30',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $db->table('pegawai')->insert([
            'nip' => 'PEG-0001', 'id_jabatan' => 1, 'id_lokasi_presensi' => 1,
            'nama' => 'Jaya Wahyudi Putra', 'jenis_kelamin' => 'Male',
            'alamat' => 'Jl. Test', 'no_handphone' => '081234567891', 'foto' => 'default.jpg',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $db->table('pegawai')->insert([
            'nip' => 'PEG-0002', 'id_jabatan' => 2, 'id_lokasi_presensi' => 1,
            'nama' => 'Tamani Indah Permata', 'jenis_kelamin' => 'Female',
            'alamat' => 'Jl. Test 2', 'no_handphone' => '081281010191', 'foto' => 'default.jpg',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $db->table('pegawai')->insert([
            'nip' => 'PEG-0003', 'id_jabatan' => 3, 'id_lokasi_presensi' => 1,
            'nama' => 'Christoper Holand', 'jenis_kelamin' => 'Male',
            'alamat' => 'Jl. Test 3', 'no_handphone' => '081287761290', 'foto' => 'default.jpg',
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $userModel = new \App\Models\UsersModel();
        $hash = $userModel->hashPassword('123456');

        $db->table('users')->insert([
            'id_pegawai' => 1, 'email' => 'jaya@test.com', 'username' => 'jayaputra',
            'password_hash' => $hash, 'active' => 1, 'created_at' => date('Y-m-d H:i:s'),
        ]);
        $db->table('users')->insert([
            'id_pegawai' => 2, 'email' => 'tamani@test.com', 'username' => 'tamanindah',
            'password_hash' => $hash, 'active' => 1, 'created_at' => date('Y-m-d H:i:s'),
        ]);
        $db->table('users')->insert([
            'id_pegawai' => 3, 'email' => 'choland@test.com', 'username' => 'choland',
            'password_hash' => $hash, 'active' => 1, 'created_at' => date('Y-m-d H:i:s'),
        ]);

        $db->table('auth_groups')->insert(['id' => 1, 'name' => 'head', 'description' => 'Senior management staff']);
        $db->table('auth_groups')->insert(['id' => 2, 'name' => 'admin', 'description' => 'Manages and oversees employee attendance and data']);
        $db->table('auth_groups')->insert(['id' => 3, 'name' => 'employee', 'description' => 'Staff members who record daily attendance']);

        $db->table('auth_groups_users')->insert(['group_id' => 1, 'user_id' => 1]);
        $db->table('auth_groups_users')->insert(['group_id' => 2, 'user_id' => 2]);
        $db->table('auth_groups_users')->insert(['group_id' => 3, 'user_id' => 3]);

        $db->table('auth_permissions')->insert(['id' => 1, 'name' => 'manage_data', 'description' => 'Manage attendance data and employee information']);
        $db->table('auth_permissions')->insert(['id' => 2, 'name' => 'fill_attendance', 'description' => 'View attendance data and record attendance']);
        $db->table('auth_permissions')->insert(['id' => 3, 'name' => 'manage_leave_requests', 'description' => 'Approve or reject employee absence requests']);

        $db->table('auth_groups_permissions')->insert(['group_id' => 1, 'permission_id' => 1]);
        $db->table('auth_groups_permissions')->insert(['group_id' => 1, 'permission_id' => 2]);
        $db->table('auth_groups_permissions')->insert(['group_id' => 2, 'permission_id' => 2]);
        $db->table('auth_groups_permissions')->insert(['group_id' => 3, 'permission_id' => 1]);
        $db->table('auth_groups_permissions')->insert(['group_id' => 3, 'permission_id' => 3]);
    }

    protected function seedPresensiData(): void
    {
        $db = \Config\Database::connect();

        $db->table('presensi')->insert([
            'id_pegawai' => 1, 'tanggal_masuk' => date('Y-m-d'),
            'jam_masuk' => '08:00:00', 'foto_masuk' => 'clock-in-test.jpg',
            'tanggal_keluar' => date('Y-m-d'),
            'jam_keluar' => '15:30:00', 'foto_keluar' => 'clock-out-test.jpg',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    protected function seedKetidakhadiranData(): void
    {
        $db = \Config\Database::connect();

        $db->table('ketidakhadiran')->insert([
            'id_pegawai' => 3, 'tipe_ketidakhadiran' => 'SAKIT',
            'tanggal_mulai' => date('Y-m-d'), 'tanggal_berakhir' => date('Y-m-d'),
            'deskripsi' => 'Test sick leave', 'file' => 'test.pdf',
            'status_pengajuan' => 'PENDING', 'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    protected function truncateAll(): void
    {
        $db = \Config\Database::connect();
        $tables = [
            'auth_groups_users', 'auth_groups_permissions', 'auth_permissions',
            'auth_groups', 'auth_tokens', 'auth_logins',
            'auth_reset_attempts', 'auth_activation_attempts', 'email_tokens',
            'users', 'presensi', 'ketidakhadiran', 'pegawai', 'lokasi_presensi', 'jabatan',
        ];

        foreach ($tables as $table) {
            try { $db->query("DROP TABLE IF EXISTS {$table}"); } catch (\Exception $e) {}
        }
    }
}