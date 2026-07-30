<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Reset known test accounts to password123 (Myth-Auth compatible hash).
 * Dev + prod safe (idempotent update by username pattern).
 */
class FixTestPasswordsSeeder extends Seeder
{
    public function run()
    {
        $plain = 'password123';
        if (class_exists(\Myth\Auth\Password::class)) {
            $hash = \Myth\Auth\Password::hash($plain);
        } else {
            $hash = password_hash(base64_encode(hash('sha384', $plain, true)), PASSWORD_DEFAULT);
        }

        $db = $this->db;

        // All siswa* accounts
        $db->table('users')
            ->groupStart()
                ->like('username', 'siswa', 'after')
                ->orLike('email', 'siswa', 'after')
            ->groupEnd()
            ->update([
                'password_hash' => $hash,
                'active'        => 1,
            ]);

        $siswaCount = $db->table('users')
            ->groupStart()
                ->like('username', 'siswa', 'after')
                ->orLike('email', 'siswa', 'after')
            ->groupEnd()
            ->countAllResults();

        // Ensure admin still works with admin123 if present
        $adminHash = class_exists(\Myth\Auth\Password::class)
            ? \Myth\Auth\Password::hash('admin123')
            : password_hash(base64_encode(hash('sha384', 'admin123', true)), PASSWORD_DEFAULT);

        $db->table('users')
            ->where('email', 'admin@smauiiyk.sch.id')
            ->orWhere('username', 'admin')
            ->update([
                'password_hash' => $adminHash,
                'active'        => 1,
            ]);

        echo "FixTestPasswordsSeeder: siswa accounts touched≈{$siswaCount}, password=password123; admin=admin123\n";
    }
}
