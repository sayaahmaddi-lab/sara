<?php
/**
 * File: install.php
 * Deskripsi: Auto installer - setup database & akun sekaligus
 * ⚠️ HAPUS FILE INI SETELAH INSTALASI SELESAI!
 */
require_once 'config/database.php';

$conn    = getConnection();
$results = [];

// =============================================
// STEP 1: Buat tabel jika belum ada
// =============================================
$tables = [
    "CREATE TABLE IF NOT EXISTS users (
        id INT PRIMARY KEY AUTO_INCREMENT,
        nama VARCHAR(100) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        no_hp VARCHAR(15),
        alamat TEXT,
        role ENUM('admin','user') DEFAULT 'user',
        status ENUM('aktif','nonaktif') DEFAULT 'aktif',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )",

    "CREATE TABLE IF NOT EXISTS jenis_retribusi (
        id INT PRIMARY KEY AUTO_INCREMENT,
        kode_jenis VARCHAR(20) UNIQUE NOT NULL,
        nama_retribusi VARCHAR(100) NOT NULL,
        deskripsi TEXT,
        tarif DECIMAL(10,2) NOT NULL,
        satuan VARCHAR(50),
        status ENUM('aktif','nonaktif') DEFAULT 'aktif',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )",

    "CREATE TABLE IF NOT EXISTS objek_retribusi (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        jenis_retribusi_id INT NOT NULL,
        kode_objek VARCHAR(50) UNIQUE NOT NULL,
        nama_objek VARCHAR(100) NOT NULL,
        lokasi TEXT,
        luas DECIMAL(10,2),
        status ENUM('aktif','nonaktif') DEFAULT 'aktif',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (jenis_retribusi_id) REFERENCES jenis_retribusi(id) ON DELETE CASCADE
    )",

    "CREATE TABLE IF NOT EXISTS tagihan (
        id INT PRIMARY KEY AUTO_INCREMENT,
        objek_retribusi_id INT NOT NULL,
        user_id INT NOT NULL,
        no_tagihan VARCHAR(50) UNIQUE NOT NULL,
        periode_bulan INT NOT NULL,
        periode_tahun INT NOT NULL,
        jumlah DECIMAL(10,2) NOT NULL,
        denda DECIMAL(10,2) DEFAULT 0,
        total DECIMAL(10,2) NOT NULL,
        status ENUM('belum_bayar','sudah_bayar','kadaluarsa') DEFAULT 'belum_bayar',
        jatuh_tempo DATE NOT NULL,
        tanggal_bayar DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (objek_retribusi_id) REFERENCES objek_retribusi(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )",

    "CREATE TABLE IF NOT EXISTS pembayaran (
        id INT PRIMARY KEY AUTO_INCREMENT,
        tagihan_id INT NOT NULL,
        user_id INT NOT NULL,
        no_pembayaran VARCHAR(50) UNIQUE NOT NULL,
        metode_pembayaran ENUM('online','tunai','transfer','qris') NOT NULL,
        jumlah_bayar DECIMAL(10,2) NOT NULL,
        tanggal_bayar DATETIME NOT NULL,
        bukti_bayar VARCHAR(255),
        status ENUM('pending','success','failed','expired') DEFAULT 'pending',
        payment_gateway VARCHAR(50),
        transaction_id VARCHAR(100),
        keterangan TEXT,
        verified_by INT NULL,
        verified_at DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (tagihan_id) REFERENCES tagihan(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )",

    "CREATE TABLE IF NOT EXISTS log_aktivitas (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        aktivitas VARCHAR(255) NOT NULL,
        keterangan TEXT,
        ip_address VARCHAR(45),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )",
];

foreach ($tables as $sql) {
    if ($conn->query($sql)) {
        preg_match('/CREATE TABLE IF NOT EXISTS (\w+)/', $sql, $m);
        $results[] = ['type' => 'success', 'msg' => "Tabel <strong>{$m[1]}</strong> siap"];
    } else {
        $results[] = ['type' => 'danger', 'msg' => "Error: " . $conn->error];
    }
}

// =============================================
// STEP 2: Insert jenis retribusi
// =============================================
$jenis = [
    ['RT-001', 'Retribusi Pasar',         'Retribusi pemakaian tempat berjualan di pasar', 50000, 'per bulan'],
    ['RT-002', 'Retribusi Parkir',        'Retribusi tempat parkir kendaraan',              5000,  'per hari'],
    ['RT-003', 'Retribusi Terminal',      'Retribusi penggunaan terminal',                  75000, 'per bulan'],
    ['RT-004', 'Retribusi Tempat Wisata', 'Retribusi tempat wisata dan rekreasi',           10000, 'per orang'],
    ['RT-005', 'Retribusi Kebersihan',    'Retribusi pelayanan kebersihan',                 25000, 'per bulan'],
];

$stmt = $conn->prepare("
    INSERT IGNORE INTO jenis_retribusi (kode_jenis, nama_retribusi, deskripsi, tarif, satuan)
    VALUES (?, ?, ?, ?, ?)
");

foreach ($jenis as $j) {
    $stmt->bind_param("sssds", $j[0], $j[1], $j[2], $j[3], $j[4]);
    $stmt->execute();
}
$results[] = ['type' => 'success', 'msg' => "Data jenis retribusi siap"];

// =============================================
// STEP 3: Buat / Update akun admin & user
// =============================================
$hash_admin = password_hash('admin123', PASSWORD_DEFAULT);
$hash_user  = password_hash('user123',  PASSWORD_DEFAULT);

$accounts = [
    [
        'nama'   => 'Administrator',
        'email'  => 'admin@retribusi.com',
        'hash'   => $hash_admin,
        'role'   => 'admin',
        'no_hp'  => '081234567890',
        'alamat' => 'Kantor Pemerintah Daerah',
        'label'  => 'Admin',
        'pass'   => 'admin123',
    ],
    [
        'nama'   => 'Budi Santoso',
        'email'  => 'budi@gmail.com',
        'hash'   => $hash_user,
        'role'   => 'user',
        'no_hp'  => '081234567891',
        'alamat' => 'Jl. Merdeka No. 123',
        'label'  => 'User',
        'pass'   => 'user123',
    ],
];

foreach ($accounts as $acc) {
    // Cek apakah sudah ada
    $cek = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $cek->bind_param("s", $acc['email']);
    $cek->execute();
    $exist = $cek->get_result()->num_rows;

    if ($exist > 0) {
        // UPDATE jika sudah ada
        $upd = $conn->prepare("
            UPDATE users 
            SET nama = ?, password = ?, role = ?, no_hp = ?, alamat = ?, status = 'aktif'
            WHERE email = ?
        ");
        $upd->bind_param("ssssss",
            $acc['nama'], $acc['hash'], $acc['role'],
            $acc['no_hp'], $acc['alamat'], $acc['email']
        );
        $upd->execute();
        $results[] = [
            'type' => 'info',
            'msg'  => "Akun <strong>{$acc['label']}</strong> ({$acc['email']}) " .
                      "diupdate → password: <code>{$acc['pass']}</code>"
        ];
    } else {
        // INSERT jika belum ada
        $ins = $conn->prepare("
            INSERT INTO users (nama, email, password, role, no_hp, alamat, status)
            VALUES (?, ?, ?, ?, ?, ?, 'aktif')
        ");
        $ins->bind_param("ssssss",
            $acc['nama'], $acc['email'], $acc['hash'],
            $acc['role'], $acc['no_hp'], $acc['alamat']
        );
        $ins->execute();
        $results[] = [
            'type' => 'success',
            'msg'  => "Akun <strong>{$acc['label']}</strong> ({$acc['email']}) " .
                      "dibuat → password: <code>{$acc['pass']}</code>"
        ];
    }

    // Verifikasi password
    $ver = $conn->prepare("SELECT password FROM users WHERE email = ?");
    $ver->bind_param("s", $acc['email']);
    $ver->execute();
    $row = $ver->get_result()->fetch_assoc();

    $valid = password_verify($acc['pass'], $row['password']);
    $results[] = [
        'type' => $valid ? 'success' : 'danger',
        'msg'  => "Verifikasi password <strong>{$acc['label']}</strong>: " .
                  ($valid ? '✅ Valid' : '❌ Hash tidak cocok!')
    ];
}

// =============================================
// STEP 4: Insert objek & tagihan demo
// =============================================
$user_demo = $conn->query("SELECT id FROM users WHERE email = 'budi@gmail.com'")->fetch_assoc();
$jenis_demo = $conn->query("SELECT id FROM jenis_retribusi WHERE kode_jenis = 'RT-001'")->fetch_assoc();

if ($user_demo && $jenis_demo) {
    // Insert objek demo
    $conn->query("
        INSERT IGNORE INTO objek_retribusi 
            (user_id, jenis_retribusi_id, kode_objek, nama_objek, lokasi, luas, status)
        VALUES 
            ({$user_demo['id']}, {$jenis_demo['id']}, 'OBJ-001', 'Kios Pasar A-12', 
             'Pasar Sentral Blok A No. 12', 6.00, 'aktif')
    ");
    
    $objek = $conn->query("SELECT id FROM objek_retribusi WHERE kode_objek = 'OBJ-001'")->fetch_assoc();

    if ($objek) {
        // Insert tagihan demo bulan ini
        $bulan = date('n');
        $tahun = date('Y');
        $no_tagihan = 'TAG-DEMO-' . date('Ymd');
        $jatuh_tempo = date('Y-m-15');

        $conn->query("
            INSERT IGNORE INTO tagihan 
                (objek_retribusi_id, user_id, no_tagihan, periode_bulan, periode_tahun,
                 jumlah, denda, total, jatuh_tempo)
            VALUES 
                ({$objek['id']}, {$user_demo['id']}, '$no_tagihan', 
                 $bulan, $tahun, 50000, 0, 50000, '$jatuh_tempo')
        ");
        $results[] = ['type' => 'success', 'msg' => "Data demo (objek & tagihan) siap"];
    }
}

closeConnection($conn);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installer - Retribusi Daerah</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body class="bg-light">
    <div class="container mt-5 mb-5">
        <div class="row justify-content-center">
            <div class="col-md-8">

                <!-- Header -->
                <div class="card shadow mb-4">
                    <div class="card-header bg-primary text-white text-center py-4">
                        <i class="bi bi-building" style="font-size: 3rem;"></i>
                        <h3 class="mt-2 mb-0">Installer Sistem Retribusi Daerah</h3>
                        <p class="mb-0 opacity-75">Setup otomatis database & akun</p>
                    </div>
                </div>

                <!-- Log Proses -->
                <div class="card shadow mb-4">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0"><i class="bi bi-terminal"></i> Log Instalasi</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($results as $r): ?>
                            <div class="alert alert-<?= $r['type'] ?> py-2 mb-2">
                                <?= $r['type'] === 'success' ? '✅' : ($r['type'] === 'danger' ? '❌' : 'ℹ️') ?>
                                <?= $r['msg'] ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Info Akun -->
                <div class="card shadow mb-4 border-success">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="bi bi-person-check"></i> Akun Login</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="card border-danger">
                                    <div class="card-body text-center">
                                        <span class="badge bg-danger mb-2">ADMIN</span>
                                        <p class="mb-1"><i class="bi bi-envelope"></i> admin@retribusi.com</p>
                                        <p class="mb-0"><i class="bi bi-lock"></i> <code>admin123</code></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="card border-primary">
                                    <div class="card-body text-center">
                                        <span class="badge bg-primary mb-2">USER</span>
                                        <p class="mb-1"><i class="bi bi-envelope"></i> budi@gmail.com</p>
                                        <p class="mb-0"><i class="bi bi-lock"></i> <code>user123</code></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Peringatan -->
                <div class="alert alert-warning shadow">
                    <h6><i class="bi bi-exclamation-triangle"></i> <strong>Penting!</strong></h6>
                    <p class="mb-1">Segera hapus file ini setelah instalasi selesai:</p>
                    <ul class="mb-0">
                        <li><code>install.php</code></li>
                        <li><code>fix-password.php</code> (jika ada)</li>
                        <li><code>generate-hash.php</code> (jika ada)</li>
                    </ul>
                </div>

                <!-- Tombol -->
                <div class="d-grid gap-2">
                    <a href="login.php" class="btn btn-primary btn-lg">
                        <i class="bi bi-box-arrow-in-right"></i> Pergi ke Halaman Login
                    </a>
                    <a href="admin/index.php" class="btn btn-outline-danger">
                        <i class="bi bi-speedometer2"></i> Langsung ke Dashboard Admin
                    </a>
                </div>

            </div>
        </div>
    </div>
</body>
</html>