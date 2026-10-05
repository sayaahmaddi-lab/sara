<?php
/**
 * File: index.php
 * Deskripsi: Halaman landing/beranda publik
 */
require_once 'config/database.php';
require_once 'includes/functions.php';

// Jika sudah login, redirect ke dashboard
if (isLoggedIn()) {
    if (isAdmin()) {
        redirect('admin/index.php');
    } else {
        redirect('user/dashboard.php');
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Retribusi Daerah</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 100px 0;
        }
        .feature-card {
            transition: transform 0.3s;
        }
        .feature-card:hover {
            transform: translateY(-10px);
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="#">
                <i class="bi bi-building text-primary"></i> Retribusi Daerah
            </a>
            <div class="ms-auto">
                <a href="login.php" class="btn btn-outline-primary me-2">Login</a>
                <a href="register.php" class="btn btn-primary">Daftar</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section text-center">
        <div class="container">
            <h1 class="display-4 fw-bold mb-4">Sistem Pembayaran Retribusi Daerah</h1>
            <p class="lead mb-5">Bayar retribusi dengan mudah, cepat, dan aman secara online</p>
            <a href="register.php" class="btn btn-light btn-lg me-3">Mulai Sekarang</a>
            <a href="login.php" class="btn btn-outline-light btn-lg">Login</a>
        </div>
    </section>

    <!-- Features -->
    <section class="py-5">
        <div class="container">
            <h2 class="text-center mb-5">Fitur Unggulan</h2>
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="card feature-card h-100 text-center p-4">
                        <div class="card-body">
                            <i class="bi bi-credit-card text-primary" style="font-size: 3rem;"></i>
                            <h5 class="card-title mt-3">Pembayaran Online</h5>
                            <p class="card-text">Bayar retribusi kapan saja dan dimana saja dengan berbagai metode pembayaran</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card feature-card h-100 text-center p-4">
                        <div class="card-body">
                            <i class="bi bi-shield-check text-success" style="font-size: 3rem;"></i>
                            <h5 class="card-title mt-3">Aman & Terpercaya</h5>
                            <p class="card-text">Transaksi Anda dijamin aman dengan sistem keamanan berlapis</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card feature-card h-100 text-center p-4">
                        <div class="card-body">
                            <i class="bi bi-clock-history text-warning" style="font-size: 3rem;"></i>
                            <h5 class="card-title mt-3">Riwayat Pembayaran</h5>
                            <p class="card-text">Lihat riwayat dan bukti pembayaran Anda dengan mudah</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="bg-light py-5">
        <div class="container text-center">
            <h3 class="mb-4">Siap untuk memulai?</h3>
            <p class="lead mb-4">Daftar sekarang dan nikmati kemudahan pembayaran retribusi</p>
            <a href="register.php" class="btn btn-primary btn-lg">Daftar Gratis</a>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4">
        <div class="container text-center">
            <p class="mb-0">© <?= date('Y') ?> Sistem Retribusi Daerah. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>