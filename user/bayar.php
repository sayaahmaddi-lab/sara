<?php
/**
 * File: user/bayar.php
 * Deskripsi: Halaman proses pembayaran tagihan
 */
$page_title = 'Pembayaran - Retribusi Daerah';
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) redirect('../login.php');
if (isAdmin()) redirect('../admin/index.php');

$conn     = getConnection();
$user_id  = $_SESSION['user_id'];
$tagihan_id = intval($_GET['id'] ?? 0);
$success  = '';
$error    = '';

// Ambil detail tagihan milik user
$stmt = $conn->prepare("
    SELECT t.*, jr.nama_retribusi, o.nama_objek, o.kode_objek, o.lokasi
    FROM tagihan t
    JOIN objek_retribusi o ON t.objek_retribusi_id = o.id
    JOIN jenis_retribusi jr ON o.jenis_retribusi_id = jr.id
    WHERE t.id = ? AND t.user_id = ? AND t.status = 'belum_bayar'
");
$stmt->bind_param("ii", $tagihan_id, $user_id);
$stmt->execute();
$tagihan = $stmt->get_result()->fetch_assoc();

// Jika tagihan tidak ditemukan
if (!$tagihan) {
    redirect('tagihan.php');
}

// Proses pembayaran
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $metode        = sanitize($_POST['metode_pembayaran']);
    $no_pembayaran = generateKode('PAY');
    $bukti_bayar   = '';

    // Jika metode transfer, upload bukti
    if ($metode === 'transfer' && isset($_FILES['bukti_bayar'])) {
        $upload = uploadFile($_FILES['bukti_bayar'], '../uploads/bukti/');
        if ($upload['success']) {
            $bukti_bayar = $upload['filename'];
        } else {
            $error = $upload['message'];
        }
    }

    if (!$error) {
        // Simpan data pembayaran
        $stmt = $conn->prepare("
            INSERT INTO pembayaran 
            (tagihan_id, user_id, no_pembayaran, metode_pembayaran, jumlah_bayar, tanggal_bayar, bukti_bayar, status)
            VALUES (?, ?, ?, ?, ?, NOW(), ?, 'success')
        ");
        $stmt->bind_param(
            "iissds",
            $tagihan_id, $user_id, $no_pembayaran,
            $metode, $tagihan['total'], $bukti_bayar
        );

        if ($stmt->execute()) {
            // Update status tagihan
            $stmt2 = $conn->prepare("
                UPDATE tagihan SET status = 'sudah_bayar', tanggal_bayar = NOW() WHERE id = ?
            ");
            $stmt2->bind_param("i", $tagihan_id);
            $stmt2->execute();

            // Log aktivitas
            logAktivitas($conn, $user_id, 'Pembayaran', "Membayar tagihan: {$tagihan['no_tagihan']}");

            $success = $no_pembayaran;
        } else {
            $error = 'Pembayaran gagal! Silakan coba lagi.';
        }
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
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand fw-bold" href="dashboard.php">
                <i class="bi bi-building"></i> Retribusi Daerah
            </a>
            <a href="tagihan.php" class="btn btn-outline-light btn-sm">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>
    </nav>

    <div class="container mt-4">
        <h2 class="mb-4"><i class="bi bi-credit-card"></i> Pembayaran Tagihan</h2>

        <?php if ($success): ?>
        <!-- Sukses Pembayaran -->
        <div class="card border-success">
            <div class="card-body text-center py-5">
                <i class="bi bi-check-circle-fill text-success" style="font-size: 5rem;"></i>
                <h3 class="mt-3 text-success">Pembayaran Berhasil!</h3>
                <p class="text-muted">No. Pembayaran: <strong><?= $success ?></strong></p>
                <div class="mt-4">
                    <a href="cetak-kwitansi.php?no=<?= $success ?>" class="btn btn-success me-2">
                        <i class="bi bi-printer"></i> Cetak Kwitansi
                    </a>
                    <a href="dashboard.php" class="btn btn-primary">
                        <i class="bi bi-house"></i> Kembali ke Dashboard
                    </a>
                </div>
            </div>
        </div>

        <?php else: ?>
        <!-- Form Pembayaran -->
        <div class="row">
            <!-- Detail Tagihan -->
            <div class="col-md-5 mb-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-receipt"></i> Detail Tagihan</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?= $error ?></div>
                        <?php endif; ?>
                        <table class="table table-borderless">
                            <tr>
                                <th width="140">No. Tagihan</th>
                                <td>: <?= $tagihan['no_tagihan'] ?></td>
                            </tr>
                            <tr>
                                <th>Objek</th>
                                <td>: <?= $tagihan['nama_objek'] ?></td>
                            </tr>
                            <tr>
                                <th>Kode Objek</th>
                                <td>: <?= $tagihan['kode_objek'] ?></td>
                            </tr>
                            <tr>
                                <th>Jenis</th>
                                <td>: <?= $tagihan['nama_retribusi'] ?></td>
                            </tr>
                            <tr>
                                <th>Lokasi</th>
                                <td>: <?= $tagihan['lokasi'] ?></td>
                            </tr>
                            <tr>
                                <th>Periode</th>
                                <td>: <?= getNamaBulan($tagihan['periode_bulan']) ?> <?= $tagihan['periode_tahun'] ?></td>
                            </tr>
                            <tr>
                                <th>Jatuh Tempo</th>
                                <td>: <?= formatTanggal($tagihan['jatuh_tempo']) ?></td>
                            </tr>
                            <tr>
                                <th>Jumlah</th>
                                <td>: <?= formatRupiah($tagihan['jumlah']) ?></td>
                            </tr>
                            <tr>
                                <th>Denda</th>
                                <td>: <span class="text-danger"><?= formatRupiah($tagihan['denda']) ?></span></td>
                            </tr>
                            <tr class="table-warning">
                                <th>Total Bayar</th>
                                <th>: <?= formatRupiah($tagihan['total']) ?></th>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Metode Pembayaran -->
            <div class="col-md-7 mb-4">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="bi bi-wallet2"></i> Pilih Metode Pembayaran</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data" id="formBayar">
                            <div class="row g-3 mb-4">
                                <!-- Online -->
                                <div class="col-6 col-md-6">
                                    <input type="radio" class="btn-check" name="metode_pembayaran" 
                                           id="online" value="online" required>
                                    <label class="btn btn-outline-primary w-100 py-3" for="online">
                                        <i class="bi bi-globe d-block" style="font-size: 1.8rem;"></i>
                                        <small>Payment Gateway</small>
                                    </label>
                                </div>
                                <!-- QRIS -->
                                <div class="col-6 col-md-6">
                                    <input type="radio" class="btn-check" name="metode_pembayaran" 
                                           id="qris" value="qris">
                                    <label class="btn btn-outline-dark w-100 py-3" for="qris">
                                        <i class="bi bi-qr-code d-block" style="font-size: 1.8rem;"></i>
                                        <small>QRIS</small>
                                    </label>
                                </div>
                                <!-- Transfer -->
                                <div class="col-6 col-md-6">
                                    <input type="radio" class="btn-check" name="metode_pembayaran" 
                                           id="transfer" value="transfer">
                                    <label class="btn btn-outline-info w-100 py-3" for="transfer">
                                        <i class="bi bi-bank d-block" style="font-size: 1.8rem;"></i>
                                        <small>Transfer Bank</small>
                                    </label>
                                </div>
                                <!-- Tunai -->
                                <div class="col-6 col-md-6">
                                    <input type="radio" class="btn-check" name="metode_pembayaran" 
                                           id="tunai" value="tunai">
                                    <label class="btn btn-outline-success w-100 py-3" for="tunai">
                                        <i class="bi bi-cash-stack d-block" style="font-size: 1.8rem;"></i>
                                        <small>Tunai</small>
                                    </label>
                                </div>
                            </div>

                            <!-- Info Transfer Bank -->
                            <div id="infoTransfer" class="alert alert-info d-none">
                                <h6><i class="bi bi-bank"></i> Informasi Rekening Transfer</h6>
                                <p class="mb-1">Bank BRI: <strong>1234-5678-9012</strong></p>
                                <p class="mb-1">Bank BNI: <strong>9876-5432-1098</strong></p>
                                <p class="mb-2">A/N: <strong>Pemerintah Daerah Kab. XYZ</strong></p>
                                <div class="mb-3">
                                    <label class="form-label">Upload Bukti Transfer</label>
                                    <input type="file" class="form-control" name="bukti_bayar" 
                                           accept="image/*,application/pdf">
                                    <small class="text-muted">Format: JPG, PNG, PDF. Max 2MB</small>
                                </div>
                            </div>

                            <!-- Info QRIS -->
                            <div id="infoQris" class="text-center d-none">
                                <img src="../assets/images/qris-dummy.png" alt="QRIS" 
                                     class="img-fluid" style="max-width: 250px;"
                                     onerror="this.src='https://via.placeholder.com/250x250?text=QRIS+Code'">
                                <p class="text-muted mt-2">Scan QR Code untuk melakukan pembayaran</p>
                            </div>

                            <!-- Info Tunai -->
                            <div id="infoTunai" class="alert alert-warning d-none">
                                <i class="bi bi-info-circle"></i>
                                Pembayaran tunai dapat dilakukan di <strong>Kantor Dinas Pendapatan Daerah</strong>
                                pada jam kerja (Senin-Jumat, 08.00-15.00 WIB).
                            </div>

                            <div class="d-grid mt-4">
                                <button type="submit" class="btn btn-primary btn-lg" 
                                        onclick="return confirm('Konfirmasi pembayaran sebesar <?= formatRupiah($tagihan['total']) ?>?')">
                                    <i class="bi bi-check-circle"></i> 
                                    Bayar <?= formatRupiah($tagihan['total']) ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <footer class="bg-light text-center py-3 mt-5">
        <small class="text-muted">© <?= date('Y') ?> Sistem Retribusi Daerah</small>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Tampilkan info berdasarkan metode pembayaran
        document.querySelectorAll('input[name="metode_pembayaran"]').forEach(function(el) {
            el.addEventListener('change', function() {
                document.getElementById('infoTransfer').classList.add('d-none');
                document.getElementById('infoQris').classList.add('d-none');
                document.getElementById('infoTunai').classList.add('d-none');

                if (this.value === 'transfer') {
                    document.getElementById('infoTransfer').classList.remove('d-none');
                } else if (this.value === 'qris') {
                    document.getElementById('infoQris').classList.remove('d-none');
                } else if (this.value === 'tunai') {
                    document.getElementById('infoTunai').classList.remove('d-none');
                }
            });
        });
    </script>
</body>
</html>
<?php closeConnection($conn); ?>