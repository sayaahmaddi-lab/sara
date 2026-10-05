<?php
/**
 * File: register.php
 * Deskripsi: Halaman registrasi user baru
 */
require_once 'config/database.php';
require_once 'includes/functions.php';

// Redirect jika sudah login
if (isLoggedIn()) {
    redirect('user/dashboard.php');
}

$error = '';
$success = '';

// Proses registrasi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = getConnection();
    
    $nama = sanitize($_POST['nama']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $no_hp = sanitize($_POST['no_hp']);
    $alamat = sanitize($_POST['alamat']);
    
    // Validasi
    if ($password !== $confirm_password) {
        $error = 'Password dan konfirmasi password tidak cocok!';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter!';
    } else {
        // Cek email sudah terdaftar
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = 'Email sudah terdaftar!';
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert user baru
            $stmt = $conn->prepare("INSERT INTO users (nama, email, password, no_hp, alamat, role, status) VALUES (?, ?, ?, ?, ?, 'user', 'aktif')");
            $stmt->bind_param("sssss", $nama, $email, $hashed_password, $no_hp, $alamat);
            
            if ($stmt->execute()) {
                $success = 'Registrasi berhasil! Silakan login.';
            } else {
                $error = 'Registrasi gagal! Silakan coba lagi.';
            }
        }
    }
    
    $stmt->close();
    closeConnection($conn);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Retribusi Daerah</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="auth-wrapper">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card auth-card">
                        <div class="card-body">
                            <div class="text-center mb-4">
                                <i class="bi bi-person-plus text-primary" style="font-size: 3rem;"></i>
                                <h2 class="fw-bold text-primary mt-3">Registrasi Akun</h2>
                                <p class="text-muted">Buat akun baru untuk mengakses sistem</p>
                            </div>
                            
                            <?php if ($error): ?>
                                <div class="alert alert-danger alert-dismissible fade show">
                                    <i class="bi bi-exclamation-triangle"></i> <?= $error ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($success): ?>
                                <div class="alert alert-success alert-dismissible fade show">
                                    <i class="bi bi-check-circle"></i> <?= $success ?>
                                    <a href="login.php" class="alert-link">Login sekarang</a>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            <?php endif; ?>
                            
                            <form method="POST" action="">
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">
                                            <i class="bi bi-person"></i> Nama Lengkap *
                                        </label>
                                        <input type="text" class="form-control" name="nama" 
                                               placeholder="Masukkan nama lengkap" required>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">
                                            <i class="bi bi-envelope"></i> Email *
                                        </label>
                                        <input type="email" class="form-control" name="email" 
                                               placeholder="nama@email.com" required>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">
                                            <i class="bi bi-phone"></i> No. HP *
                                        </label>
                                        <input type="text" class="form-control" name="no_hp" 
                                               placeholder="08xxxxxxxxxx" required>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">
                                            <i class="bi bi-lock"></i> Password *
                                        </label>
                                        <input type="password" class="form-control" name="password" 
                                               placeholder="Min. 6 karakter" required minlength="6">
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">
                                            <i class="bi bi-lock-fill"></i> Konfirmasi Password *
                                        </label>
                                        <input type="password" class="form-control" name="confirm_password" 
                                               placeholder="Ulangi password" required>
                                    </div>
                                    
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">
                                            <i class="bi bi-geo-alt"></i> Alamat *
                                        </label>
                                        <textarea class="form-control" name="alamat" rows="3" 
                                                  placeholder="Masukkan alamat lengkap" required></textarea>
                                    </div>
                                </div>
                                
                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" id="agree" required>
                                    <label class="form-check-label" for="agree">
                                        Saya setuju dengan <a href="#">syarat dan ketentuan</a>
                                    </label>
                                </div>
                                
                                <button type="submit" class="btn btn-primary w-100 mb-3">
                                    <i class="bi bi-person-plus"></i> Daftar Sekarang
                                </button>
                                
                                <div class="text-center">
                                    <small>Sudah punya akun? 
                                        <a href="login.php" class="text-decoration-none">Login disini</a>
                                    </small>
                                </div>
                                
                                <hr>
                                
                                <div class="text-center">
                                    <a href="index.php" class="text-muted text-decoration-none">
                                        <i class="bi bi-arrow-left"></i> Kembali ke Beranda
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>