<?php
/**
 * File: admin/kelola-tagihan.php
 * Deskripsi: Kelola Tagihan Retribusi (CRUD)
 */
$page_title = 'Kelola Tagihan';
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) redirect('../login.php');

$conn    = getConnection();
$success = '';
$error   = '';

// Ambil daftar objek retribusi (untuk form tambah tagihan)
$objek_list = $conn->query("
    SELECT o.*, u.nama as nama_user, jr.nama_retribusi, jr.tarif
    FROM objek_retribusi o
    JOIN users u ON o.user_id = u.id
    JOIN jenis_retribusi jr ON o.jenis_retribusi_id = jr.id
    WHERE o.status = 'aktif'
");

// Proses tambah tagihan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    // Tambah Tagihan
    if ($action === 'tambah') {
        $objek_id      = intval($_POST['objek_retribusi_id']);
        $periode_bulan = intval($_POST['periode_bulan']);
        $periode_tahun = intval($_POST['periode_tahun']);
        $jumlah        = floatval($_POST['jumlah']);
        $jatuh_tempo   = sanitize($_POST['jatuh_tempo']);

        // Cek duplikasi tagihan
        $stmt = $conn->prepare("
            SELECT id FROM tagihan 
            WHERE objek_retribusi_id = ? AND periode_bulan = ? AND periode_tahun = ?
        ");
        $stmt->bind_param("iii", $objek_id, $periode_bulan, $periode_tahun);
        $stmt->execute();

        if ($stmt->get_result()->num_rows > 0) {
            $error = 'Tagihan untuk periode ini sudah ada!';
        } else {
            // Ambil user_id dari objek
            $stmt2 = $conn->prepare("SELECT user_id FROM objek_retribusi WHERE id = ?");
            $stmt2->bind_param("i", $objek_id);
            $stmt2->execute();
            $objek  = $stmt2->get_result()->fetch_assoc();
            $user_id = $objek['user_id'];

            // Hitung denda jika sudah lewat jatuh tempo
            $denda = hitungDenda($jumlah, $jatuh_tempo);
            $total = $jumlah + $denda;
            $no_tagihan = generateKode('TAG');

            $stmt3 = $conn->prepare("
                INSERT INTO tagihan 
                (objek_retribusi_id, user_id, no_tagihan, periode_bulan, periode_tahun, jumlah, denda, total, jatuh_tempo)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt3->bind_param("iisiiidds", $objek_id, $user_id, $no_tagihan,
                               $periode_bulan, $periode_tahun, $jumlah, $denda, $total, $jatuh_tempo);

            if ($stmt3->execute()) {
                $success = 'Tagihan berhasil ditambahkan!';
            } else {
                $error = 'Gagal menambahkan tagihan!';
            }
        }
    }

    // Hapus Tagihan
    if ($action === 'hapus') {
        $id = intval($_POST['id']);
        $stmt = $conn->prepare("DELETE FROM tagihan WHERE id = ? AND status = 'belum_bayar'");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $success = 'Tagihan berhasil dihapus!';
        } else {
            $error = 'Gagal menghapus tagihan (hanya tagihan belum bayar yang dapat dihapus)!';
        }
    }
}

// Ambil semua tagihan
$tagihan_list = $conn->query("
    SELECT t.*, u.nama as nama_user, jr.nama_retribusi, o.nama_objek, o.kode_objek
    FROM tagihan t
    JOIN users u ON t.user_id = u.id
    JOIN objek_retribusi o ON t.objek_retribusi_id = o.id
    JOIN jenis_retribusi jr ON o.jenis_retribusi_id = jr.id
    ORDER BY t.created_at DESC
");
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
            <li class="nav-item mb-1"><a href="kelola-tagihan.php" class="nav-link text-white active"><i class="bi bi-receipt me-2"></i>Kelola Tagihan</a></li>
            <li class="nav-item mb-1"><a href="laporan.php" class="nav-link text-white"><i class="bi bi-bar-chart me-2"></i>Laporan</a></li>
        </ul>
        <hr>
        <a href="../logout.php" class="btn btn-outline-danger btn-sm"><i class="bi bi-box-arrow-right"></i> Logout</a>
    </nav>

    <!-- Content -->
    <div class="flex-grow-1 p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-receipt"></i> Kelola Tagihan</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambah">
                <i class="bi bi-plus-circle"></i> Tambah Tagihan
            </button>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="bi bi-check-circle"></i> <?= $success ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="bi bi-exclamation-triangle"></i> <?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Tabel Tagihan -->
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>No</th>
                                <th>No. Tagihan</th>
                                <th>Penyewa</th>
                                <th>Objek</th>
                                <th>Jenis Retribusi</th>
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
                            while ($row = $tagihan_list->fetch_assoc()):
                            ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><small><?= $row['no_tagihan'] ?></small></td>
                                <td><?= $row['nama_user'] ?></td>
                                <td><?= $row['nama_objek'] ?><br><small class="text-muted"><?= $row['kode_objek'] ?></small></td>
                                <td><?= $row['nama_retribusi'] ?></td>
                                <td><?= getNamaBulan($row['periode_bulan']) ?> <?= $row['periode_tahun'] ?></td>
                                <td><?= formatRupiah($row['jumlah']) ?></td>
                                <td class="text-danger"><?= formatRupiah($row['denda']) ?></td>
                                <td class="fw-bold"><?= formatRupiah($row['total']) ?></td>
                                <td><?= formatTanggal($row['jatuh_tempo']) ?></td>
                                <td><?= getStatusBadge($row['status']) ?></td>
                                <td>
                                    <?php if ($row['status'] === 'belum_bayar'): ?>
                                    <form method="POST" class="d-inline"
                                          onsubmit="return confirm('Hapus tagihan ini?')">
                                        <input type="hidden" name="action" value="hapus">
                                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                        <button class="btn btn-sm btn-danger">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah Tagihan -->
<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-plus-circle"></i> Tambah Tagihan</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="tambah">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Objek Retribusi</label>
                        <select class="form-select" name="objek_retribusi_id" required id="selectObjek">
                            <option value="">-- Pilih Objek --</option>
                            <?php
                            $objek_arr = [];
                            while ($o = $objek_list->fetch_assoc()):
                                $objek_arr[] = $o;
                            ?>
                            <option value="<?= $o['id'] ?>" data-tarif="<?= $o['tarif'] ?>">
                                <?= $o['nama_objek'] ?> - <?= $o['nama_user'] ?> (<?= $o['nama_retribusi'] ?>)
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <div class="mb-3">
                                <label class="form-label">Periode Bulan</label>
                                <select class="form-select" name="periode_bulan" required>
                                    <?php for ($m = 1; $m <= 12; $m++): ?>
                                        <option value="<?= $m ?>" <?= $m == date('n') ? 'selected' : '' ?>>
                                            <?= getNamaBulan($m) ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="mb-3">
                                <label class="form-label">Periode Tahun</label>
                                <input type="number" class="form-control" name="periode_tahun"
                                       value="<?= date('Y') ?>" min="2020" max="2030" required>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Jumlah (Rp)</label>
                        <input type="number" class="form-control" name="jumlah"
                               id="inputJumlah" min="0" step="500" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Jatuh Tempo</label>
                        <input type="date" class="form-control" name="jatuh_tempo"
                               value="<?= date('Y-m-') . '15' ?>" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Simpan Tagihan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Auto-fill jumlah berdasarkan tarif objek
document.getElementById('selectObjek').addEventListener('change', function() {
    const tarif = this.options[this.selectedIndex].dataset.tarif;
    if (tarif) document.getElementById('inputJumlah').value = tarif;
});
</script>
</body>
</html>
<?php closeConnection($conn); ?>