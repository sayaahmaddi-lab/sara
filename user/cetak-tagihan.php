<?php
/**
 * File: user/cetak-tagihan.php
 * Deskripsi: Cetak bukti tagihan untuk pembayaran manual di bank/teller
 */
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isLoggedIn()) redirect('../login.php');

$conn       = getConnection();
$user_id    = $_SESSION['user_id'];
$tagihan_id = intval($_GET['id'] ?? 0);

// Ambil detail tagihan milik user
$stmt = $conn->prepare("
    SELECT
        t.*,
        jr.nama_retribusi,
        jr.kode_jenis,
        jr.tarif,
        jr.satuan,
        o.nama_objek,
        o.kode_objek,
        o.lokasi,
        o.luas,
        u.nama    AS nama_penyewa,
        u.email   AS email_penyewa,
        u.no_hp   AS hp_penyewa,
        u.alamat  AS alamat_penyewa
    FROM tagihan t
    JOIN objek_retribusi o  ON t.objek_retribusi_id = o.id
    JOIN jenis_retribusi jr ON o.jenis_retribusi_id  = jr.id
    JOIN users u            ON t.user_id             = u.id
    WHERE t.id = ? AND t.user_id = ?
");
$stmt->bind_param("ii", $tagihan_id, $user_id);
$stmt->execute();
$tagihan = $stmt->get_result()->fetch_assoc();

if (!$tagihan) redirect('tagihan.php');

// Log aktivitas cetak
logAktivitas($conn, $user_id, 'Cetak Tagihan',
             "Mencetak tagihan: {$tagihan['no_tagihan']}");

closeConnection($conn);

// Hitung sisa hari jatuh tempo
$today     = new DateTime();
$jatuh     = new DateTime($tagihan['jatuh_tempo']);
$selisih   = $today->diff($jatuh);
$sisa_hari = $jatuh >= $today ? $selisih->days : -$selisih->days;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bukti Tagihan - <?= $tagihan['no_tagihan'] ?></title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">

    <!-- Custom CSS -->
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>

    <!-- ===================== TOMBOL AKSI ===================== -->
    <div class="btn-wrapper no-print">
        <button onclick="window.print()" class="btn btn-primary btn-lg px-5">
            <i class="bi bi-printer"></i> Cetak Bukti Tagihan
        </button>
        <a href="tagihan.php" class="btn btn-outline-secondary btn-lg">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
        <?php if ($tagihan['status'] === 'belum_bayar'): ?>
        <a href="bayar.php?id=<?= $tagihan['id'] ?>"
           class="btn btn-success btn-lg px-5">
            <i class="bi bi-credit-card"></i> Bayar Online
        </a>
        <?php endif; ?>
    </div>

    <!-- ===================== DOKUMEN ===================== -->
    <div class="dokumen">

        <!-- HEADER -->
        <div class="doc-header">
            <div class="d-flex justify-content-between align-items-center">
                <div class="logo-area">
                    <div class="logo-box">
                        <img src="../assets/images/logo.png"
                             alt="Logo"
                             onerror="this.style.display='none';
                                      this.nextElementSibling.style.display='block'">
                        <i class="bi bi-building" style="display:none;"></i>
                    </div>
                    <div class="instansi">
                        <h3>PEMERINTAH DAERAH KABUPATEN/KOTA XYZ</h3>
                        <p>DINAS PENDAPATAN DAN PENGELOLAAN KEUANGAN DAERAH</p>
                        <p>Jl. Pemerintahan No. 1 | Telp. (021) 123-4567</p>
                        <p>Email: pendapatan@pemda-xyz.go.id</p>
                    </div>
                </div>
                <div class="doc-info">
                    <div class="doc-title">
                        Surat Tagihan<br>Retribusi Daerah
                    </div>
                    <div class="doc-no mt-2">
                        No: <?= $tagihan['no_tagihan'] ?><br>
                        Dicetak: <?= date('d/m/Y H:i') ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- STATUS BANNER -->
        <div class="status-banner <?= $tagihan['status'] ?>">
            <?php if ($tagihan['status'] === 'belum_bayar'): ?>
                <i class="bi bi-exclamation-circle-fill"></i>
                TAGIHAN BELUM DIBAYAR — Harap segera lakukan pembayaran
                sebelum jatuh tempo
            <?php elseif ($tagihan['status'] === 'sudah_bayar'): ?>
                <i class="bi bi-check-circle-fill"></i>
                TAGIHAN INI SUDAH DIBAYAR LUNAS — Dokumen ini hanya untuk arsip
            <?php else: ?>
                <i class="bi bi-x-circle-fill"></i>
                TAGIHAN KADALUARSA — Hubungi petugas untuk informasi lebih lanjut
            <?php endif; ?>
        </div>

        <!-- BODY -->
        <div class="doc-body">

            <!-- DATA PENYEWA & OBJEK -->
            <div class="row mb-3">

                <!-- Data Wajib Retribusi -->
                <div class="col-md-6">
                    <div class="section-title">
                        <i class="bi bi-person"></i> Data Wajib Retribusi
                    </div>
                    <table class="info-table">
                        <tr>
                            <td>Nama</td>
                            <td>:</td>
                            <td><?= $tagihan['nama_penyewa'] ?></td>
                        </tr>
                        <tr>
                            <td>Email</td>
                            <td>:</td>
                            <td><?= $tagihan['email_penyewa'] ?></td>
                        </tr>
                        <tr>
                            <td>No. HP</td>
                            <td>:</td>
                            <td><?= $tagihan['hp_penyewa'] ?></td>
                        </tr>
                        <tr>
                            <td>Alamat</td>
                            <td>:</td>
                            <td><?= $tagihan['alamat_penyewa'] ?></td>
                        </tr>
                    </table>
                </div>

                <!-- Data Objek Retribusi -->
                <div class="col-md-6">
                    <div class="section-title">
                        <i class="bi bi-building"></i> Data Objek Retribusi
                    </div>
                    <table class="info-table">
                        <tr>
                            <td>Kode Objek</td>
                            <td>:</td>
                            <td><?= $tagihan['kode_objek'] ?></td>
                        </tr>
                        <tr>
                            <td>Nama Objek</td>
                            <td>:</td>
                            <td><?= $tagihan['nama_objek'] ?></td>
                        </tr>
                        <tr>
                            <td>Jenis Retribusi</td>
                            <td>:</td>
                            <td><?= $tagihan['nama_retribusi'] ?></td>
                        </tr>
                        <tr>
                            <td>Lokasi</td>
                            <td>:</td>
                            <td><?= $tagihan['lokasi'] ?? '-' ?></td>
                        </tr>
                        <tr>
                            <td>Luas</td>
                            <td>:</td>
                            <td>
                                <?= $tagihan['luas']
                                    ? $tagihan['luas'] . ' m²'
                                    : '-' ?>
                            </td>
                        </tr>
                        <tr>
                            <td>Periode</td>
                            <td>:</td>
                            <td>
                                <?= getNamaBulan($tagihan['periode_bulan']) ?>
                                <?= $tagihan['periode_tahun'] ?>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- ALERT JATUH TEMPO -->
            <?php if ($tagihan['status'] === 'belum_bayar'): ?>
            <div class="jatuh-tempo-alert
                <?php
                if ($sisa_hari < 0)      echo 'bg-danger text-white';
                elseif ($sisa_hari <= 3) echo 'bg-warning text-dark';
                else                     echo 'bg-info text-dark';
                ?>">
                <i class="bi bi-calendar-event fs-5"></i>
                <div>
                    <strong>
                        Jatuh Tempo: <?= formatTanggal($tagihan['jatuh_tempo']) ?>
                    </strong>
                    <?php if ($sisa_hari < 0): ?>
                        &nbsp;|&nbsp; ⚠️ Telah melewati jatuh tempo
                        <strong><?= abs($sisa_hari) ?> hari</strong>
                        — denda berlaku!
                    <?php elseif ($sisa_hari === 0): ?>
                        &nbsp;|&nbsp; ⚠️
                        <strong>Hari ini adalah hari terakhir pembayaran!</strong>
                    <?php elseif ($sisa_hari <= 3): ?>
                        &nbsp;|&nbsp; Sisa
                        <strong><?= $sisa_hari ?> hari</strong>
                        — segera bayar!
                    <?php else: ?>
                        &nbsp;|&nbsp; Sisa
                        <strong><?= $sisa_hari ?> hari</strong>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- RINCIAN TAGIHAN -->
            <div class="section-title">
                <i class="bi bi-receipt"></i> Rincian Tagihan
            </div>
            <div class="tagihan-box">
                <div class="rincian-row">
                    <span class="text-muted">Retribusi Pokok</span>
                    <span class="fw-semibold">
                        <?= formatRupiah($tagihan['jumlah']) ?>
                    </span>
                </div>
                <div class="rincian-row">
                    <span class="text-muted">
                        Denda Keterlambatan
                        <?php if ($tagihan['denda'] > 0): ?>
                            <small class="text-danger">
                                (keterlambatan pembayaran)
                            </small>
                        <?php endif; ?>
                    </span>
                    <span class="fw-semibold text-danger">
                        <?= formatRupiah($tagihan['denda']) ?>
                    </span>
                </div>
                <div class="total-row">
                    <span>TOTAL YANG HARUS DIBAYAR</span>
                    <span><?= formatRupiah($tagihan['total']) ?></span>
                </div>
            </div>

            <!-- REKENING BANK -->
            <?php if ($tagihan['status'] === 'belum_bayar'): ?>
            <div class="section-title">
                <i class="bi bi-bank"></i> Rekening Pembayaran
            </div>
            <div class="bank-section">
                <div class="bank-card">
                    <div class="bank-logo" style="background:#003087;">BRI</div>
                    <div class="bank-info">
                        <p class="bank-name">Bank Rakyat Indonesia (BRI)</p>
                        <p class="bank-rek">0123 - 4567 - 8901 - 234</p>
                        <p class="bank-an">
                            a.n. Pemerintah Daerah Kabupaten/Kota XYZ
                        </p>
                    </div>
                </div>
                <div class="bank-card">
                    <div class="bank-logo" style="background:#f96c00;">BNI</div>
                    <div class="bank-info">
                        <p class="bank-name">Bank Negara Indonesia (BNI)</p>
                        <p class="bank-rek">9876 - 5432 - 1098 - 765</p>
                        <p class="bank-an">
                            a.n. Pemerintah Daerah Kabupaten/Kota XYZ
                        </p>
                    </div>
                </div>
                <div class="bank-card">
                    <div class="bank-logo" style="background:#009944;">
                        Bank<br>Daerah
                    </div>
                    <div class="bank-info">
                        <p class="bank-name">
                            Bank Pembangunan Daerah (BPD) XYZ
                        </p>
                        <p class="bank-rek">1122 - 3344 - 5566 - 778</p>
                        <p class="bank-an">
                            a.n. Dinas Pendapatan Daerah Kab/Kota XYZ
                        </p>
                    </div>
                </div>
                <small class="text-muted d-block mt-1">
                    <i class="bi bi-info-circle"></i>
                    Pastikan nominal transfer sesuai dengan total tagihan di atas.
                    Simpan bukti transfer untuk proses konfirmasi.
                </small>
            </div>

            <!-- INSTRUKSI PEMBAYARAN -->
            <div class="section-title">
                <i class="bi bi-list-check"></i> Tata Cara Pembayaran
            </div>
            <div class="instruksi-box">
                <ol>
                    <li>
                        <strong>Pembayaran di Teller Bank</strong> —
                        Cetak dokumen ini dan tunjukkan kepada teller bank,
                        sebutkan nomor tagihan
                        <strong><?= $tagihan['no_tagihan'] ?></strong>
                        untuk melakukan pembayaran.
                    </li>
                    <li>
                        <strong>Transfer via ATM / Mobile Banking</strong> —
                        Transfer ke salah satu rekening di atas dengan nominal
                        <strong><?= formatRupiah($tagihan['total']) ?></strong>,
                        lalu kirimkan bukti transfer melalui website.
                    </li>
                    <li>
                        <strong>Pembayaran Online</strong> —
                        Login ke website Retribusi Daerah, pilih tagihan ini
                        dan klik tombol <em>"Bayar Online"</em>.
                    </li>
                    <li>
                        <strong>Konfirmasi Pembayaran</strong> —
                        Setelah transfer, konfirmasikan pembayaran dengan
                        mengunggah bukti transfer melalui website atau datang
                        langsung ke kantor.
                    </li>
                    <li>
                        <strong>Simpan Kwitansi</strong> —
                        Setelah pembayaran dikonfirmasi, unduh atau cetak
                        kwitansi sebagai bukti pembayaran resmi.
                    </li>
                </ol>
            </div>
            <?php endif; ?>

            <!-- CATATAN -->
            <div class="p-3 bg-light rounded border mt-2">
                <small class="text-muted">
                    <i class="bi bi-info-circle text-primary"></i>
                    <strong>Catatan:</strong>
                    Dokumen ini dicetak secara otomatis oleh Sistem Informasi
                    Retribusi Daerah Kab/Kota XYZ dan
                    <strong>berlaku tanpa tanda tangan basah</strong>
                    dari pejabat berwenang. Jika terdapat kekeliruan, hubungi
                    Dinas Pendapatan Daerah pada jam kerja
                    <strong>Senin–Jumat pukul 08.00–15.00 WIB</strong>.
                    Telp: (021) 123-4567.
                </small>
            </div>

        </div><!-- End doc-body -->

        <!-- FOOTER DOKUMEN -->
        <div class="doc-footer">
            <span>
                <i class="bi bi-shield-check"></i>
                Dokumen Resmi Pemerintah Daerah Kabupaten/Kota XYZ
            </span>
            <span>
                Sistem Retribusi Daerah © <?= date('Y') ?>
                &nbsp;|&nbsp;
                Dicetak: <?= date('d/m/Y H:i:s') ?>
            </span>
        </div>

    </div><!-- End dokumen -->

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto print jika ada parameter ?print=1
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('print') === '1') {
            window.onload = () => window.print();
        }
    </script>
</body>
</html>