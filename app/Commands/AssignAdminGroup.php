<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\Database\Config;

class AssignAdminGroup extends BaseCommand
{
    protected $group       = 'user';
    protected $name        = 'user:assign_admin_group';
    protected $description = 'Assign admin user to admin group';
    protected $usage       = 'user:assign_admin_group';
    protected $arguments   = [];
    protected $options     = [];

    public function run(array $params)
    {
        $db = Config::connect();

        $user = $db->table('users')->where('email', 'admin@smauiiyk.sch.id')->get()->getRow();
        if (! $user) {
            echo 'Admin user not found' . PHP_EOL;
            return;
        }

        $group = $db->table('auth_groups')->where('name', 'admin')->get()->getRow();
        if (! $group) {
            echo 'Admin group not found' . PHP_EOL;
            return;
        }

        $existing = $db->table('auth_groups_users')
            ->where('user_id', $user->id)
            ->where('group_id', $group->id)
            ->get()->getRow();

        if ($existing) {
            echo 'Admin already in admin group' . PHP_EOL;
            return;
        }

        $db->table('auth_groups_users')->insert([
            'user_id'  => $user->id,
            'group_id' => $group->id,
        ]);

        echo 'Admin user assigned to admin group' . PHP_EOL;
    }
}
