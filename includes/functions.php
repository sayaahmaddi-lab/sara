<?php
/**
 * File: includes/functions.php
 * Deskripsi: Fungsi-fungsi helper yang digunakan di seluruh aplikasi
 */

// Start session jika belum dimulai
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Cek apakah user sudah login
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Cek apakah user adalah admin
 * @return bool
 */
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Redirect ke halaman tertentu
 * @param string $url
 */
function redirect($url) {
    header("Location: $url");
    exit();
}

/**
 * Generate kode unik dengan prefix
 * @param string $prefix
 * @return string
 */
function generateKode($prefix) {
    return $prefix . '-' . date('Ymd') . rand(1000, 9999);
}

/**
 * Format angka ke format Rupiah
 * @param float $angka
 * @return string
 */
function formatRupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

/**
 * Format tanggal ke format Indonesia
 * @param string $tanggal
 * @param bool $with_time
 * @return string
 */
function formatTanggal($tanggal, $with_time = false) {
    if ($with_time) {
        return date('d/m/Y H:i', strtotime($tanggal));
    }
    return date('d/m/Y', strtotime($tanggal));
}

/**
 * Sanitize input dari user
 * @param string $data
 * @return string
 */
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

/**
 * Ambil nama bulan dalam Bahasa Indonesia
 * @param int $bulan
 * @return string
 */
function getNamaBulan($bulan) {
    $nama_bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    return $nama_bulan[$bulan] ?? '';
}

/**
 * Hitung denda berdasarkan keterlambatan
 * @param float $jumlah
 * @param string $jatuh_tempo
 * @return float
 */
function hitungDenda($jumlah, $jatuh_tempo) {
    $today = new DateTime();
    $due_date = new DateTime($jatuh_tempo);
    
    if ($today > $due_date) {
        $interval = $today->diff($due_date);
        $hari_terlambat = $interval->days;
        
        // Denda 1% per hari, maksimal 25%
        $persen_denda = min($hari_terlambat * 1, 25);
        return ($jumlah * $persen_denda) / 100;
    }
    
    return 0;
}

/**
 * Catat log aktivitas user
 * @param mysqli $conn
 * @param int $user_id
 * @param string $aktivitas
 * @param string $keterangan
 */
function logAktivitas($conn, $user_id, $aktivitas, $keterangan = '') {
    $ip = $_SERVER['REMOTE_ADDR'];
    $stmt = $conn->prepare("INSERT INTO log_aktivitas (user_id, aktivitas, keterangan, ip_address) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $user_id, $aktivitas, $keterangan, $ip);
    $stmt->execute();
    $stmt->close();
}

/**
 * Upload file
 * @param array $file
 * @param string $target_dir
 * @return array
 */
function uploadFile($file, $target_dir = 'uploads/') {
    $result = ['success' => false, 'message' => '', 'filename' => ''];
    
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'];
    $max_size = 2 * 1024 * 1024; // 2MB
    
    if (!in_array($file['type'], $allowed_types)) {
        $result['message'] = 'Format file tidak diizinkan';
        return $result;
    }
    
    if ($file['size'] > $max_size) {
        $result['message'] = 'Ukuran file terlalu besar (max 2MB)';
        return $result;
    }
    
    $filename = uniqid() . '_' . basename($file['name']);
    $target_file = $target_dir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        $result['success'] = true;
        $result['filename'] = $filename;
        $result['message'] = 'File berhasil diupload';
    } else {
        $result['message'] = 'Gagal upload file';
    }
    
    return $result;
}

/**
 * Get status badge HTML
 * @param string $status
 * @return string
 */
function getStatusBadge($status) {
    $badges = [
        'belum_bayar' => '<span class="badge bg-danger">Belum Bayar</span>',
        'sudah_bayar' => '<span class="badge bg-success">Sudah Bayar</span>',
        'pending' => '<span class="badge bg-warning">Pending</span>',
        'success' => '<span class="badge bg-success">Success</span>',
        'failed' => '<span class="badge bg-danger">Failed</span>',
        'aktif' => '<span class="badge bg-success">Aktif</span>',
        'nonaktif' => '<span class="badge bg-secondary">Non Aktif</span>',
    ];
    
    return $badges[$status] ?? '<span class="badge bg-secondary">' . $status . '</span>';
}
?>