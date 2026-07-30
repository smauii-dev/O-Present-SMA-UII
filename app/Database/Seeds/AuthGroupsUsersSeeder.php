<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class AuthGroupsUsersSeeder extends Seeder
{
    public function run()
    {
        // Get user IDs from database
        $users = $this->db->table('users')->select('id, email')->get()->getResultArray();
        $userMap = [];
        foreach ($users as $user) {
            $userMap[$user['email']] = $user['id'];
        }

        $data = [
            // Admin users
            ['group_id' => 1, 'user_id' => $userMap['admin@smauiiyk.sch.id'] ?? 1], // admin
            
            // Admin group - tamani@present.com
            ['group_id' => 2, 'user_id' => $userMap['tamani@present.com'] ?? 2],
            
            // Pegawai group - students and staff
            ['group_id' => 3, 'user_id' => $userMap['admin@smauiiyk.sch.id'] ?? 1], // admin also in pegawai
            ['group_id' => 3, 'user_id' => $userMap['hanif.hasan9@gmail.com'] ?? 3],
            ['group_id' => 3, 'user_id' => $userMap['siswa1@gmail.com'] ?? 4],
            ['group_id' => 3, 'user_id' => $userMap['siswa2@gmail.com'] ?? 5],
            ['group_id' => 3, 'user_id' => $userMap['siswa3@gmail.com'] ?? 6],
            ['group_id' => 3, 'user_id' => $userMap['siswa4@gmail.com'] ?? 7],
            ['group_id' => 3, 'user_id' => $userMap['siswa5@gmail.com'] ?? 8],
            ['group_id' => 3, 'user_id' => $userMap['siswa6@gmail.com'] ?? 9],
            ['group_id' => 3, 'user_id' => $userMap['siswa7@gmail.com'] ?? 10],
            ['group_id' => 3, 'user_id' => $userMap['siswa8@gmail.com'] ?? 11],
            ['group_id' => 3, 'user_id' => $userMap['siswa9@gmail.com'] ?? 11],
            ['group_id' => 3, 'user_id' => $userMap['siswa10@gmail.com'] ?? 12],
            ['group_id' => 3, 'user_id' => $userMap['siswa11@gmail.com'] ?? 13],
            ['group_id' => 3, 'user_id' => $userMap['siswa12@gmail.com'] ?? 14],
            ['group_id' => 3, 'user_id' => $userMap['siswa13@gmail.com'] ?? 15],
            ['group_id' => 3, 'user_id' => $userMap['siswa14@gmail.com'] ?? 16],
            ['group_id' => 3, 'user_id' => $userMap['siswa15@gmail.com'] ?? 17],
            ['group_id' => 3, 'user_id' => $userMap['siswa17@gmail.com'] ?? 19],
            ['group_id' => 3, 'user_id' => $userMap['siswa18@gmail.com'] ?? 20],
            ['group_id' => 3, 'user_id' => $userMap['siswa20@gmail.com'] ?? 21],
            ['group_id' => 3, 'user_id' => $userMap['siswa22@gmail.com'] ?? 22],
            ['group_id' => 3, 'user_id' => $userMap['siswa23@gmail.com'] ?? 22],
            ['group_id' => 3, 'user_id' => $userMap['siswa25@gmail.com'] ?? 23],
            ['group_id' => 3, 'user_id' => $userMap['siswa27@gmail.com'] ?? 25],
            ['group_id' => 3, 'user_id' => $userMap['siswa28@gmail.com'] ?? 26],
            ['group_id' => 3, 'user_id' => $userMap['siswa29@gmail.com'] ?? 27],
            ['group_id' => 3, 'user_id' => $userMap['siswa30@gmail.com'] ?? 27],
            ['group_id' => 3, 'user_id' => $userMap['siswa31@gmail.com'] ?? 28],
            ['group_id' => 3, 'user_id' => $userMap['siswa33@gmail.com'] ?? 29],
            ['group_id' => 3, 'user_id' => $userMap['siswa36@gmail.com'] ?? 29],
            ['group_id' => 3, 'user_id' => $userMap['siswa38@gmail.com'] ?? 30],
            ['group_id' => 3, 'user_id' => $userMap['siswa41@gmail.com'] ?? 31],
            ['group_id' => 3, 'user_id' => $userMap['siswa43@gmail.com'] ?? 31],
            ['group_id' => 3, 'user_id' => $userMap['siswa44@gmail.com'] ?? 31],
        ];

        // Filter out entries where user_id is null
        $data = array_filter($data, function($item) {
            return !empty($item['user_id']);
        });

        // Using Query Builder
        $this->db->table('auth_groups_users')->insertBatch(array_values($data));
    }
}