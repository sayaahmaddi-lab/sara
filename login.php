<?php
/**
 * File: login.php
 * Deskripsi: Halaman login user dan admin
 */
require_once 'config/database.php';
require_once 'includes/functions.php';

// Redirect jika sudah login
if (isLoggedIn()) {
    if (isAdmin()) {
        redirect('admin/index.php');
    } else {
        redirect('user/dashboard.php');
    }
}

$error = '';

// Proses login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = getConnection();
    
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    
    $stmt = $conn->prepare("SELECT id, nama, email, password, role, status FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Cek status akun
        if ($user['status'] !== 'aktif') {
            $error = 'Akun Anda tidak aktif. Hubungi administrator.';
        } 
        // Verifikasi password
        elseif (password_verify($password, $user['password'])) {
            // Set session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['nama'] = $user['nama'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            
            // Log aktivitas
            logAktivitas($conn, $user['id'], 'Login', 'User login ke sistem');
            
            // Redirect berdasarkan role
            if ($user['role'] === 'admin') {
                redirect('admin/index.php');
            } else {
                redirect('user/dashboard.php');
            }
        } else {
            $error = 'Email atau password salah!';
        }
    } else {
        $error = 'Email atau password salah!';
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
    <title>Login - Retribusi Daerah</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="auth-wrapper">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-5">
                    <div class="card auth-card">
                        <div class="card-body">
                            <div class="text-center mb-4">
                                <i class="bi bi-building text-primary" style="font-size: 3rem;"></i>
                                <h2 class="fw-bold text-primary mt-3">Retribusi Daerah</h2>
                                <p class="text-muted">Silakan login ke akun Anda</p>
                            </div>
                            
                            <?php if ($error): ?>
                                <div class="alert alert-danger alert-dismissible fade show">
                                    <i class="bi bi-exclamation-triangle"></i> <?= $error ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            <?php endif; ?>
                            
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="bi bi-envelope"></i> Email
                                    </label>
                                    <input type="email" class="form-control" name="email" 
                                           placeholder="nama@email.com" required autofocus>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="bi bi-lock"></i> Password
                                    </label>
                                    <input type="password" class="form-control" name="password" 
                                           placeholder="Masukkan password" required>
                                </div>
                                
                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" id="remember">
                                    <label class="form-check-label" for="remember">Ingat saya</label>
                                </div>
                                
                                <button type="submit" class="btn btn-primary w-100 mb-3">
                                    <i class="bi bi-box-arrow-in-right"></i> Login
                                </button>
                                
                                <div class="text-center">
                                    <small>Belum punya akun? 
                                        <a href="register.php" class="text-decoration-none">Daftar disini</a>
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
                    
                    <!-- Demo Accounts Info -->
                    <div class="card mt-3">
                        <div class="card-body bg-light">
                            <small class="text-muted">
                                <strong>Demo Account:</strong><br>
                                Admin: admin@retribusi.com / admin123<br>
                                User: budi@gmail.com / user123
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>