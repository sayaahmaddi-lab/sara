<?php
/**
 * File: config/database.php
 * Deskripsi: Konfigurasi koneksi database
 */

// Konfigurasi Database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'retribusi_daerah');

/**
 * Fungsi untuk membuat koneksi database
 * @return mysqli
 */
function getConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        die("Koneksi database gagal: " . $conn->connect_error);
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