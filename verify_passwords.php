<?php
// Verify password hashes for dev DB
require '/var/www/html/vendor/autoload.php';
$app = \Config\Services::codeigniter();
$app->initialize();
$db = \Config\Database::connect();

$users = $db->table('users')->select('id, email, username, password_hash')->get()->getResultArray();
echo "=== DEV DB PASSWORD VERIFICATION ===" . PHP_EOL;
foreach ($users as $u) {
    $verifyAdmin = \Myth\Auth\Password::verify('admin123', $u['password_hash']);
    $verifyPass = \Myth\Auth\Password::verify('password123', $u['password_hash']);
    $verifyUji = \Myth\Auth\Password::verify('UjiSiswa@2026', $u['password_hash']);
    echo "id={$u['id']} email={$u['email']} admin123=" . ($verifyAdmin ? 'YES' : 'no') . ' password123=' . ($verifyPass ? 'YES' : 'no') . ' UjiSiswa=' . ($verifyUji ? 'YES' : 'no') . PHP_EOL;
}
echo PHP_EOL . "=== DEV ADMIN USER AUTH GROUPS ===" . PHP_EOL;
$adminGroups = $db->table('auth_groups_users')->where('user_id', 1)->get()->getResultArray();
foreach ($adminGroups as $g) echo "group_id={$g['group_id']}" . PHP_EOL;

echo PHP_EOL . "=== DEV ALL AUTH GROUPS ===" . PHP_EOL;
$allGroups = $db->table('auth_groups')->get()->getResultArray();
foreach ($allGroups as $g) echo "id={$g['id']} name={$g['name']}" . PHP_EOL;
