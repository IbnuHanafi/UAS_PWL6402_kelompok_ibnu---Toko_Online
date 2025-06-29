<?php

/**
 * Login Page
 * File: auth/login.php
 * Toko Online - UAS Pemrograman Web Lanjut
 */

require_once '../includes/functions.php';

// Jika user sudah login, redirect ke dashboard sesuai role
if (isLoggedIn()) {
    if (isAdmin()) {
        redirect('../admin/dashboard.php');
    } else {
        redirect('../customer/dashboard.php');
    }
}

$error = '';
$success = '';

// Proses login
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = cleanInput($_POST['username']);
    $password = $_POST['password'];

    // Validasi input
    if (empty($username) || empty($password)) {
        $error = 'Username dan password harus diisi';
    } else {
        global $db;

        // Cari user berdasarkan username atau email
        $sql = "SELECT * FROM users WHERE username = ? OR email = ?";
        $user = $db->fetch($sql, [$username, $username]);

        if ($user && verifyPassword($password, $user['password'])) {
            // Login berhasil
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['full_name'] = $user['full_name'];

            // Redirect berdasarkan role
            if ($user['role'] === 'admin') {
                redirect('../admin/dashboard.php');
            } else {
                redirect('../customer/dashboard.php');
            }
        } else {
            $error = 'Username/email atau password salah';
        }
    }
}

$page_title = 'Login';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Toko Online</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Poppins', sans-serif;
        }

        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            max-width: 900px;
            width: 100%;
        }

        .login-left {
            background: linear-gradient(135deg, #0d6efd, #0b5ed7);
            color: white;
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            text-align: center;
            height: 100%;
            /* <-- PERBAIKAN: Ditambahkan untuk memenuhi tinggi card */
        }

        .login-right {
            padding: 3rem;
        }

        .form-floating .form-control {
            border-radius: 12px;
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
        }

        .form-floating .form-control:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
        }

        .btn-login {
            background: linear-gradient(135deg, #0d6efd, #0b5ed7);
            border: none;
            border-radius: 12px;
            padding: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-login:hover {
            background: linear-gradient(135deg, #0b5ed7, #0a58ca);
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(13, 110, 253, 0.3);
        }

        .social-btn {
            border-radius: 12px;
            padding: 12px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .divider {
            position: relative;
            text-align: center;
            margin: 1.5rem 0;
        }

        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: #dee2e6;
        }

        .divider span {
            background: white;
            padding: 0 1rem;
            color: #6c757d;
            font-size: 0.9rem;
        }

        .floating-elements {
            position: absolute;
            width: 100%;
            height: 100%;
            overflow: hidden;
        }

        .floating-elements span {
            position: absolute;
            display: block;
            list-style: none;
            width: 20px;
            height: 20px;
            background: rgba(255, 255, 255, 0.2);
            animation: animate 25s linear infinite;
            bottom: -150px;
        }

        @keyframes animate {
            0% {
                transform: translateY(0) rotate(0deg);
                opacity: 1;
                border-radius: 0;
            }

            100% {
                transform: translateY(-1000px) rotate(720deg);
                opacity: 0;
                border-radius: 50%;
            }
        }

        .permanent-notice {
            padding: 0.5rem 1rem;
            margin-top: 1rem;
            color: #842029;
            /* Warna teks dari .alert-danger */
            background-color: #f8d7da;
            /* Warna latar dari .alert-danger */
            border: 1px solid #f5c2c7;
            /* Warna border dari .alert-danger */
            border-radius: 0.375rem;
            /* Sudut melengkung standar Bootstrap */
            line-height: 1.5;
        }

        .permanent-notice i {
            color: #842029;
            /* Memastikan warna ikon sama dengan teks */
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="floating-elements">
            <span style="left: 25%; width: 80px; height: 80px; animation-delay: 0s;"></span>
            <span style="left: 10%; width: 20px; height: 20px; animation-delay: 2s; animation-duration: 12s;"></span>
            <span style="left: 70%; width: 20px; height: 20px; animation-delay: 4s;"></span>
            <span style="left: 40%; width: 60px; height: 60px; animation-delay: 0s; animation-duration: 18s;"></span>
            <span style="left: 65%; width: 20px; height: 20px; animation-delay: 0s;"></span>
            <span style="left: 75%; width: 110px; height: 110px; animation-delay: 3s;"></span>
            <span style="left: 35%; width: 150px; height: 150px; animation-delay: 7s;"></span>
            <span style="left: 50%; width: 25px; height: 25px; animation-delay: 15s; animation-duration: 45s;"></span>
            <span style="left: 20%; width: 15px; height: 15px; animation-delay: 2s; animation-duration: 35s;"></span>
            <span style="left: 85%; width: 150px; height: 150px; animation-delay: 0s; animation-duration: 11s;"></span>
        </div>

        <div class="login-card">
            <div class="row g-0">
                <div class="col-lg-6 d-none d-lg-block">
                    <div class="login-left">
                        <div>
                            <i class="fas fa-store fa-4x mb-4"></i>
                            <h2 class="fw-bold mb-3">Selamat Datang!</h2>
                            <p class="lead mb-4">
                                Masuk ke akun Anda untuk mengakses ribuan produk berkualitas
                                dengan harga terbaik.
                            </p>
                            <div class="row text-center">
                                <div class="col-4">
                                    <i class="fas fa-shipping-fast fa-2x mb-2"></i>
                                    <p class="small">Pengiriman Cepat</p>
                                </div>
                                <div class="col-4">
                                    <i class="fas fa-shield-alt fa-2x mb-2"></i>
                                    <p class="small">Pembayaran Aman</p>
                                </div>
                                <div class="col-4">
                                    <i class="fas fa-headset fa-2x mb-2"></i>
                                    <p class="small">CS 24/7</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="login-right">
                        <div class="text-center mb-4">
                            <h3 class="fw-bold text-primary">Masuk Akun</h3>
                            <p class="text-muted">Silakan masuk dengan akun Anda</p>
                        </div>

                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <?php echo $error; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle me-2"></i>
                                <?php echo $success; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" class="needs-validation" novalidate>
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" id="username" name="username"
                                    placeholder="Username atau Email" required>
                                <label for="username">
                                    <i class="fas fa-user me-2"></i>Username atau Email
                                </label>
                                <div class="invalid-feedback">
                                    Username atau email harus diisi
                                </div>
                            </div>

                            <div class="form-floating mb-3">
                                <input type="password" class="form-control" id="password" name="password"
                                    placeholder="Password" required>
                                <label for="password">
                                    <i class="fas fa-lock me-2"></i>Password
                                </label>
                                <div class="invalid-feedback">
                                    Password harus diisi
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="remember" name="remember">
                                    <label class="form-check-label" for="remember">
                                        Ingat saya
                                    </label>
                                </div>
                                <a href="forgot_password.php" class="text-decoration-none small">Lupa password?</a>
                            </div>

                            <button type="submit" class="btn btn-primary btn-login w-100 mb-3">
                                <i class="fas fa-sign-in-alt me-2"></i>Masuk
                            </button>
                        </form>

                        <div class="text-center">
                            <p class="mb-0">
                                Belum punya akun?
                                <a href="register.php" class="text-decoration-none fw-bold">
                                    Daftar di sini
                                </a>
                            </p>
                            <hr class="my-3">
                            <a href="../index.php" class="text-decoration-none">
                                <i class="fas fa-arrow-left me-2"></i>Kembali ke Beranda
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="position-fixed bottom-0 start-0 m-3" style="z-index: 1000;">
        <div class="card shadow" style="width: 300px;">
            <div class="card-header bg-info text-white">
                <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Demo Accounts</h6>
            </div>
            <div class="card-body small">
                <p class="mb-2"><strong>Admin:</strong></p>
                <p class="mb-1">Username: admin</p>
                <p class="mb-3">Password: password</p>

                <p class="mb-2"><strong>Customer:</strong></p>
                <p class="mb-1">Username: customer1</p>
                <p class="mb-0">Password: password</p>

                <div class="permanent-notice" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <small>Harap jangan mengubah username dan password demo accounts.</small>
                </div>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <script>
        // Form validation
        (function() {
            'use strict';
            window.addEventListener('load', function() {
                var forms = document.getElementsByClassName('needs-validation');
                var validation = Array.prototype.filter.call(forms, function(form) {
                    form.addEventListener('submit', function(event) {
                        if (form.checkValidity() === false) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                        form.classList.add('was-validated');
                    }, false);
                });
            }, false);
        })();

        // Show/hide password
        $('#togglePassword').click(function() {
            var password = $('#password');
            var type = password.attr('type') === 'password' ? 'text' : 'password';
            password.attr('type', type);
            $(this).find('i').toggleClass('fa-eye fa-eye-slash');
        });

        // Auto-hide alerts
        $('.alert').delay(5000).slideUp(500);

        // Demo account quick login
        $('.demo-login').click(function(e) {
            e.preventDefault();
            var username = $(this).data('username');
            var password = $(this).data('password');

            $('#username').val(username);
            $('#password').val(password);

            // Auto submit form
            $('form').submit();
        });
    </script>
</body>

</html>