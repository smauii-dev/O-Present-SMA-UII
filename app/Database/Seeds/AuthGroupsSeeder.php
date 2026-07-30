<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class AuthGroupsSeeder extends Seeder
{
    public function run()
    {
        $data = [
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

        foreach ($data as $group) {
            $exists = $this->db->table('auth_groups')->where('name', $group['name'])->get()->getRow();
            if ($exists) {
                $this->db->table('auth_groups')->where('id', $exists->id)->update([
                    'description' => $group['description'],
                ]);
            } else {
                $this->db->table('auth_groups')->insert($group);
            }
        }
    }
}
