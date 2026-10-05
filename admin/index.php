<?php
/**
 * File: user/cetak-kwitansi.php
 * Deskripsi: Cetak kwitansi pembayaran
 */
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) redirect('../login.php');

$conn    = getConnection();
$user_id = $_SESSION['user_id'];

// Ambil berdasarkan no_pembayaran atau tagihan_id
$no_bayar  = sanitize($_GET['no'] ?? '');
$tag_id    = intval($_GET['id'] ?? 0);

if ($no_bayar) {
    $stmt = $conn->prepare("
        SELECT p.*, t.no_tagihan, t.periode_bulan, t.periode_tahun,
               t.jumlah, t.denda, t.total,
               jr.nama_retribusi, o.nama_objek, o.kode_objek, o.lokasi,
               u.nama as nama_penyewa, u.alamat, u.no_hp
        FROM pembayaran p
        JOIN tagihan t ON p.tagihan_id = t.id
        JOIN objek_retribusi o ON t.objek_retribusi_id = o.id
        JOIN jenis_retribusi jr ON o.jenis_retribusi_id = jr.id
        JOIN users u ON p.user_id = u.id
        WHERE p.no_pembayaran = ? AND p.user_id = ?
    ");
    $stmt->bind_param("si", $no_bayar, $user_id);
} else {
    $stmt = $conn->prepare("
        SELECT p.*, t.no_tagihan, t.periode_bulan, t.periode_tahun,
               t.jumlah, t.denda, t.total,
               jr.nama_retribusi, o.nama_objek, o.kode_objek, o.lokasi,
               u.nama as nama_penyewa, u.alamat, u.no_hp
        FROM pembayaran p
        JOIN tagihan t ON p.tagihan_id = t.id
        JOIN objek_retribusi o ON t.objek_retribusi_id = o.id
        JOIN jenis_retribusi jr ON o.jenis_retribusi_id = jr.id
        JOIN users u ON p.user_id = u.id
        WHERE p.tagihan_id = ? AND p.user_id = ? AND p.status = 'success'
    ");
    $stmt->bind_param("ii", $tag_id, $user_id);
}

$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

if (!$data) redirect('riwayat.php');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kwitansi - <?= $data['no_pembayaran'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f0f0f0; }
        .kwitansi {
            max-width: 700px;
            margin: 30px auto;
            background: white;
            padding: 30px;
            border: 1px solid #ddd;
        }
        .kwitansi-header {
            border-bottom: 3px solid #0d6efd;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .logo-area { display: flex; align-items: center; gap: 15px; }
        .logo-area img { width: 70px; }
        .instansi h4 { margin: 0; font-size: 1.1rem; color: #0d6efd; }
        .instansi p { margin: 0; font-size: 0.85rem; color: #555; }
        table.detail td, table.detail th { padding: 5px 10px; }
        .total-row { background: #0d6efd; color: white; }
        .footer-kwitansi { border-top: 2px solid #0d6efd; margin-top: 20px; padding-top: 15px; }
        @media print {
            body { background: white; }
            .no-print { display: none; }
            .kwitansi { margin: 0; border: none; box-shadow: none; }
        }
    </style>
</head>
<body>
    <!-- Tombol Cetak -->
    <div class="text-center my-3 no-print">
        <button onclick="window.print()" class="btn btn-primary">
            <i class="bi bi-printer"></i> Cetak Kwitansi
        </button>
        <a href="riwayat.php" class="btn btn-secondary ms-2">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>

    <!-- Kwitansi -->
    <div class="kwitansi shadow">
        <!-- Header -->
        <div class="kwitansi-header">
            <div class="logo-area">
                <div>
                    <img src="../assets/images/logo.png" alt="Logo"
                         onerror="this.style.display='none'">
                </div>
                <div class="instansi">
                    <h4>PEMERINTAH DAERAH KABUPATEN/KOTA XYZ</h4>
                    <p>DINAS PENDAPATAN DAERAH</p>
                    <p>Jl. Pemerintahan No. 1, Telp. (021) 123-4567</p>
                </div>
            </div>
        </div>

        <!-- Judul -->
        <div class="text-center mb-4">
            <h4 class="fw-bold">KWITANSI PEMBAYARAN RETRIBUSI DAERAH</h4>
            <div class="badge bg-success fs-6">LUNAS</div>
        </div>

        <!-- Detail -->
        <table class="table table-bordered detail">
            <tr>
                <th width="180">No. Pembayaran</th>
                <td><?= $data['no_pembayaran'] ?></td>
                <th width="160">Tanggal Bayar</th>
                <td><?= formatTanggal($data['tanggal_bayar'], true) ?></td>
            </tr>
            <tr>
                <th>No. Tagihan</th>
                <td><?= $data['no_tagihan'] ?></td>
                <th>Metode</th>
                <td><?= strtoupper($data['metode_pembayaran']) ?></td>
            </tr>
            <tr>
                <th>Nama Penyewa</th>
                <td colspan="3"><?= $data['nama_penyewa'] ?></td>
            </tr>
            <tr>
                <th>Alamat</th>
                <td colspan="3"><?= $data['alamat'] ?></td>
            </tr>
            <tr>
                <th>Objek Retribusi</th>
                <td><?= $data['nama_objek'] ?></td>
                <th>Kode Objek</th>
                <td><?= $data['kode_objek'] ?></td>
            </tr>
            <tr>
                <th>Jenis Retribusi</th>
                <td colspan="3"><?= $data['nama_retribusi'] ?></td>
            </tr>
            <tr>
                <th>Lokasi</th>
                <td colspan="3"><?= $data['lokasi'] ?></td>
            </tr>
            <tr>
                <th>Periode</th>
                <td colspan="3"><?= getNamaBulan($data['periode_bulan']) ?> <?= $data['periode_tahun'] ?></td>
            </tr>
            <tr>
                <th>Jumlah Retribusi</th>
                <td colspan="3"><?= formatRupiah($data['jumlah']) ?></td>
            </tr>
            <tr>
                <th>Denda</th>
                <td colspan="3"><?= formatRupiah($data['denda']) ?></td>
            </tr>
            <tr class="total-row">
                <th>TOTAL BAYAR</th>
                <td colspan="3"><strong><?= formatRupiah($data['total']) ?></strong></td>
            </tr>
        </table>

        <!-- Tanda Tangan -->
        <div class="row mt-4">
            <div class="col-6">
                <p class="text-muted small">
                    <i>Kwitansi ini sah dan berlaku sebagai bukti pembayaran resmi</i>
                </p>
            </div>
            <div class="col-6 text-center">
                <p>Petugas Penerima,</p>
                <br><br><br>
                <p><strong>................................</strong></p>
                <p class="text-muted small">NIP. ................................</p>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer-kwitansi text-center">
            <small class="text-muted">
                Dicetak pada: <?= date('d/m/Y H:i:s') ?> | 
                Sistem Retribusi Daerah © <?= date('Y') ?>
            </small>
        </div>
    </div>
</body>
</html>
<?php closeConnection($conn); ?>