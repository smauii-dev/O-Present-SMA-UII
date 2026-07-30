<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;

class FixAllPasswords extends BaseCommand
{
    protected $group       = 'O-Present';
    protected $name        = 'opresent:fix-passwords';
    protected $description = 'Fix ALL user passwords to use Myth\Auth compatible hashing';
    protected $usage       = 'opresent:fix-passwords [--dry-run] [--fix-groups]';

    public function run(array $params)
    {
        $db = Database::connect();
        $dryRun = in_array('--dry-run', $params);
        $fixGroups = in_array('--fix-groups', $params);

        if ($dryRun) {
            CLI::write('DRY RUN MODE — no changes will be made', 'yellow');
        }

        $passwordMap = [
            'admin@smauiiyk.sch.id'       => ['admin123', 'Admin'],
            'jaya@present.com'             => ['admin123', 'Jayaputra'],
            'tamani@present.com'           => ['admin123', 'Tamani Indah'],
            'hanif.hasan9@gmail.com'       => ['admin123', 'Choland/Hanif'],
            'uji.siswa@smauiiyk.sch.id'   => ['UjiSiswa@2026', 'Test Student'],
        ];

        $updated = 0;

        CLI::write('=== FIXING ADMIN/TEST USERS ===', 'yellow');
        foreach ($passwordMap as $email => $info) {
            $pw = $info[0];
            $desc = $info[1];
            $user = $db->table('users')->where('email', $email)->get()->getRow();

            if (!$user) {
                CLI::write("  {$email} — NOT FOUND (skipped)", 'yellow');
                continue;
            }

            $currentWorks = \Myth\Auth\Password::verify($pw, $user->password_hash);
            $status = $currentWorks ? 'OK' : 'NEEDS FIX';
            $color = $currentWorks ? 'green' : 'red';
            CLI::write("  {$email} ({$desc}) — {$status}", $color);

            if (!$currentWorks && !$dryRun) {
                $correctHash = \Myth\Auth\Password::hash($pw);
                $db->table('users')->where('id', $user->id)->update(['password_hash' => $correctHash]);
                $newHash = $db->table('users')->where('id', $user->id)->get()->getRow()->password_hash;
                $verified = \Myth\Auth\Password::verify($pw, $newHash);
                CLI::write("    Fixed! Verified: " . ($verified ? 'YES' : 'NO'), $verified ? 'green' : 'red');
                $updated++;
            }
        }

        CLI::write(PHP_EOL . '=== FIXING STUDENT USERS (password123) ===', 'yellow');
        $students = $db->table('users')->like('email', 'siswa%@gmail.com', 'after')->get()->getResultArray();

        foreach ($students as $student) {
            $currentWorks = \Myth\Auth\Password::verify('password123', $student['password_hash']);
            $status = $currentWorks ? 'OK' : 'NEEDS FIX';
            $color = $currentWorks ? 'green' : 'red';
            CLI::write("  {$student['email']} — {$status}", $color);

            if (!$currentWorks && !$dryRun) {
                $correctHash = \Myth\Auth\Password::hash('password123');
                $db->table('users')->where('id', $student['id'])->update(['password_hash' => $correctHash]);
                $newHash = $db->table('users')->where('id', $student['id'])->get()->getRow()->password_hash;
                $verified = \Myth\Auth\Password::verify('password123', $newHash);
                CLI::write("    Fixed! Verified: " . ($verified ? 'YES' : 'NO'), $verified ? 'green' : 'red');
                $updated++;
            }
        }

        if ($fixGroups) {
            CLI::write(PHP_EOL . '=== FIXING DUPLICATE GROUP ASSIGNMENTS ===', 'yellow');
            $dupes = $db->table('auth_groups_users')
                ->groupBy('user_id, group_id')
                ->having('COUNT(*) > 1')
                ->get()
                ->getResultArray();

            if (empty($dupes)) {
                CLI::write("  No duplicate groups found", 'green');
            } else {
                foreach ($dupes as $dupe) {
                    $user = $db->table('users')->where('id', $dupe['user_id'])->get()->getRow();
                    CLI::write("  Duplicate: user={$user->email} group_id={$dupe['group_id']}", 'red');

                    if (!$dryRun) {
                        $allRows = $db->table('auth_groups_users')
                            ->where('user_id', $dupe['user_id'])
                            ->where('group_id', $dupe['group_id'])
                            ->orderBy('id', 'ASC')
                            ->get()
                            ->getResultArray();

                        for ($i = 1; $i < count($allRows); $i++) {
                            $db->table('auth_groups_users')->where('id', $allRows[$i]['id'])->delete();
                        }
                        CLI::write("    Cleaned up duplicates", 'green');
                    }
                }
            }
        }

        CLI::write(PHP_EOL . '=== FINAL VERIFICATION ===', 'yellow');
        $allUsers = $db->table('users')->select('id, email, password_hash')->orderBy('id')->get()->getResultArray();
        $failCount = 0;

        foreach ($allUsers as $user) {
            $expectedPw = 'password123';
            if (isset($passwordMap[$user['email']])) {
                $expectedPw = $passwordMap[$user['email']][0];
            }

            $ok = \Myth\Auth\Password::verify($expectedPw, $user['password_hash']);
            CLI::write("  {$user['email']} → " . ($ok ? 'PASS' : 'FAIL'), $ok ? 'green' : 'red');
            if (!$ok) $failCount++;
        }

        CLI::write(PHP_EOL . "=== SUMMARY ===", 'yellow');
        CLI::write("Users updated: {$updated}");
        CLI::write("Final failures: {$failCount}", $failCount > 0 ? 'red' : 'green');
    }
}
