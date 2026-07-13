<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class AuthGroupsUsersSeeder extends Seeder
{
    public function run()
    {
        $data = [
            [
                'group_id'          => 1,
                'user_id'           => 1,
            ],
            [
                'group_id'          => 2,
                'user_id'           => 2,
            ],
            [
                'group_id'          => 3,
                'user_id'           => 3,
            ],
        ];

        foreach ($data as $row) {
            $this->db->table('auth_groups_users')->insert($row);
        }
    }
}
