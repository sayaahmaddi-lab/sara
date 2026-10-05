<?php
/**
 * File: user/dashboard.php
 * Deskripsi: Halaman dashboard untuk user/penyewa
 */
$page_title = 'Dashboard - Retribusi Daerah';
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) redirect('../login.php');
if (isAdmin()) redirect('../admin/index.php');

$conn = getConnection();
$user_id = $_SESSION['user_id'];

// Ambil statistik tagihan belum bayar
$stmt = $conn->prepare("
    SELECT COUNT(*) as total, COALESCE(SUM(total), 0) as nominal 
    FROM tagihan 
    WHERE user_id = ? AND status = 'belum_bayar'
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$belum_bayar = $stmt->get_result()->fetch_assoc();

// Ambil statistik tagihan sudah bayar
$stmt = $conn->prepare("
    SELECT COUNT(*) as total, COALESCE(SUM(total), 0) as nominal 
    FROM tagihan 
    WHERE user_id = ? AND status = 'sudah_bayar'
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$sudah_bayar = $stmt->get_result()->fetch_assoc();

// Ambil jumlah objek retribusi
$stmt = $conn->prepare("
    SELECT COUNT(*) as total 
    FROM objek_retribusi 
    WHERE user_id = ? AND status = 'aktif'
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_objek = $stmt->get_result()->fetch_assoc()['total'];

// Ambil tagihan terbaru
$stmt = $conn->prepare("
    SELECT t.*, jr.nama_retribusi, o.nama_objek, o.kode_objek
    FROM tagihan t
    JOIN objek_retribusi o ON t.objek_retribusi_id = o.id
    JOIN jenis_retribusi jr ON o.jenis_retribusi_id = jr.id
    WHERE t.user_id = ?
    ORDER BY t.created_at DESC
    LIMIT 5
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$tagihan_terbaru = $stmt->get_result();
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
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand fw-bold" href="dashboard.php">
                <i class="bi bi-building"></i> Retribusi Daerah
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">
                            <i class="bi bi-house-door"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="tagihan.php">
                            <i class="bi bi-receipt"></i> Tagihan
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="riwayat.php">
                            <i class="bi bi-clock-history"></i> Riwayat
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?= $_SESSION['nama'] ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="#"><i class="bi bi-person"></i> Profil</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="../logout.php">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Welcome Alert -->
        <div class="alert alert-primary alert-dismissible fade show" role="alert">
            <i class="bi bi-info-circle"></i> Selamat datang, <strong><?= $_SESSION['nama'] ?></strong>!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>

        <!-- Statistik Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card stats-card bg-danger text-white h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="me-3">
                            <i class="bi bi-exclamation-circle" style="font-size: 2.5rem;"></i>
                        </div>
                        <div>
                            <div class="small">Tagihan Belum Bayar</div>
                            <h3 class="mb-0"><?= $belum_bayar['total'] ?></h3>
                            <small><?= formatRupiah($belum_bayar['nominal']) ?></small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stats-card bg-success text-white h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="me-3">
                            <i class="bi bi-check-circle" style="font-size: 2.5rem;"></i>
                        </div>
                        <div>
                            <div class="small">Tagihan Lunas</div>
                            <h3 class="mb-0"><?= $sudah_bayar['total'] ?></h3>
                            <small><?= formatRupiah($sudah_bayar['nominal']) ?></small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stats-card bg-info text-white h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="me-3">
                            <i class="bi bi-building" style="font-size: 2.5rem;"></i>
                        </div>
                        <div>
                            <div class="small">Objek Retribusi</div>
                            <h3 class="mb-0"><?= $total_objek ?></h3>
                            <small>Objek aktif</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stats-card bg-warning text-white h-100">
                    <div class="card-body d-flex align-items-center">
                        <div class="me-3">
                            <i class="bi bi-person-check" style="font-size: 2.5rem;"></i>
                        </div>
                        <div>
                            <div class="small">Status Akun</div>
                            <h3 class="mb-0">Aktif</h3>
                            <small>Terverifikasi</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tagihan Terbaru -->
        <div class="card">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-receipt"></i> Tagihan Terbaru</h5>
                <a href="tagihan.php" class="btn btn-light btn-sm">Lihat Semua</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>No. Tagihan</th>
                                <th>Objek</th>
                                <th>Jenis Retribusi</th>
                                <th>Periode</th>
                                <th>Total</th>
                                <th>Jatuh Tempo</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($tagihan_terbaru->num_rows > 0): ?>
                                <?php while ($row = $tagihan_terbaru->fetch_assoc()): ?>
                                <tr>
                                    <td><small><?= $row['no_tagihan'] ?></small></td>
                                    <td><?= $row['nama_objek'] ?></td>
                                    <td><?= $row['nama_retribusi'] ?></td>
                                    <td><?= getNamaBulan($row['periode_bulan']) ?> <?= $row['periode_tahun'] ?></td>
                                    <td class="fw-bold"><?= formatRupiah($row['total']) ?></td>
                                    <td><?= formatTanggal($row['jatuh_tempo']) ?></td>
                                    <td><?= getStatusBadge($row['status']) ?></td>
                                    <td>
    <?php if ($row['status'] === 'belum_bayar'): ?>
        <a href="bayar.php?id=<?= $row['id'] ?>"
           class="btn btn-sm btn-danger mb-1">
            <i class="bi bi-credit-card"></i> Bayar
        </a>
        <a href="cetak-tagihan.php?id=<?= $row['id'] ?>"
           target="_blank"
           class="btn btn-sm btn-warning mb-1">
            <i class="bi bi-printer"></i> Cetak
        </a>
    <?php else: ?>
        <a href="cetak-kwitansi.php?id=<?= $row['id'] ?>"
           target="_blank"
           class="btn btn-sm btn-success">
            <i class="bi bi-printer"></i> Kwitansi
        </a>
    <?php endif; ?>
</td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4">
                                        <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                                        <p class="mt-2 text-muted">Belum ada tagihan</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <footer class="bg-light text-center py-3 mt-5">
        <small class="text-muted">© <?= date('Y') ?> Sistem Retribusi Daerah</small>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="../assets/js/script.js"></script>
</body>
</html>
<?php closeConnection($conn); ?>