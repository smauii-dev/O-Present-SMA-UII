<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class UsersSeeder extends Seeder
{
    public function run()
    {
        $data = [
            [
                'id_pegawai'        => '1',
                'email'             => 'admin@smauiiyk.sch.id',
                'username'          => 'admin',
                'password_hash'     => '$2y$10$.g0bOzk.wZdSJS6/QwQX7.xgvxYxESFH7r/GuDZyFWcPLGq2fqXTe',
                'active'            => '1',
                'force_pass_reset'  => 1,
            ],
            [
                'id_pegawai'        => '3',
                'email'             => 'jaya@present.com',
                'username'          => 'jayaputra',
                'password_hash'     => '$2y$10$EdFcZIe2FO5BFecVBHB58e0zI1aLswPtOKZkaE.Fw2oA/caUwbm7q',
                'active'            => '1',
                'force_pass_reset'  => 1,
            ],
            [
                'id_pegawai'        => '4',
                'email'             => 'tamani@present.com',
                'username'          => 'tamanindah',
                'password_hash'     => '$2y$10$EdFcZIe2FO5BFecVBHB58e0zI1aLswPtOKZkaE.Fw2oA/caUwbm7q',
                'active'            => '1',
                'force_pass_reset'  => 1,
            ],
            [
                'id_pegawai'        => '5',
                'email'             => 'hanif.hasan9@gmail.com',
                'username'          => 'choland',
                'password_hash'     => '$2y$10$HslMKgwyZ/4gR7qAfJRUS.WFKiZaT58kwReAZQOQHZdtX3tw4wsAG',
                'active'            => '1',
                'force_pass_reset'  => 1,
            ],
        ];

        foreach ($data as $user) {
            // Skip if email already exists
            $existing = $this->db->table('users')->where('email', $user['email'])->get()->getRow();
            if ($existing) {
                echo "Skipping email {$user['email']} (already exists)" . PHP_EOL;
                continue;
            }

            $this->db->table('users')->insert($user);
            echo "Created user: {$user['email']} - {$user['username']}" . PHP_EOL;
        }
    }
}