<?php
/**
 * File: user/tagihan.php
 * Deskripsi: Halaman daftar semua tagihan user
 */
$page_title = 'Tagihan - Retribusi Daerah';
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) redirect('../login.php');
if (isAdmin()) redirect('../admin/index.php');

$conn = getConnection();
$user_id = $_SESSION['user_id'];

// Filter status
$filter_status = $_GET['status'] ?? 'semua';
$filter_tahun  = $_GET['tahun'] ?? date('Y');

// Query dasar
$where = "WHERE t.user_id = ?";
$params = [$user_id];
$types  = "i";

if ($filter_status !== 'semua') {
    $where .= " AND t.status = ?";
    $params[] = $filter_status;
    $types .= "s";
}

if ($filter_tahun) {
    $where .= " AND t.periode_tahun = ?";
    $params[] = $filter_tahun;
    $types .= "i";
}

$stmt = $conn->prepare("
    SELECT t.*, jr.nama_retribusi, o.nama_objek, o.kode_objek
    FROM tagihan t
    JOIN objek_retribusi o ON t.objek_retribusi_id = o.id
    JOIN jenis_retribusi jr ON o.jenis_retribusi_id = jr.id
    $where
    ORDER BY t.created_at DESC
");
$stmt->bind_param($types, ...$params);
$stmt->execute();
$tagihan_list = $stmt->get_result();
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
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="bi bi-house-door"></i> Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link active" href="tagihan.php"><i class="bi bi-receipt"></i> Tagihan</a></li>
                    <li class="nav-item"><a class="nav-link" href="riwayat.php"><i class="bi bi-clock-history"></i> Riwayat</a></li>
                    <li class="nav-item"><a class="nav-link text-danger" href="../logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-receipt"></i> Daftar Tagihan</h2>
        </div>

        <!-- Filter -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Status Tagihan</label>
                        <select class="form-select" name="status">
                            <option value="semua" <?= $filter_status === 'semua' ? 'selected' : '' ?>>Semua</option>
                            <option value="belum_bayar" <?= $filter_status === 'belum_bayar' ? 'selected' : '' ?>>Belum Bayar</option>
                            <option value="sudah_bayar" <?= $filter_status === 'sudah_bayar' ? 'selected' : '' ?>>Sudah Bayar</option>
                            <option value="kadaluarsa" <?= $filter_status === 'kadaluarsa' ? 'selected' : '' ?>>Kadaluarsa</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Tahun</label>
                        <select class="form-select" name="tahun">
                            <?php for ($y = date('Y'); $y >= date('Y') - 3; $y--): ?>
                                <option value="<?= $y ?>" <?= $filter_tahun == $y ? 'selected' : '' ?>><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search"></i> Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabel Tagihan -->
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-list-ul"></i> Daftar Tagihan</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>No</th>
                                <th>No. Tagihan</th>
                                <th>Objek Retribusi</th>
                                <th>Jenis</th>
                                <th>Periode</th>
                                <th>Jumlah</th>
                                <th>Denda</th>
                                <th>Total</th>
                                <th>Jatuh Tempo</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $no = 1;
                            if ($tagihan_list->num_rows > 0):
                                while ($row = $tagihan_list->fetch_assoc()):
                            ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><small class="text-muted"><?= $row['no_tagihan'] ?></small></td>
                                <td>
                                    <strong><?= $row['nama_objek'] ?></strong>
                                    <br><small class="text-muted"><?= $row['kode_objek'] ?></small>
                                </td>
                                <td><?= $row['nama_retribusi'] ?></td>
                                <td><?= getNamaBulan($row['periode_bulan']) ?> <?= $row['periode_tahun'] ?></td>
                                <td><?= formatRupiah($row['jumlah']) ?></td>
                                <td><?= formatRupiah($row['denda']) ?></td>
                                <td class="fw-bold text-danger"><?= formatRupiah($row['total']) ?></td>
                                <td><?= formatTanggal($row['jatuh_tempo']) ?></td>
                                <td><?= getStatusBadge($row['status']) ?></td>
                                <td>
                                    <?php if ($row['status'] === 'belum_bayar'): ?>
                                        <a href="bayar.php?id=<?= $row['id'] ?>" class="btn btn-danger btn-sm">
                                            <i class="bi bi-credit-card"></i> Bayar
                                        </a>
                                    <?php elseif ($row['status'] === 'sudah_bayar'): ?>
                                        <a href="cetak-kwitansi.php?id=<?= $row['id'] ?>" class="btn btn-success btn-sm">
                                            <i class="bi bi-printer"></i> Cetak
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php
                                endwhile;
                            else:
                            ?>
                            <tr>
                                <td colspan="11" class="text-center py-5">
                                    <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                                    <p class="mt-2 text-muted">Tidak ada tagihan ditemukan</p>
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
    <script src="../assets/js/script.js"></script>
</body>
</html>
<?php closeConnection($conn); ?>