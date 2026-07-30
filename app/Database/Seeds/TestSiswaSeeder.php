<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * One dedicated student-level test account for production / staging smoke tests.
 *
 *   php spark db:seed TestSiswaSeeder
 *
 * Credentials (intentionally simple for QA — change after testing if needed):
 *   username : uji.siswa
 *   email    : uji.siswa@smauiiyk.sch.id
 *   password : UjiSiswa@2026
 *   role     : pegawai (same access level as siswa in this app)
 *   lokasi   : SMA UII Yogyakarta (canonical campus pin)
 */
class TestSiswaSeeder extends Seeder
{
    public const USERNAME = 'uji.siswa';
    public const EMAIL    = 'uji.siswa@smauiiyk.sch.id';
    public const PASSWORD = 'UjiSiswa@2026';
    public const NIP      = 'UJI-SISWA-001';

    public function run()
    {
        helper('geo');

        // 1) Ensure campus location
        $lokasi = $this->db->table('lokasi_presensi')
            ->where('slug', 'sma-uii-yogyakarta')
            ->get()
            ->getRow();

        if (! $lokasi) {
            $service = new \App\Services\LokasiService();
            $service->ensureCanonicalLocations();
            $lokasi = $this->db->table('lokasi_presensi')
                ->where('slug', 'sma-uii-yogyakarta')
                ->get()
                ->getRow();
        }

        if (! $lokasi) {
            echo "TestSiswaSeeder ABORT: lokasi sma-uii-yogyakarta missing\n";

            return;
        }

        // 2) Ensure jabatan Siswa
        $jabatan = $this->db->table('jabatan')->where('slug', 'siswa')->get()->getRow();
        if (! $jabatan) {
            $this->db->table('jabatan')->insert([
                'jabatan' => 'Siswa',
                'slug'    => 'siswa',
            ]);
            $jabatanId = (int) $this->db->insertID();
        } else {
            $jabatanId = (int) $jabatan->id;
        }

        // 3) Pegawai row
        $pegawai = $this->db->table('pegawai')->where('nip', self::NIP)->get()->getRow();
        if ($pegawai) {
            $this->db->table('pegawai')->where('id', $pegawai->id)->update([
                'nama'               => 'Uji Siswa (Test)',
                'id_jabatan'         => $jabatanId,
                'id_lokasi_presensi' => (int) $lokasi->id,
                'jenis_kelamin'      => 'Laki-laki',
                'alamat'             => 'Akun testing production — boleh dihapus',
                'no_handphone'       => '-',
                'foto'               => 'default.jpg',
            ]);
            $pegawaiId = (int) $pegawai->id;
        } else {
            $this->db->table('pegawai')->insert([
                'nip'                => self::NIP,
                'nama'               => 'Uji Siswa (Test)',
                'id_jabatan'         => $jabatanId,
                'id_lokasi_presensi' => (int) $lokasi->id,
                'jenis_kelamin'      => 'Laki-laki',
                'alamat'             => 'Akun testing production — boleh dihapus',
                'no_handphone'       => '-',
                'foto'               => 'default.jpg',
            ]);
            $pegawaiId = (int) $this->db->insertID();
        }

        // 4) Password (Myth-Auth compatible)
        if (class_exists(\Myth\Auth\Password::class)) {
            $hash = \Myth\Auth\Password::hash(self::PASSWORD);
        } else {
            $hash = password_hash(
                base64_encode(hash('sha384', self::PASSWORD, true)),
                PASSWORD_DEFAULT
            );
        }

        $user = $this->db->table('users')
            ->groupStart()
                ->where('username', self::USERNAME)
                ->orWhere('email', self::EMAIL)
            ->groupEnd()
            ->get()
            ->getRow();

        if ($user) {
            $this->db->table('users')->where('id', $user->id)->update([
                'id_pegawai'    => $pegawaiId,
                'email'         => self::EMAIL,
                'username'      => self::USERNAME,
                'password_hash' => $hash,
                'active'        => 1,
            ]);
            $userId = (int) $user->id;
            echo "Updated user #" . $userId . "\n";
        } else {
            $this->db->table('users')->insert([
                'id_pegawai'    => $pegawaiId,
                'email'         => self::EMAIL,
                'username'      => self::USERNAME,
                'password_hash' => $hash,
                'active'        => 1,
            ]);
            $userId = (int) $this->db->insertID();
            echo "Created user #" . $userId . "\n";
        }

        // 5) Role = pegawai (siswa level in this app)
        $group = $this->db->table('auth_groups')->where('name', 'pegawai')->get()->getRow();
        if (! $group) {
            $this->db->table('auth_groups')->insert([
                'name'        => 'pegawai',
                'description' => 'Pegawai / siswa — akses presensi',
            ]);
            $groupId = (int) $this->db->insertID();
        } else {
            $groupId = (int) $group->id;
        }

        $link = $this->db->table('auth_groups_users')
            ->where('user_id', $userId)
            ->where('group_id', $groupId)
            ->get()
            ->getRow();

        if (! $link) {
            $this->db->table('auth_groups_users')->insert([
                'user_id'  => $userId,
                'group_id' => $groupId,
            ]);
        }

        echo "========================================\n";
        echo "TEST SISWA ACCOUNT READY\n";
        echo "  username : " . self::USERNAME . "\n";
        echo "  email    : " . self::EMAIL . "\n";
        echo "  password : " . self::PASSWORD . "\n";
        echo "  role     : pegawai (siswa-level)\n";
        echo "  lokasi   : {$lokasi->nama_lokasi}\n";
        echo "  lat/lng  : {$lokasi->latitude}, {$lokasi->longitude}\n";
        echo "  radius   : {$lokasi->radius} m\n";
        echo "========================================\n";
    }
}
