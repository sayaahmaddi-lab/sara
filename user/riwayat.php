<?php
/**
 * File: user/riwayat.php
 * Deskripsi: Riwayat pembayaran user
 */
$page_title = 'Riwayat Pembayaran';
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) redirect('../login.php');

$conn    = getConnection();
$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT p.*, t.no_tagihan, t.periode_bulan, t.periode_tahun,
           jr.nama_retribusi, o.nama_objek
    FROM pembayaran p
    JOIN tagihan t ON p.tagihan_id = t.id
    JOIN objek_retribusi o ON t.objek_retribusi_id = o.id
    JOIN jenis_retribusi jr ON o.jenis_retribusi_id = jr.id
    WHERE p.user_id = ?
    ORDER BY p.created_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$riwayat = $stmt->get_result();
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
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand fw-bold" href="dashboard.php">
                <i class="bi bi-building"></i> Retribusi Daerah
            </a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="bi bi-house-door"></i> Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="tagihan.php"><i class="bi bi-receipt"></i> Tagihan</a></li>
                    <li class="nav-item"><a class="nav-link active" href="riwayat.php"><i class="bi bi-clock-history"></i> Riwayat</a></li>
                    <li class="nav-item"><a class="nav-link text-danger" href="../logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h2 class="mb-4"><i class="bi bi-clock-history"></i> Riwayat Pembayaran</h2>
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>No</th>
                                <th>No. Pembayaran</th>
                                <th>No. Tagihan</th>
                                <th>Objek / Jenis</th>
                                <th>Periode</th>
                                <th>Metode</th>
                                <th>Jumlah</th>
                                <th>Tanggal Bayar</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $no = 1;
                            if ($riwayat->num_rows > 0):
                                while ($row = $riwayat->fetch_assoc()):
                            ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><small><?= $row['no_pembayaran'] ?></small></td>
                                <td><small><?= $row['no_tagihan'] ?></small></td>
                                <td>
                                    <strong><?= $row['nama_objek'] ?></strong>
                                    <br><small class="text-muted"><?= $row['nama_retribusi'] ?></small>
                                </td>
                                <td><?= getNamaBulan($row['periode_bulan']) ?> <?= $row['periode_tahun'] ?></td>
                                <td>
                                    <span class="badge bg-secondary">
                                        <?= strtoupper($row['metode_pembayaran']) ?>
                                    </span>
                                </td>
                                <td class="fw-bold text-success"><?= formatRupiah($row['jumlah_bayar']) ?></td>
                                <td><?= formatTanggal($row['tanggal_bayar'], true) ?></td>
                                <td><?= getStatusBadge($row['status']) ?></td>
                                <td>
                                    <a href="cetak-kwitansi.php?no=<?= $row['no_pembayaran'] ?>" 
                                       class="btn btn-sm btn-outline-success">
                                        <i class="bi bi-printer"></i> Cetak
                                    </a>
                                </td>
                            </tr>
                            <?php
                                endwhile;
                            else:
                            ?>
                            <tr>
                                <td colspan="10" class="text-center py-5">
                                    <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                                    <p class="mt-2 text-muted">Belum ada riwayat pembayaran</p>
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
</body>
</html>
<?php closeConnection($conn); ?>