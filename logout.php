<?php
/**
 * File: logout.php
 * Deskripsi: Proses logout user
 */
require_once 'config/database.php';
require_once 'includes/functions.php';

// Log aktivitas logout
if (isLoggedIn()) {
    $conn = getConnection();
    logAktivitas($conn, $_SESSION['user_id'], 'Logout', 'User logout dari sistem');
    closeConnection($conn);
}

// Hapus semua session
$_SESSION = [];
session_destroy();

// Redirect ke halaman login
redirect('login.php');
?>