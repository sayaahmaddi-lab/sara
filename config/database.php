<?php
/**
 * File: config/database.php
 * Deskripsi: Konfigurasi koneksi database
 * Support: local (localhost) + Vercel env vars + DATABASE_URL
 */

// Baca dari env vars jika ada (untuk Vercel), fallback ke default lokal
// Juga support DATABASE_URL format: mysql://user:pass@host:port/dbname
if (getenv('DATABASE_URL')) {
    $dbUrl = parse_url(getenv('DATABASE_URL'));
    define('DB_HOST', $dbUrl['host'] ?? 'localhost');
    define('DB_USER', $dbUrl['user'] ?? 'root');
    define('DB_PASS', $dbUrl['pass'] ?? '');
    define('DB_NAME', ltrim($dbUrl['path'] ?? '/retribusi_daerah', '/'));
    // port jika ada di URL
    if (isset($dbUrl['port'])) {
        define('DB_PORT', $dbUrl['port']);
    } else {
        define('DB_PORT', 3306);
    }
} else {
    define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
    define('DB_USER', getenv('DB_USER') ?: 'root');
    define('DB_PASS', getenv('DB_PASS') ?: '');
    define('DB_NAME', getenv('DB_NAME') ?: 'retribusi_daerah');
    define('DB_PORT', getenv('DB_PORT') ?: 3306);
}

/**
 * Fungsi untuk membuat koneksi database
 * @return mysqli
 */
function getConnection() {
    $port = defined('DB_PORT') ? (int)DB_PORT : 3306;
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, $port);

    if ($conn->connect_error) {
        // Tampilkan pesan ramah jika di production
        die("Koneksi database gagal: " . $conn->connect_error . " — Set env vars DB_HOST/DB_USER/DB_PASS/DB_NAME atau DATABASE_URL di Vercel.");
    }

    $conn->set_charset("utf8");
    return $conn;
}

/**
 * Fungsi untuk menutup koneksi database
 * @param mysqli $conn
 */
function closeConnection($conn) {
    if ($conn) {
        $conn->close();
    }
}
?>
