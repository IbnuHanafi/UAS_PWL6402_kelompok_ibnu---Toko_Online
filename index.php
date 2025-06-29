<?php

/**
 * Landing Page
 * File: index.php
 * Toko Online - UAS Pemrograman Web Lanjut
 */

// Include functions
require_once 'includes/functions.php';

// Jika user sudah login, redirect ke dashboard sesuai role
if (isLoggedIn()) {
    if (isAdmin()) {
        redirect('admin/dashboard.php');
    } else {
        redirect('customer/dashboard.php');
    }
}

$page_title = 'Beranda';

// Get featured products
global $db;
$featured_products = $db->query("
    SELECT p.*, c.name as category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE p.status = 'active' 
    ORDER BY p.created_at DESC 
    LIMIT 8
");
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Toko Online - Platform Belanja Terpercaya</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="assets/css/style.css">

    <style>
        .hero-section {
            background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 100" fill="rgba(255,255,255,0.1)"><polygon points="1000,100 1000,0 0,100"/></svg>');
            background-size: cover;
        }

        .feature-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #0d6efd, #0b5ed7);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            color: white;
            font-size: 2rem;
        }

        .stats-counter {
            font-size: 3rem;
            font-weight: 700;
            color: #0d6efd;
        }

        .product-carousel .card {
            transition: transform 0.3s ease;
        }

        .product-carousel .card:hover {
            transform: translateY(-10px);
        }

        /* --- PERBAIKAN UNTUK MERAPIKAN GAMBAR PRODUK --- */
        .product-image-container {
            aspect-ratio: 1 / 1;
            /* Membuat container gambar persegi */
            width: 100%;
            background-color: #f8f9fa;
            /* Warna latar jika gambar transparan */
        }

        .product-img-landing,
        .product-img-placeholder {
            width: 100%;
            height: 100%;
            object-fit: cover;
            /* Membuat gambar mengisi container tanpa distorsi */
        }

        .product-img-placeholder {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* --- AKHIR DARI PERBAIKAN --- */
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">
                <i class="fas fa-store me-2"></i>Toko Online
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="#home">Beranda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#products">Produk</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#features">Fitur</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#about">Tentang</a>
                    </li>
                </ul>

                <div class="navbar-nav">
                    <a class="nav-link" href="auth/login.php">
                        <i class="fas fa-sign-in-alt me-1"></i>Login
                    </a>
                    <a class="btn btn-outline-light ms-2" href="auth/register.php">
                        <i class="fas fa-user-plus me-1"></i>Daftar
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <section id="home" class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <div class="hero-content text-white fade-in">
                        <h1 class="display-4 fw-bold mb-4">
                            Belanja Online
                            <span class="text-warning">Mudah & Terpercaya</span>
                        </h1>
                        <p class="lead mb-4">
                            Temukan ribuan produk berkualitas dengan harga terbaik.
                            Pengalaman belanja online yang aman, cepat, dan menyenangkan.
                        </p>
                        <div class="d-flex gap-3 flex-wrap">
                            <a href="#products" class="btn btn-warning btn-lg">
                                <i class="fas fa-shopping-cart me-2"></i>Scroll ke bawah untuk melihat informasi kami
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="hero-image text-center slide-in-right">
                        <i class="fas fa-shopping-bag" style="font-size: 20rem; color: rgba(255,255,255,0.1);"></i>
                    </div>
                </div>
            </div>
        </div>
    </section>



    <section id="products" class="py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold">Produk Unggulan</h2>
                <p class="lead text-muted">Temukan produk terbaik pilihan kami</p>
            </div>

            <div class="row product-carousel">
                <?php foreach ($featured_products as $product): ?>
                    <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                        <div class="card h-100 border-0 shadow product-card-landing">
                            <div class="product-image-container position-relative overflow-hidden">
                                <?php if ($product['image']): ?>
                                    <img src="assets/images/products/<?php echo $product['image']; ?>"
                                        class="card-img-top product-img-landing" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                <?php else: ?>
                                    <div class="product-img-placeholder">
                                        <div class="text-center">
                                            <i class="fas fa-image fa-3x text-muted mb-2"></i>
                                            <div class="small text-muted">No Image</div>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <div class="position-absolute top-0 start-0 m-2">
                                    <span class="badge bg-primary">
                                        <?php echo htmlspecialchars($product['category_name'] ?? 'Produk'); ?>
                                    </span>
                                </div>

                                <?php if ($product['stock'] <= 5): ?>
                                    <div class="position-absolute top-0 end-0 m-2">
                                        <span class="badge bg-warning text-dark">Limited Stock</span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="card-body">
                                <h6 class="card-title fw-bold product-title-landing"><?php echo htmlspecialchars($product['name']); ?></h6>
                                <p class="card-text text-muted small product-desc-landing">
                                    <?php echo htmlspecialchars(substr($product['description'], 0, 70)) . (strlen($product['description']) > 70 ? '...' : ''); ?>
                                </p>
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="text-success fw-bold h6 mb-0"><?php echo formatRupiah($product['price']); ?></span>
                                    <small class="text-muted">
                                        <i class="fas fa-box me-1"></i><?php echo $product['stock']; ?> tersedia
                                    </small>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent border-0">
                                <a href="auth/login.php" class="btn btn-primary w-100 btn-beli-sekarang">
                                    <i class="fas fa-shopping-cart me-1"></i>Beli Sekarang
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="text-center mt-4">
                <a href="auth/login.php" class="btn btn-outline-primary btn-lg">
                    Lihat Semua Produk <i class="fas fa-arrow-right ms-2"></i>
                </a>
            </div>
        </div>
    </section>

    <section id="features" class="py-5 bg-light">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-5 fw-bold">Mengapa Memilih Kami?</h2>
                <p class="lead text-muted">Keunggulan yang membuat kami berbeda</p>
            </div>

            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="text-center">
                        <div class="feature-icon">
                            <i class="fas fa-shipping-fast"></i>
                        </div>
                        <h5 class="fw-bold">Pengiriman Cepat</h5>
                        <p class="text-muted">
                            Pengiriman ke seluruh Indonesia dengan waktu yang cepat dan aman
                        </p>
                    </div>
                </div>

                <div class="col-md-4 mb-4">
                    <div class="text-center">
                        <div class="feature-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h5 class="fw-bold">Pembayaran Aman</h5>
                        <p class="text-muted">
                            Sistem pembayaran yang aman dengan berbagai metode pilihan
                        </p>
                    </div>
                </div>

                <div class="col-md-4 mb-4">
                    <div class="text-center">
                        <div class="feature-icon">
                            <i class="fas fa-headset"></i>
                        </div>
                        <h5 class="fw-bold">Customer Service 24/7</h5>
                        <p class="text-muted">
                            Tim customer service siap membantu Anda kapan saja
                        </p>
                    </div>
                </div>

                <div class="col-md-4 mb-4">
                    <div class="text-center">
                        <div class="feature-icon">
                            <i class="fas fa-medal"></i>
                        </div>
                        <h5 class="fw-bold">Produk Berkualitas</h5>
                        <p class="text-muted">
                            Semua produk telah melalui quality control yang ketat
                        </p>
                    </div>
                </div>

                <div class="col-md-4 mb-4">
                    <div class="text-center">
                        <div class="feature-icon">
                            <i class="fas fa-undo"></i>
                        </div>
                        <h5 class="fw-bold">Garansi Return</h5>
                        <p class="text-muted">
                            Mudah return produk jika tidak sesuai ekspektasi
                        </p>
                    </div>
                </div>

                <div class="col-md-4 mb-4">
                    <div class="text-center">
                        <div class="feature-icon">
                            <i class="fas fa-mobile-alt"></i>
                        </div>
                        <h5 class="fw-bold">Responsive Design</h5>
                        <p class="text-muted">
                            Akses mudah dari smartphone, tablet, atau komputer
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="about" class="py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h2 class="display-5 fw-bold mb-4">Tentang Toko Online</h2>
                    <p class="lead mb-4">
                        Kami adalah platform e-commerce terpercaya yang telah melayani
                        ribuan pelanggan di seluruh Indonesia sejak tahun 2020.
                    </p>
                    <p class="mb-4">
                        Dengan komitmen untuk memberikan pengalaman belanja online terbaik,
                        kami menyediakan berbagai produk berkualitas dari brand-brand ternama
                        dengan harga yang kompetitif.
                    </p>
                    <div class="row">
                        <div class="col-6">
                            <h4 class="text-primary fw-bold">Visi</h4>
                            <p class="small">Menjadi platform e-commerce pilihan utama di Indonesia</p>
                        </div>
                        <div class="col-6">
                            <h4 class="text-primary fw-bold">Misi</h4>
                            <p class="small">Memberikan pengalaman belanja online yang mudah dan menyenangkan</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="text-center">
                        <i class="fas fa-store" style="font-size: 15rem; color: #e9ecef;"></i>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-5 bg-primary text-white">
        <div class="container text-center">
            <h2 class="display-5 fw-bold mb-4">Siap Memulai Belanja?</h2>
            <p class="lead mb-4">
                Bergabunglah dengan ribuan pelanggan yang sudah merasakan kemudahan berbelanja bersama kami
            </p>
            <div class="d-flex gap-3 justify-content-center flex-wrap">
                <a href="auth/register.php" class="btn btn-warning btn-lg">
                    <i class="fas fa-user-plus me-2"></i>Daftar Gratis
                </a>
                <a href="auth/login.php" class="btn btn-outline-light btn-lg">
                    <i class="fas fa-sign-in-alt me-2"></i>Masuk Sekarang
                </a>
            </div>
        </div>
    </section>

    <footer class="bg-dark text-light py-5 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 col-md-6 mb-4">
                    <h5 class="fw-bold mb-3">
                        <i class="fas fa-store me-2"></i>Toko Online
                    </h5>
                    <p class="text-light"> Platform belanja online terpercaya dengan berbagai pilihan produk berkualitas
                        dan pelayanan terbaik untuk kepuasan pelanggan.
                    </p>
                    <div class="social-links">
                        <a href="https://www.facebook.com/" class="text-light me-3" target="_blank" aria-label="Facebook">
                            <i class="fab fa-facebook-f fa-lg"></i>
                        </a>
                        <a href="https://www.instagram.com/" class="text-light me-3" target="_blank" aria-label="Instagram">
                            <i class="fab fa-instagram fa-lg"></i>
                        </a>
                        <a href="https://wa.me/6288802972620" class="text-light me-3" target="_blank" aria-label="WhatsApp"> <i class="fab fa-whatsapp fa-lg"></i> </a>
                    </div>
                </div>

                <div class="col-lg-2 col-md-6 mb-4">
                    <h6 class="fw-bold mb-3">Menu</h6>
                    <ul class="list-unstyled">
                        <li><a href="#home" class="text-light text-decoration-none">Beranda</a></li>
                        <li><a href="#products" class="text-light text-decoration-none">Produk</a></li>
                        <li><a href="#features" class="text-light text-decoration-none">Fitur</a></li>
                        <li><a href="#about" class="text-light text-decoration-none">Tentang</a></li>
                    </ul>
                </div>

                <div class="col-lg-3 col-md-6 mb-4">
                    <h6 class="fw-bold mb-3">Hubungi Kami</h6>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <i class="fab fa-whatsapp fa-lg"></i>
                            <span class="text-light">+62 088802972620 (Ibnu - Ketua project)</span>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-envelope me-2"></i>
                            <span class="text-light">112202306994@mhs.dinus.ac.id</span>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-clock me-2"></i>
                            <span class="text-light">24/7 Online</span>
                        </li>
                    </ul>
                </div>

                <div class="col-lg-3 col-md-6 mb-4">
                    <h6 class="fw-bold mb-3">Dibuat Oleh</h6>
                    <ul class="list-unstyled">
                        <li><span class="text-light">1. Ibnu Hanafi Assalam - A12.2023.06994</span></li>
                        <li><span class="text-light">2. Muhammad Fuad Aqil - A12.2023.06982</span></li>
                        <li><span class="text-light">3. Dzaki Jamil M - A12.2023.07101</span></li>
                        <li><span class="text-light">4. Rafli Zibrilian Farrel - A12.2023.06973</span></li>
                        <li><span class="text-light">5. Mutiara Acintyacitra N - A12.2023.07059</span></li>
                    </ul>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <h6 class="fw-bold mb-3">Newsletter</h6>
                    <p class="text-light mb-3">Dapatkan info promo dan produk terbaru</p>
                    <div class="input-group">
                        <input type="email" class="form-control" placeholder="Email Anda">
                        <button class="btn btn-primary" type="button">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                </div>
            </div>

            <hr class="my-4">

            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="text-light mb-0"> &copy; <?php echo date('Y'); ?> Toko Online. UAS Pemrograman Web Lanjut - Sistem Informasi.
                    </p>
                </div>
                <div class="col-md-6 text-md-end">
                    <img src="assets/images/payment-methods.png" alt="Payment Methods"
                        class="img-fluid" style="max-height: 30px;" onerror="this.style.display='none'">
                </div>
            </div>
        </div>
    </footer>

    <button type="button" class="btn btn-primary btn-floating btn-lg rounded-circle back-to-top"
        id="backToTopBtn" style="display: none;">
        <i class="fas fa-arrow-up"></i>
    </button>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="assets/js/script.js"></script>

    <script>
        // Smooth scrolling for navigation links
        $('a[href^="#"]').on('click', function(event) {
            var target = $(this.getAttribute('href'));
            if (target.length) {
                event.preventDefault();
                $('html, body').stop().animate({
                    scrollTop: target.offset().top - 80 // Adjust for fixed navbar height
                }, 800);
            }
        });

        // Navbar scroll effect
        $(window).scroll(function() {
            if ($(window).scrollTop() > 50) {
                $('.navbar').addClass('shadow');
            } else {
                $('.navbar').removeClass('shadow');
            }
        });

        // Back to top button
        var backToTopBtn = document.getElementById('backToTopBtn');
        window.onscroll = function() {
            if (document.body.scrollTop > 100 || document.documentElement.scrollTop > 100) {
                backToTopBtn.style.display = "block";
            } else {
                backToTopBtn.style.display = "none";
            }
        };
        $('#backToTopBtn').on('click', function() {
            $('html, body').animate({
                scrollTop: 0
            }, 'slow');
        });
    </script>
</body>

</html>