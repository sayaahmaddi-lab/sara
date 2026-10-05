<?php
/**
 * File: admin/laporan.php
 * Deskripsi: Laporan Pembayaran Retribusi
 */
$page_title = 'Laporan';
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) redirect('../login.php');

$conn = getConnection();

// Filter
$filter_bulan = intval($_GET['bulan'] ?? date('n'));
$filter_tahun = intval($_GET['tahun'] ?? date('Y'));

// Query laporan
$stmt = $conn->prepare("
    SELECT p.*, u.nama as nama_user, t.no_tagihan, t.periode_bulan, t.periode_tahun,
           jr.nama_retribusi, o.nama_objek, o.kode_objek
    FROM pembayaran p
    JOIN users u ON p.user_id = u.id
    JOIN tagihan t ON p.tagihan_id = t.id
    JOIN objek_retribusi o ON t.objek_retribusi_id = o.id
    JOIN jenis_retribusi jr ON o.jenis_retribusi_id = jr.id
    WHERE MONTH(p.tanggal_bayar) = ? AND YEAR(p.tanggal_bayar) = ?
    AND p.status = 'success'
    ORDER BY p.tanggal_bayar DESC
");
$stmt->bind_param("ii", $filter_bulan, $filter_tahun);
$stmt->execute();
$laporan = $stmt->get_result();

// Total pendapatan bulan ini
$stmt2 = $conn->prepare("
    SELECT COALESCE(SUM(jumlah_bayar), 0) as total
    FROM pembayaran
    WHERE MONTH(tanggal_bayar) = ? AND YEAR(tanggal_bayar) = ? AND status = 'success'
");
$stmt2->bind_param("ii", $filter_bulan, $filter_tahun);
$stmt2->execute();
$total_pendapatan = $stmt2->get_result()->fetch_assoc()['total'];

// Simpan hasil ke array untuk print
$laporan_arr = [];
while ($row = $laporan->fetch_assoc()) {
    $laporan_arr[] = $row;
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
</head>
<body>
<div class="d-flex">
    <!-- Sidebar -->
    <nav class="d-flex flex-column flex-shrink-0 p-3 bg-dark text-white" style="width: 250px; min-height: 100vh;">
        <a href="index.php" class="d-flex align-items-center mb-3 text-white text-decoration-none">
            <i class="bi bi-building me-2 fs-4"></i>
            <span class="fs-5 fw-bold">Admin Panel</span>
        </a>
        <hr>
        <ul class="nav nav-pills flex-column mb-auto">
            <li class="nav-item mb-1"><a href="index.php" class="nav-link text-white"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a></li>
            <li class="nav-item mb-1"><a href="kelola-user.php" class="nav-link text-white"><i class="bi bi-people me-2"></i>Kelola User</a></li>
            <li class="nav-item mb-1"><a href="kelola-tagihan.php" class="nav-link text-white"><i class="bi bi-receipt me-2"></i>Kelola Tagihan</a></li>
            <li class="nav-item mb-1"><a href="laporan.php" class="nav-link text-white active"><i class="bi bi-bar-chart me-2"></i>Laporan</a></li>
        </ul>
        <hr>
        <a href="../logout.php" class="btn btn-outline-danger btn-sm"><i class="bi bi-box-arrow-right"></i> Logout</a>
    </nav>

    <!-- Content -->
    <div class="flex-grow-1 p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-bar-chart"></i> Laporan Pembayaran</h2>
            <button onclick="window.print()" class="btn btn-success no-print">
                <i class="bi bi-printer"></i> Cetak Laporan
            </button>
        </div>

        <!-- Filter -->
        <div class="card mb-4 no-print">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Bulan</label>
                        <select class="form-select" name="bulan">
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                                <option value="<?= $m ?>" <?= $filter_bulan == $m ? 'selected' : '' ?>>
                                    <?= getNamaBulan($m) ?>
                                </option>
                            <?php endfor; ?>
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
                            <i class="bi bi-search"></i> Tampilkan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Summary -->
        <div class="card mb-4 bg-primary text-white">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0">Total Pendapatan</h5>
                    <small><?= getNamaBulan($filter_bulan) ?> <?= $filter_tahun ?></small>
                </div>
                <h2 class="mb-0"><?= formatRupiah($total_pendapatan) ?></h2>
            </div>
        </div>

        <!-- Tabel Laporan -->
        <div class="card" id="tabelLaporan">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">
                    Laporan Pembayaran - <?= getNamaBulan($filter_bulan) ?> <?= $filter_tahun ?>
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>No</th>
                                <th>No. Pembayaran</th>
                                <th>Tanggal Bayar</th>
                                <th>Nama Penyewa</th>
                                <th>Objek</th>
                                <th>Jenis Retribusi</th>
                                <th>Metode</th>
                                <th>Jumlah</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($laporan_arr) > 0): ?>
                                <?php $no = 1; foreach ($laporan_arr as $row): ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><small><?= $row['no_pembayaran'] ?></small></td>
                                    <td><?= formatTanggal($row['tanggal_bayar'], true) ?></td>
                                    <td><?= $row['nama_user'] ?></td>
                                    <td>
                                        <?= $row['nama_objek'] ?>
                                        <br><small class="text-muted"><?= $row['kode_objek'] ?></small>
                                    </td>
                                    <td><?= $row['nama_retribusi'] ?></td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            <?= strtoupper($row['metode_pembayaran']) ?>
                                        </span>
                                    </td>
                                    <td class="fw-bold text-success"><?= formatRupiah($row['jumlah_bayar']) ?></td>
                                    <td><?= getStatusBadge($row['status']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <!-- Total Row -->
                                <tr class="table-dark fw-bold">
                                    <td colspan="7" class="text-end">TOTAL PENDAPATAN:</td>
                                    <td class="text-warning"><?= formatRupiah($total_pendapatan) ?></td>
                                    <td></td>
                                </tr>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="text-center py-5">
                                        <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                                        <p class="text-muted mt-2">Tidak ada data pembayaran untuk periode ini</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php closeConnection($conn); ?>