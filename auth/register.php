<?php

/**
 * Register Page
 * File: auth/register.php
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

// Proses registrasi
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = cleanInput($_POST['username']);
    $email = cleanInput($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $full_name = cleanInput($_POST['full_name']);
    $phone = cleanInput($_POST['phone']);
    $address = cleanInput($_POST['address']);

    // Validasi input
    if (empty($username) || empty($email) || empty($password) || empty($full_name)) {
        $error = 'Semua field wajib harus diisi';
    } elseif (!validateEmail($email)) {
        $error = 'Format email tidak valid';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter';
    } elseif ($password !== $confirm_password) {
        $error = 'Konfirmasi password tidak cocok';
    } else {
        global $db;

        // Cek apakah username atau email sudah digunakan
        $check_sql = "SELECT id FROM users WHERE username = ? OR email = ?";
        $existing_user = $db->fetch($check_sql, [$username, $email]);

        if ($existing_user) {
            $error = 'Username atau email sudah digunakan';
        } else {
            // Insert user baru
            $hashed_password = hashPassword($password);
            $insert_sql = "INSERT INTO users (username, email, password, full_name, phone, address, role) VALUES (?, ?, ?, ?, ?, ?, 'customer')";

            if ($db->execute($insert_sql, [$username, $email, $hashed_password, $full_name, $phone, $address])) {
                $success = 'Registrasi berhasil! Silakan login dengan akun Anda.';

                // Redirect ke login setelah 2 detik
                echo "<script>
                    setTimeout(function() {
                        window.location.href = 'login.php';
                    }, 2000);
                </script>";
            } else {
                $error = 'Terjadi kesalahan saat registrasi. Silakan coba lagi.';
            }
        }
    }
}

$page_title = 'Register';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar - Toko Online</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Poppins', sans-serif;
        }

        .register-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .register-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            max-width: 1000px;
            width: 100%;
        }

        .register-left {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            text-align: center;
            height: 100%;
        }

        .register-right {
            padding: 3rem;
        }

        .form-floating .form-control {
            border-radius: 12px;
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
        }

        .form-floating .form-control:focus {
            border-color: #28a745;
            box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
        }

        .btn-register {
            background: linear-gradient(135deg, #28a745, #20c997);
            border: none;
            border-radius: 12px;
            padding: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-register:hover {
            background: linear-gradient(135deg, #20c997, #198754);
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(40, 167, 69, 0.3);
        }

        .strength-meter {
            height: 5px;
            border-radius: 3px;
            overflow: hidden;
            background-color: #e9ecef;
            margin-top: 0.5rem;
        }

        .strength-bar {
            height: 100%;
            transition: all 0.3s ease;
            width: 0%;
        }

        .strength-weak {
            background-color: #dc3545;
            width: 25%;
        }

        .strength-fair {
            background-color: #ffc107;
            width: 50%;
        }

        .strength-good {
            background-color: #20c997;
            width: 75%;
        }

        .strength-strong {
            background-color: #28a745;
            width: 100%;
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

        .step-indicator {
            display: flex;
            margin-bottom: 2rem;
        }

        .step {
            flex: 1;
            text-align: center;
            position: relative;
        }

        .step::before {
            content: '';
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #e9ecef;
            display: block;
            margin: 0 auto 0.5rem;
            line-height: 30px;
            color: white;
            font-weight: bold;
        }

        .step.active::before {
            background: #28a745;
            content: '✓';
        }

        .step.current::before {
            background: #0d6efd;
            content: attr(data-step);
        }
    </style>
</head>

<body>
    <div class="register-container">
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

        <div class="register-card">
            <div class="row g-0">
                <!-- Left Side - Welcome -->
                <div class="col-lg-5 d-none d-lg-block">
                    <div class="register-left">
                        <div>
                            <i class="fas fa-user-plus fa-4x mb-4"></i>
                            <h2 class="fw-bold mb-3">Bergabung Bersama Kami!</h2>
                            <p class="lead mb-4">
                                Daftar sekarang dan nikmati pengalaman belanja online
                                yang mudah dan menyenangkan.
                            </p>

                            <div class="row text-center mb-4">
                                <div class="col-6">
                                    <i class="fas fa-gift fa-2x mb-2"></i>
                                    <p class="small">Promo Menarik</p>
                                </div>
                                <div class="col-6">
                                    <i class="fas fa-medal fa-2x mb-2"></i>
                                    <p class="small">Produk Berkualitas</p>
                                </div>
                            </div>

                            <div class="bg-white bg-opacity-25 rounded p-3">
                                <h6 class="mb-2">Keuntungan Member:</h6>
                                <ul class="list-unstyled small text-start">
                                    <li><i class="fas fa-check me-2"></i>Gratis ongkos kirim</li>
                                    <li><i class="fas fa-check me-2"></i>Akses produk eksklusif</li>
                                    <li><i class="fas fa-check me-2"></i>Poin reward setiap pembelian</li>
                                    <li><i class="fas fa-check me-2"></i>Notifikasi promo terbaru</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Side - Register Form -->
                <div class="col-lg-7">
                    <div class="register-right">
                        <div class="text-center mb-4">
                            <h3 class="fw-bold text-primary">Buat Akun Baru</h3>
                            <p class="text-muted">Isi data diri Anda dengan lengkap</p>
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
                                <div class="mt-2">
                                    <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                                    Mengalihkan ke halaman login...
                                </div>
                            </div>
                        <?php endif; ?>

                        <form method="POST" class="needs-validation" novalidate id="registerForm">
                            <div class="row">
                                <!-- Personal Information -->
                                <div class="col-md-6">
                                    <div class="form-floating mb-3">
                                        <input type="text" class="form-control" id="full_name" name="full_name"
                                            placeholder="Nama Lengkap" required value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>">
                                        <label for="full_name">
                                            <i class="fas fa-id-card me-2"></i>Nama Lengkap
                                        </label>
                                        <div class="invalid-feedback">
                                            Nama lengkap harus diisi
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-floating mb-3">
                                        <input type="text" class="form-control" id="username" name="username"
                                            placeholder="Username" required pattern="[a-zA-Z0-9_]{3,20}"
                                            value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                                        <label for="username">
                                            <i class="fas fa-user me-2"></i>Username
                                        </label>
                                        <div class="invalid-feedback">
                                            Username 3-20 karakter, hanya huruf, angka, dan underscore
                                        </div>
                                        <div class="form-text">Username akan digunakan untuk login</div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-floating mb-3">
                                        <input type="email" class="form-control" id="email" name="email"
                                            placeholder="Email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                                        <label for="email">
                                            <i class="fas fa-envelope me-2"></i>Email
                                        </label>
                                        <div class="invalid-feedback">
                                            Masukkan email yang valid
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-floating mb-3">
                                        <input type="tel" class="form-control" id="phone" name="phone"
                                            placeholder="Nomor Telepon" pattern="[0-9]{10,15}"
                                            value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                                        <label for="phone">
                                            <i class="fas fa-phone me-2"></i>Nomor Telepon
                                        </label>
                                        <div class="invalid-feedback">
                                            Masukkan nomor telepon yang valid (10-15 digit)
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-floating mb-3">
                                <textarea class="form-control" id="address" name="address"
                                    placeholder="Alamat" style="height: 80px"><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
                                <label for="address">
                                    <i class="fas fa-map-marker-alt me-2"></i>Alamat
                                </label>
                            </div>

                            <!-- Password Section -->
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-floating mb-3">
                                        <input type="password" class="form-control" id="password" name="password"
                                            placeholder="Password" required minlength="6">
                                        <label for="password">
                                            <i class="fas fa-lock me-2"></i>Password
                                        </label>
                                        <div class="invalid-feedback">
                                            Password minimal 6 karakter
                                        </div>
                                    </div>
                                    <div class="strength-meter">
                                        <div class="strength-bar" id="strengthBar"></div>
                                    </div>
                                    <small class="text-muted" id="strengthText">Kekuatan password</small>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-floating mb-3">
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password"
                                            placeholder="Konfirmasi Password" required>
                                        <label for="confirm_password">
                                            <i class="fas fa-lock me-2"></i>Konfirmasi Password
                                        </label>
                                        <div class="invalid-feedback">
                                            Konfirmasi password tidak cocok
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Terms and Conditions -->
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="terms" name="terms" required>
                                <label class="form-check-label" for="terms">
                                    Saya setuju dengan <a href="#" class="text-decoration-none">Syarat & Ketentuan</a>
                                    dan <a href="#" class="text-decoration-none">Kebijakan Privasi</a>
                                </label>
                                <div class="invalid-feedback">
                                    Anda harus menyetujui syarat dan ketentuan
                                </div>
                            </div>

                            <div class="form-check mb-4">
                                <input class="form-check-input" type="checkbox" id="newsletter" name="newsletter">
                                <label class="form-check-label" for="newsletter">
                                    Saya ingin menerima informasi promo dan penawaran khusus via email
                                </label>
                            </div>

                            <button type="submit" class="btn btn-success btn-register w-100 mb-3">
                                <i class="fas fa-user-plus me-2"></i>Daftar Sekarang
                            </button>
                        </form>

                        <div class="text-center">
                            <p class="mb-0">
                                Sudah punya akun?
                                <a href="login.php" class="text-decoration-none fw-bold">
                                    Masuk di sini
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

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- jQuery -->
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

        // Password strength checker
        $('#password').on('input', function() {
            var password = $(this).val();
            var strength = 0;
            var strengthText = '';
            var strengthClass = '';

            // Length check
            if (password.length >= 6) strength += 25;
            if (password.length >= 10) strength += 25;

            // Character type checks
            if (/[a-z]/.test(password)) strength += 12.5;
            if (/[A-Z]/.test(password)) strength += 12.5;
            if (/[0-9]/.test(password)) strength += 12.5;
            if (/[^A-Za-z0-9]/.test(password)) strength += 12.5;

            // Set strength text and class
            if (strength < 25) {
                strengthText = 'Sangat Lemah';
                strengthClass = 'strength-weak';
            } else if (strength < 50) {
                strengthText = 'Lemah';
                strengthClass = 'strength-fair';
            } else if (strength < 75) {
                strengthText = 'Cukup';
                strengthClass = 'strength-good';
            } else {
                strengthText = 'Kuat';
                strengthClass = 'strength-strong';
            }

            $('#strengthBar').removeClass().addClass('strength-bar ' + strengthClass);
            $('#strengthText').text(strengthText);
        });

        // Confirm password validation
        $('#confirm_password').on('input', function() {
            var password = $('#password').val();
            var confirmPassword = $(this).val();

            if (confirmPassword && password !== confirmPassword) {
                $(this).addClass('is-invalid');
            } else {
                $(this).removeClass('is-invalid');
            }
        });

        // Username availability check (simulation)
        $('#username').on('blur', function() {
            var username = $(this).val();
            if (username.length >= 3) {
                // Simulate API call
                setTimeout(function() {
                    // Random availability check for demo
                    var available = Math.random() > 0.3;
                    if (!available) {
                        $('#username').addClass('is-invalid');
                        $('#username').siblings('.invalid-feedback').text('Username sudah digunakan');
                    } else {
                        $('#username').removeClass('is-invalid');
                        $('#username').addClass('is-valid');
                    }
                }, 500);
            }
        });

        // Auto-hide alerts
        $('.alert').delay(5000).slideUp(500);

        // Format phone number
        $('#phone').on('input', function() {
            var value = $(this).val().replace(/\D/g, '');
            $(this).val(value);
        });

        // Real-time form validation feedback
        $('input[required]').on('blur', function() {
            if ($(this).val()) {
                $(this).addClass('is-valid').removeClass('is-invalid');
            }
        });

        // Character counter for textarea
        $('#address').on('input', function() {
            var maxLength = 200;
            var currentLength = $(this).val().length;
            var remaining = maxLength - currentLength;

            if (!$('#addressCounter').length) {
                $(this).after('<small class="text-muted" id="addressCounter"></small>');
            }

            $('#addressCounter').text(remaining + ' karakter tersisa');

            if (remaining < 0) {
                $(this).addClass('is-invalid');
                $('#addressCounter').addClass('text-danger');
            } else {
                $(this).removeClass('is-invalid');
                $('#addressCounter').removeClass('text-danger');
            }
        });
    </script>
</body>

</html>