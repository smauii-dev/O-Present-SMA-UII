<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class JabatanSeeder extends Seeder
{
    public function run()
    {
        $data = [
            [
                'jabatan' => 'Chief Executive Officer',
                'slug'    => 'chief-executive-officer',
            ],
            [
                'jabatan' => 'Sales Lead',
                'slug'    => 'sales-lead',
            ],
            [
                'jabatan' => 'Siswa',
                'slug'    => 'siswa',
            ],
            [
                'jabatan' => 'Administrator',
                'slug'    => 'administrator',
            ],
        ];

        foreach ($data as $row) {
            $exists = $this->db->table('jabatan')->where('slug', $row['slug'])->get()->getRow();
            if ($exists) {
                $this->db->table('jabatan')->where('id', $exists->id)->update([
                    'jabatan' => $row['jabatan'],
                ]);
            } else {
                $this->db->table('jabatan')->insert($row);
            }
        }
    }
}
