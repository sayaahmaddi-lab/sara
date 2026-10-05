<?php
/**
 * File: admin/kelola-user.php
 * Deskripsi: Kelola data user/penyewa
 */
$page_title = 'Kelola User';
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) redirect('../login.php');

$conn    = getConnection();
$success = '';
$error   = '';

// Proses aksi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];

    // Ubah Status User
    if ($action === 'toggle_status') {
        $id     = intval($_POST['id']);
        $status = sanitize($_POST['status']);
        $new_status = $status === 'aktif' ? 'nonaktif' : 'aktif';

        $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ? AND role = 'user'");
        $stmt->bind_param("si", $new_status, $id);
        $success = $stmt->execute() ? "Status user berhasil diubah!" : "Gagal mengubah status!";
    }

    // Hapus User
    if ($action === 'hapus') {
        $id = intval($_POST['id']);
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'user'");
        $stmt->bind_param("i", $id);
        $success = $stmt->execute() ? "User berhasil dihapus!" : "Gagal menghapus user!";
    }
}

// Ambil semua user (bukan admin)
$search = sanitize($_GET['search'] ?? '');
$where  = "WHERE role = 'user'";
if ($search) {
    $where .= " AND (nama LIKE '%$search%' OR email LIKE '%$search%')";
}

$users = $conn->query("SELECT * FROM users $where ORDER BY created_at DESC");
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
            <li class="nav-item mb-1"><a href="kelola-user.php" class="nav-link text-white active"><i class="bi bi-people me-2"></i>Kelola User</a></li>
            <li class="nav-item mb-1"><a href="kelola-tagihan.php" class="nav-link text-white"><i class="bi bi-receipt me-2"></i>Kelola Tagihan</a></li>
            <li class="nav-item mb-1"><a href="laporan.php" class="nav-link text-white"><i class="bi bi-bar-chart me-2"></i>Laporan</a></li>
        </ul>
        <hr>
        <a href="../logout.php" class="btn btn-outline-danger btn-sm"><i class="bi bi-box-arrow-right"></i> Logout</a>
    </nav>

    <!-- Content -->
    <div class="flex-grow-1 p-4">
        <h2 class="mb-4"><i class="bi bi-people"></i> Kelola User</h2>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="bi bi-check-circle"></i> <?= $success ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Search -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-8">
                        <input type="text" class="form-control" name="search"
                               placeholder="Cari nama atau email..." value="<?= $search ?>">
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search"></i> Cari
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabel User -->
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>No</th>
                                <th>Nama</th>
                                <th>Email</th>
                                <th>No. HP</th>
                                <th>Alamat</th>
                                <th>Status</th>
                                <th>Terdaftar</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $no = 1;
                            if ($users->num_rows > 0):
                                while ($row = $users->fetch_assoc()):
                            ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><?= $row['nama'] ?></td>
                                <td><?= $row['email'] ?></td>
                                <td><?= $row['no_hp'] ?></td>
                                <td><small><?= $row['alamat'] ?></small></td>
                                <td><?= getStatusBadge($row['status']) ?></td>
                                <td><?= formatTanggal($row['created_at']) ?></td>
                                <td>
                                    <!-- Toggle Status -->
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                        <input type="hidden" name="status" value="<?= $row['status'] ?>">
                                        <button class="btn btn-sm <?= $row['status'] === 'aktif' ? 'btn-warning' : 'btn-success' ?>"
                                                title="<?= $row['status'] === 'aktif' ? 'Nonaktifkan' : 'Aktifkan' ?>">
                                            <i class="bi bi-<?= $row['status'] === 'aktif' ? 'pause-circle' : 'play-circle' ?>"></i>
                                        </button>
                                    </form>

                                    <!-- Hapus -->
                                    <form method="POST" class="d-inline"
                                          onsubmit="return confirm('Yakin hapus user ini? Semua data terkait akan terhapus!')">
                                        <input type="hidden" name="action" value="hapus">
                                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                        <button class="btn btn-sm btn-danger" title="Hapus">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php
                                endwhile;
                            else:
                            ?>
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                                    <p class="text-muted mt-2">Tidak ada data user</p>
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