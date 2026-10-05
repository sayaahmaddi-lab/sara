<?php
/**
 * File: fix-password.php
 * Deskripsi: Otomatis memperbaiki password hash di database
 * ⚠️ HAPUS FILE INI SETELAH DIGUNAKAN!
 */
require_once 'config/database.php';

$conn = getConnection();

// Generate hash baru
$hash_admin = password_hash('admin123', PASSWORD_DEFAULT);
$hash_user  = password_hash('user123',  PASSWORD_DEFAULT);

// Update password admin
$stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = 'admin@retribusi.com'");
$stmt->bind_param("s", $hash_admin);
$stmt->execute();
$admin_ok = $stmt->affected_rows;

// Update password user demo
$stmt2 = $conn->prepare("UPDATE users SET password = ? WHERE email = 'budi@gmail.com'");
$stmt2->bind_param("s", $hash_user);

// Jika user belum ada, insert dulu
$cek = $conn->query("SELECT id FROM users WHERE email = 'budi@gmail.com'");
if ($cek->num_rows === 0) {
    $nama   = 'Budi Santoso';
    $email  = 'budi@gmail.com';
    $no_hp  = '081234567891';
    $alamat = 'Jl. Merdeka No. 123';
    $role   = 'user';
    $status = 'aktif';

    $ins = $conn->prepare("
        INSERT INTO users (nama, email, password, no_hp, alamat, role, status)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $ins->bind_param("sssssss", $nama, $email, $hash_user, $no_hp, $alamat, $role, $status);
    $ins->execute();
    $user_ok = $ins->affected_rows;
} else {
    $stmt2->execute();
    $user_ok = $stmt2->affected_rows;
}

$stmt->close();
$stmt2->close();
closeConnection($conn);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Fix Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header bg-success text-white">
                        <h4 class="mb-0">✅ Fix Password Selesai</h4>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered">
                            <thead class="table-dark">
                                <tr>
                                    <th>Akun</th>
                                    <th>Email</th>
                                    <th>Password</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><span class="badge bg-danger">Admin</span></td>
                                    <td>admin@retribusi.com</td>
                                    <td><code>admin123</code></td>
                                    <td>
                                        <?php if ($admin_ok > 0): ?>
                                            <span class="badge bg-success">✅ Berhasil</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning">⚠️ Tidak berubah</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td><span class="badge bg-primary">User</span></td>
                                    <td>budi@gmail.com</td>
                                    <td><code>user123</code></td>
                                    <td>
                                        <?php if ($user_ok > 0): ?>
                                            <span class="badge bg-success">✅ Berhasil</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning">⚠️ Tidak berubah</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <div class="alert alert-warning mt-3">
                            <strong>⚠️ Penting!</strong> Hapus file 
                            <code>fix-password.php</code> dan 
                            <code>generate-hash.php</code>
                            setelah digunakan!
                        </div>

                        <div class="d-grid gap-2">
                            <a href="login.php" class="btn btn-primary">
                                🔐 Pergi ke Halaman Login
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>