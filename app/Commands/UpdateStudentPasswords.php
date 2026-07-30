<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;

class UpdateStudentPasswords extends BaseCommand
{
    protected $group       = 'App';
    protected $name        = 'students:update-passwords';
    protected $description = 'Update all student passwords to password123';
    protected $usage       = 'students:update-passwords';

    public function run(array $params)
    {
        $db = Database::connect();
        
        // Update student passwords
        $result = $db->query("SELECT id, email FROM users WHERE email LIKE 'siswa%@gmail.com'");
        $rows = $result->getResultArray();
        
        $updated = 0;
        foreach ($rows as $row) {
            // MUST use Myth\Auth\Password::hash() — it prepends SHA-384+base64 before bcrypt
            $hash = \Myth\Auth\Password::hash('password123');
            $db->query("UPDATE users SET password_hash = ?, force_pass_reset = 1 WHERE id = ?", [$hash, $row['id']]);
            CLI::write("Updated: {$row['email']}");
            $updated++;
        }
        
        // Also update admin and test users
        $adminResult = $db->query("SELECT id, email FROM users WHERE email IN ('admin@smauiiyk.sch.id', 'jaya@present.com', 'tamani@present.com', 'hanif.hasan9@gmail.com', 'uji.siswa@smauiiyk.sch.id')");
        $adminRows = $adminResult->getResultArray();
        
        foreach ($adminRows as $row) {
            // MUST use Myth\Auth\Password::hash() — it prepends SHA-384+base64 before bcrypt
            $hash = \Myth\Auth\Password::hash('admin123');
            $db->query("UPDATE users SET password_hash = ?, force_pass_reset = 1 WHERE id = ?", [$hash, $row['id']]);
            CLI::write("Updated: {$row['email']} (admin123)");
            $updated++;
        }
        
        // uji.siswa gets special password
        $ujiResult = $db->query("SELECT id, email FROM users WHERE email = 'uji.siswa@smauiiyk.sch.id'");
        $ujiRow = $ujiResult->getRow();
        if ($ujiRow) {
            $hash = \Myth\Auth\Password::hash('UjiSiswa@2026');
            $db->query("UPDATE users SET password_hash = ?, force_pass_reset = 1 WHERE id = ?", [$hash, $ujiRow->id]);
            CLI::write("Updated: {$ujiRow->email} (UjiSiswa@2026)");
            $updated++;
        }
        
        CLI::write("Done! Updated {$updated} users.");
    }
}