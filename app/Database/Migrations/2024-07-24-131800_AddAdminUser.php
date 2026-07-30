<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Seeds baseline admin data. Fully idempotent so re-runs / partial failures
 * (duplicate NIP, missing id_pegawai after Myth\Auth) do not break migrate.
 */
class AddAdminUser extends Migration
{
    private const ADMIN_EMAIL    = 'admin@smauiiyk.sch.id';
    private const ADMIN_USERNAME = 'admin';
    private const ADMIN_NIP      = '00000000';
    private const ADMIN_PASSWORD = 'admin123';

    public function up()
    {
        $this->ensureUsersPegawaiColumn();

        $lokasiId  = $this->ensureLokasi();
        $jabatanId = $this->ensureJabatan();
        $pegawaiId = $this->ensurePegawai($jabatanId, $lokasiId);
        $userId    = $this->ensureUser($pegawaiId);
        $this->ensureAuthGroups();
        $this->ensureUserInGroup($userId, 'admin');
        $this->ensureUserInGroup($userId, 'pegawai');
    }

    public function down()
    {
        $user = $this->db->table('users')->where('email', self::ADMIN_EMAIL)->get()->getRow();
        if ($user) {
            $this->db->table('auth_groups_users')->where('user_id', $user->id)->delete();
            $this->db->table('users')->where('id', $user->id)->delete();
            if (! empty($user->id_pegawai)) {
                $this->db->table('pegawai')->where('id', $user->id_pegawai)->delete();
            }
        } else {
            $this->db->table('pegawai')->where('nip', self::ADMIN_NIP)->delete();
        }

        // Only remove baseline jabatan we created. Do NOT delete SMA UII lokasi
        // (shared canonical attendance points used by other rows).
        $this->db->table('jabatan')->where('slug', 'administrator')->delete();
    }

    private function ensureUsersPegawaiColumn(): void
    {
        if (! $this->db->tableExists('users') || $this->db->fieldExists('id_pegawai', 'users')) {
            return;
        }

        $this->forge->addColumn('users', [
            'id_pegawai' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
        ]);

        if (! $this->db->tableExists('pegawai')) {
            return;
        }

        try {
            $this->db->query(
                'ALTER TABLE users
                 ADD CONSTRAINT users_id_pegawai_foreign
                 FOREIGN KEY (id_pegawai) REFERENCES pegawai(id)
                 ON UPDATE CASCADE ON DELETE CASCADE'
            );
        } catch (\Throwable $e) {
            log_message('debug', 'AddAdminUser FK: ' . $e->getMessage());
        }
    }

    private function ensureLokasi(): int
    {
        // Prefer canonical campus point (never Jakarta dummy / "Gedung Pusat")
        helper('geo');
        $canonical = canonical_sma_uii_locations()[0]; // SMA UII Yogyakarta

        $existing = $this->db->table('lokasi_presensi')
            ->where('slug', $canonical['slug'])
            ->get()
            ->getRow();

        if ($existing) {
            // Keep coords in sync if an old row used wrong values
            $this->db->table('lokasi_presensi')->where('id', $existing->id)->update([
                'nama_lokasi'   => $canonical['nama_lokasi'],
                'alamat_lokasi' => $canonical['alamat_lokasi'],
                'latitude'      => $canonical['latitude'],
                'longitude'     => $canonical['longitude'],
                'radius'        => $canonical['radius'],
                'zona_waktu'    => $canonical['zona_waktu'],
                'jam_masuk'     => $canonical['jam_masuk'],
                'jam_pulang'    => $canonical['jam_pulang'],
                'tipe_lokasi'   => $canonical['tipe_lokasi'],
            ]);

            return (int) $existing->id;
        }

        $this->db->table('lokasi_presensi')->insert($canonical);

        return (int) $this->db->insertID();
    }

    private function ensureJabatan(): int
    {
        $existing = $this->db->table('jabatan')
            ->where('slug', 'administrator')
            ->get()
            ->getRow();

        if ($existing) {
            return (int) $existing->id;
        }

        $this->db->table('jabatan')->insert([
            'jabatan' => 'Administrator',
            'slug'    => 'administrator',
        ]);

        return (int) $this->db->insertID();
    }

    private function ensurePegawai(int $jabatanId, int $lokasiId): int
    {
        $existing = $this->db->table('pegawai')
            ->where('nip', self::ADMIN_NIP)
            ->get()
            ->getRow();

        if ($existing) {
            // Keep refs consistent if earlier partial run left orphans
            $this->db->table('pegawai')->where('id', $existing->id)->update([
                'id_jabatan'         => $jabatanId,
                'id_lokasi_presensi' => $lokasiId,
                'nama'               => 'Administrator',
            ]);

            return (int) $existing->id;
        }

        $this->db->table('pegawai')->insert([
            'nip'                => self::ADMIN_NIP,
            'nama'               => 'Administrator',
            'id_jabatan'         => $jabatanId,
            'id_lokasi_presensi' => $lokasiId,
            'jenis_kelamin'      => 'Laki-laki',
            'alamat'             => '-',
            'no_handphone'       => '-',
            'foto'               => 'default.jpg',
        ]);

        return (int) $this->db->insertID();
    }

    private function ensureUser(int $pegawaiId): int
    {
        $hash = $this->hashPassword(self::ADMIN_PASSWORD);

        $existing = $this->db->table('users')
            ->groupStart()
                ->where('email', self::ADMIN_EMAIL)
                ->orWhere('username', self::ADMIN_USERNAME)
                ->orWhere('id_pegawai', $pegawaiId)
            ->groupEnd()
            ->get()
            ->getRow();

        if ($existing) {
            $this->db->table('users')->where('id', $existing->id)->update([
                'id_pegawai'    => $pegawaiId,
                'email'         => self::ADMIN_EMAIL,
                'username'      => self::ADMIN_USERNAME,
                'password_hash' => $hash,
                'active'        => 1,
            ]);

            return (int) $existing->id;
        }

        $this->db->table('users')->insert([
            'id_pegawai'    => $pegawaiId,
            'email'         => self::ADMIN_EMAIL,
            'username'      => self::ADMIN_USERNAME,
            'password_hash' => $hash,
            'active'        => 1,
        ]);

        return (int) $this->db->insertID();
    }

    private function ensureAuthGroups(): void
    {
        $groups = [
            [
                'name'        => 'head',
                'description' => 'Unit manajerial tingkat tinggi yang bertanggung jawab memimpin, mengelola, dan mengkoordinasikan berbagai tim di organisasi untuk mencapai tujuan strategis.',
            ],
            [
                'name'        => 'admin',
                'description' => 'Mengelola dan mengawasi fungsi administratif sistem, termasuk manajemen pengguna dan hak akses, untuk mendukung operasional harian.',
            ],
            [
                'name'        => 'pegawai',
                'description' => 'Mencakup anggota yang fokus pada kehadiran dan aktivitas presensi, dengan akses terbatas untuk memastikan pencatatan dan pemantauan presensi yang akurat.',
            ],
        ];

        foreach ($groups as $group) {
            $exists = $this->db->table('auth_groups')->where('name', $group['name'])->get()->getRow();
            if (! $exists) {
                $this->db->table('auth_groups')->insert($group);
            }
        }
    }

    private function ensureUserInGroup(int $userId, string $groupName): void
    {
        $group = $this->db->table('auth_groups')->where('name', $groupName)->get()->getRow();
        if (! $group) {
            return;
        }

        $exists = $this->db->table('auth_groups_users')
            ->where('user_id', $userId)
            ->where('group_id', $group->id)
            ->get()
            ->getRow();

        if (! $exists) {
            $this->db->table('auth_groups_users')->insert([
                'user_id'  => $userId,
                'group_id' => $group->id,
            ]);
        }
    }

    /**
     * Match Myth\Auth + UsersModel hashing (sha384 prehash + password_hash).
     */
    private function hashPassword(string $password): string
    {
        if (class_exists(\Myth\Auth\Password::class)) {
            return \Myth\Auth\Password::hash($password);
        }

        return password_hash(
            base64_encode(hash('sha384', $password, true)),
            PASSWORD_DEFAULT
        );
    }
}
