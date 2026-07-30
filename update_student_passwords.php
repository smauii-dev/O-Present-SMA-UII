<?php
require "vendor/autoload.php";
$db = \Config\Database::connect();
$result = $db->query("SELECT id, email FROM users WHERE email LIKE 'siswa%@gmail.com'");
$rows = $result->getResultArray();
foreach ($rows as $row) {
    $hash = password_hash("password123", PASSWORD_BCRYPT, ["cost" => 10]);
    $db->query("UPDATE users SET password_hash = ? WHERE id = ?", [$hash, $row["id"]]);
    echo "Updated: " . $row["email"] . PHP_EOL;
}
echo "Done updating student passwords." . PHP_EOL;