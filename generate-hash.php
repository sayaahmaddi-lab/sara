<?php
/**
 * File: generate-hash.php
 * Deskripsi: Generate password hash yang benar
 * ⚠️ HAPUS FILE INI SETELAH DIGUNAKAN!
 */

$passwords = [
    'admin123' => password_hash('admin123', PASSWORD_DEFAULT),
    'user123'  => password_hash('user123',  PASSWORD_DEFAULT),
];

echo "<pre>";
foreach ($passwords as $pass => $hash) {
    echo "Password : $pass\n";
    echo "Hash     : $hash\n";
    echo "Verify   : " . (password_verify($pass, $hash) ? '✅ Valid' : '❌ Invalid') . "\n";
    echo str_repeat('-', 80) . "\n";
}
echo "</pre>";
?>