<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use Myth\Auth\Password;

/**
 * Idempotent admin bootstrap. Links admin to SMA UII Yogyakarta lokasi.
 */
class AdminUserSeeder extends Seeder
{
    public function run()
    {
        helper('geo');

        $hash = class_exists(Password::class)
            ? Password::hash('admin123')
            : password_hash(base64_encode(hash('sha384', 'admin123', true)), PASSWORD_DEFAULT);

        // Ensure campus lokasi exists
        $lokasi = $this->db->table('lokasi_presensi')->where('slug', 'sma-uii-yogyakarta')->get()->getRow();
        if (! $lokasi) {
            $this->call('LokasiSeeder');
            $lokasi = $this->db->table('lokasi_presensi')->where('slug', 'sma-uii-yogyakarta')->get()->getRow();
        }
        if (! $lokasi) {
            echo "AdminUserSeeder: SMA UII lokasi missing, abort.\n";

            return;
        }

        $jabatan = $this->db->table('jabatan')->where('slug', 'administrator')->get()->getRow();
        if (! $jabatan) {
            $this->db->table('jabatan')->insert([
                'jabatan' => 'Administrator',
                'slug'    => 'administrator',
            ]);
            $jabatanId = (int) $this->db->insertID();
        } else {
            $jabatanId = (int) $jabatan->id;
        }

        $pegawai = $this->db->table('pegawai')->where('nip', '00000000')->get()->getRow();
        if (! $pegawai) {
            $this->db->table('pegawai')->insert([
                'nip'                => '00000000',
                'nama'               => 'Administrator',
                'id_jabatan'         => $jabatanId,
                'id_lokasi_presensi' => (int) $lokasi->id,
                'jenis_kelamin'      => 'Laki-laki',
                'alamat'             => '-',
                'no_handphone'       => '-',
                'foto'               => 'default.jpg',
            ]);
            $pegawaiId = (int) $this->db->insertID();
        } else {
            $pegawaiId = (int) $pegawai->id;
            $this->db->table('pegawai')->where('id', $pegawaiId)->update([
                'id_lokasi_presensi' => (int) $lokasi->id,
                'id_jabatan'         => $jabatanId,
            ]);
        }

        $user = $this->db->table('users')
            ->groupStart()
                ->where('email', 'admin@smauiiyk.sch.id')
                ->orWhere('username', 'admin')
            ->groupEnd()
            ->get()
            ->getRow();

        if ($user) {
            $this->db->table('users')->where('id', $user->id)->update([
                'id_pegawai'    => $pegawaiId,
                'email'         => 'admin@smauiiyk.sch.id',
                'username'      => 'admin',
                'password_hash' => $hash,
                'active'        => 1,
            ]);
            $userId = (int) $user->id;
        } else {
            $this->db->table('users')->insert([
                'id_pegawai'    => $pegawaiId,
                'email'         => 'admin@smauiiyk.sch.id',
                'username'      => 'admin',
                'password_hash' => $hash,
                'active'        => 1,
            ]);
            $userId = (int) $this->db->insertID();
        }

        $adminGroup = $this->db->table('auth_groups')->where('name', 'admin')->get()->getRow();
        if ($adminGroup) {
            $link = $this->db->table('auth_groups_users')
                ->where('user_id', $userId)
                ->where('group_id', $adminGroup->id)
                ->get()
                ->getRow();
            if (! $link) {
                $this->db->table('auth_groups_users')->insert([
                    'user_id'  => $userId,
                    'group_id' => $adminGroup->id,
                ]);
            }
        }

        echo "Admin ready: admin@smauiiyk.sch.id / admin123 (lokasi={$lokasi->nama_lokasi})\n";
    }
}
