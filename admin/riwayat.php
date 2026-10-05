<?php
/**
 * File: admin/riwayat.php
 * Deskripsi: Riwayat semua pembayaran retribusi (khusus admin)
 */
$page_title = 'Riwayat Pembayaran - Admin';
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) redirect('../login.php');

$conn = getConnection();

// =============================================
// FILTER
// =============================================
$filter_status  = $_GET['status']  ?? 'semua';
$filter_metode  = $_GET['metode']  ?? 'semua';
$filter_bulan   = intval($_GET['bulan']  ?? date('n'));
$filter_tahun   = intval($_GET['tahun']  ?? date('Y'));
$filter_search  = sanitize($_GET['search'] ?? '');

// Build WHERE
$where  = "WHERE 1=1";
$params = [];
$types  = "";

if ($filter_status !== 'semua') {
    $where .= " AND p.status = ?";
    $params[] = $filter_status;
    $types   .= "s";
}

if ($filter_metode !== 'semua') {
    $where .= " AND p.metode_pembayaran = ?";
    $params[] = $filter_metode;
    $types   .= "s";
}

if ($filter_bulan > 0) {
    $where .= " AND MONTH(p.tanggal_bayar) = ?";
    $params[] = $filter_bulan;
    $types   .= "i";
}

if ($filter_tahun > 0) {
    $where .= " AND YEAR(p.tanggal_bayar) = ?";
    $params[] = $filter_tahun;
    $types   .= "i";
}

if ($filter_search !== '') {
    $where .= " AND (u.nama LIKE ? OR p.no_pembayaran LIKE ? OR t.no_tagihan LIKE ?)";
    $like      = "%$filter_search%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types   .= "sss";
}

// =============================================
// QUERY DATA
// =============================================
$sql = "
    SELECT 
        p.*,
        u.nama         AS nama_user,
        u.email        AS email_user,
        u.no_hp        AS hp_user,
        t.no_tagihan,
        t.periode_bulan,
        t.periode_tahun,
        t.jumlah       AS jumlah_tagihan,
        t.denda,
        jr.nama_retribusi,
        o.nama_objek,
        o.kode_objek
    FROM pembayaran p
    JOIN users u            ON p.user_id       = u.id
    JOIN tagihan t          ON p.tagihan_id     = t.id
    JOIN objek_retribusi o  ON t.objek_retribusi_id = o.id
    JOIN jenis_retribusi jr ON o.jenis_retribusi_id = jr.id
    $where
    ORDER BY p.created_at DESC
";

if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($sql);
}

// Simpan ke array (dipakai untuk tabel & total)
$riwayat_arr = [];
while ($row = $result->fetch_assoc()) {
    $riwayat_arr[] = $row;
}

// =============================================
// TOTAL PENDAPATAN (dari hasil filter)
// =============================================
$total_pendapatan = 0;
$total_sukses     = 0;
$total_pending    = 0;
$total_gagal      = 0;

foreach ($riwayat_arr as $row) {
    if ($row['status'] === 'success') {
        $total_pendapatan += $row['jumlah_bayar'];
        $total_sukses++;
    } elseif ($row['status'] === 'pending') {
        $total_pending++;
    } elseif ($row['status'] === 'failed') {
        $total_gagal++;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        @media print {
            .no-print  { display: none !important; }
            .sidebar   { display: none !important; }
            .flex-grow-1 { padding: 0 !important; }
        }
    </style>
</head>
<body>
<div class="d-flex">

    <!-- ===================== SIDEBAR ===================== -->
    <nav class="sidebar d-flex flex-column flex-shrink-0 p-3 bg-dark text-white no-print"
         style="width: 250px; min-height: 100vh;">
        <a href="index.php" class="d-flex align-items-center mb-3 text-white text-decoration-none">
            <i class="bi bi-building me-2 fs-4"></i>
            <span class="fs-5 fw-bold">Admin Panel</span>
        </a>
        <hr>
        <ul class="nav nav-pills flex-column mb-auto">
            <li class="nav-item mb-1">
                <a href="index.php" class="nav-link text-white">
                    <i class="bi bi-speedometer2 me-2"></i> Dashboard
                </a>
            </li>
            <li class="nav-item mb-1">
                <a href="kelola-user.php" class="nav-link text-white">
                    <i class="bi bi-people me-2"></i> Kelola User
                </a>
            </li>
            <li class="nav-item mb-1">
                <a href="kelola-tagihan.php" class="nav-link text-white">
                    <i class="bi bi-receipt me-2"></i> Kelola Tagihan
                </a>
            </li>
            <li class="nav-item mb-1">
                <a href="riwayat.php" class="nav-link text-white active">
                    <i class="bi bi-clock-history me-2"></i> Riwayat Pembayaran
                </a>
            </li>
            <li class="nav-item mb-1">
                <a href="laporan.php" class="nav-link text-white">
                    <i class="bi bi-bar-chart me-2"></i> Laporan
                </a>
            </li>
        </ul>
        <hr>
        <div class="dropdown">
            <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle"
               data-bs-toggle="dropdown">
                <i class="bi bi-person-circle me-2"></i>
                <strong><?= $_SESSION['nama'] ?></strong>
            </a>
            <ul class="dropdown-menu dropdown-menu-dark text-small shadow">
                <li><a class="dropdown-item" href="#">Profil</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="../logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <!-- ===================== MAIN CONTENT ===================== -->
    <div class="flex-grow-1 p-4">

        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center 