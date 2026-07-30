<?php
namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class VerifyProdPasswords extends BaseCommand
{
    protected $group = 'O-Present';
    protected $name = 'opresent:verify-prod';
    protected $description = 'Verify production password hashes';

    public function run(array $params)
    {
        // Connect to production DB directly
        $db = \Config\Database::connect();

        $users = $db->table('users')->select('id, email, username, password_hash, active, id_pegawai')->get()->getResultArray();

        CLI::write('=== PRODUCTION USERS PASSWORD VERIFICATION ===', 'yellow');

        $passwords = ['admin123', 'password123', 'UjiSiswa@2026'];

        foreach ($users as $u) {
            CLI::write("id={$u['id']} email={$u['email']} user={$u['username']} active={$u['active']} id_pegawai={$u['id_pegawai']}", 'cyan');
            foreach ($passwords as $pw) {
                $ok = \Myth\Auth\Password::verify($pw, $u['password_hash']);
                CLI::write("  {$pw} => " . ($ok ? 'YES' : 'NO'), $ok ? 'green' : 'red');
            }
        }

        CLI::write(PHP_EOL . '=== AUTH GROUPS ASSIGNMENTS ===', 'yellow');
        $groups = $db->table('auth_groups')->get()->getResultArray();
        $groupNames = [];
        foreach ($groups as $g) $groupNames[$g['id']] = $g['name'];

        foreach ($users as $u) {
            $gu = $db->table('auth_groups_users')->where('user_id', $u['id'])->get()->getResultArray();
            $gnames = array_map(fn($g) => $groupNames[$g['group_id']] ?? "group_{$g['group_id']}", $gu);
            CLI::write("  {$u['email']} => groups: " . implode(', ', $gnames), 'cyan');
        }
    }
}
