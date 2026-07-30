<?php
namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class VerifyPasswords extends BaseCommand
{
    protected $group = 'O-Present';
    protected $name = 'opresent:verify-passwords';
    protected $description = 'Verify password hashes for all users';

    public function run(array $params)
    {
        $db = \Config\Database::connect();

        $users = $db->table('users')->select('id, email, username, password_hash, active, id_pegawai')->get()->getResultArray();

        CLI::write('=== USERS PASSWORD VERIFICATION ===', 'yellow');

        $passwords = ['admin123', 'password123', 'UjiSiswa@2026'];

        foreach ($users as $u) {
            CLI::write("id={$u['id']} email={$u['email']} user={$u['username']} active={$u['active']} id_pegawai={$u['id_pegawai']}", 'cyan');
            foreach ($passwords as $pw) {
                $ok = \Myth\Auth\Password::verify($pw, $u['password_hash']);
                CLI::write("  {$pw} => " . ($ok ? '✓ MATCH' : '✗ NO MATCH'), $ok ? 'green' : 'red');
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

        CLI::write(PHP_EOL . '=== PEGAWAI (id, nip, nama, jabatan) ===', 'yellow');
        $pegawai = $db->table('pegawai')
            ->select('pegawai.id, nip, nama, id_jabatan, jabatan.jabatan as nama_jabatan')
            ->join('jabatan', 'jabatan.id = pegawai.id_jabatan', 'left')
            ->get()->getResultArray();
        foreach ($pegawai as $p) {
            CLI::write("  id={$p['id']} nip={$p['nip']} nama={$p['nama']} jabatan={$p['nama_jabatan']}", 'cyan');
        }

        CLI::write(PHP_EOL . '=== HASH GENERATION TEST ===', 'yellow');
        $correctHash = \Myth\Auth\Password::hash('admin123');
        $wrongHash = password_hash('admin123', PASSWORD_BCRYPT, ['cost' => 10]);
        CLI::write("Myth\Auth\Password::hash('admin123') = $correctHash", 'green');
        CLI::write("password_hash('admin123')          = $wrongHash", 'red');
        CLI::write("Myth verify against Myth hash: " . (\Myth\Auth\Password::verify('admin123', $correctHash) ? 'YES' : 'NO'), 'cyan');
        CLI::write("Myth verify against plain hash: " . (\Myth\Auth\Password::verify('admin123', $wrongHash) ? 'YES' : 'NO'), 'cyan');
        CLI::write("plain verify against Myth hash: " . (password_verify('admin123', $correctHash) ? 'YES' : 'NO'), 'cyan');
        CLI::write("plain verify against plain hash: " . (password_verify('admin123', $wrongHash) ? 'YES' : 'NO'), 'cyan');

        // Show the raw admin hash from DB for comparison
        $adminRow = $db->table('users')->where('id', 1)->get()->getRow();
        if ($adminRow) {
            CLI::write(PHP_EOL . '=== ADMIN HASH IN DB ===', 'yellow');
            CLI::write("hash: {$adminRow->password_hash}", 'cyan');
            CLI::write("Myth verify: " . (\Myth\Auth\Password::verify('admin123', $adminRow->password_hash) ? 'YES' : 'NO'), 'cyan');
            CLI::write("plain verify: " . (password_verify('admin123', $adminRow->password_hash) ? 'YES' : 'NO'), 'cyan');
        }
    }
}
