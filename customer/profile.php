<?php

/**
 * Customer Profile
 * File: customer/profile.php
 * Toko Online - UAS Pemrograman Web Lanjut
 */

require_once '../includes/functions.php';

// Proteksi halaman customer
requireCustomer();

$page_title = 'Profil Saya';

global $db;

$user_id = $_SESSION['user_id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_profile'])) {
        $full_name = cleanInput($_POST['full_name']);
        $email = cleanInput($_POST['email']);
        $phone = cleanInput($_POST['phone']);
        $address = cleanInput($_POST['address']);

        if (empty($full_name) || empty($email)) {
            setFlashMessage('error', 'Nama lengkap dan email harus diisi');
        } elseif (!validateEmail($email)) {
            setFlashMessage('error', 'Format email tidak valid');
        } else {
            // Check if email already used by other user
            $existing_user = $db->fetch("SELECT id FROM users WHERE email = ? AND id != ?", [$email, $user_id]);
            if ($existing_user) {
                setFlashMessage('error', 'Email sudah digunakan oleh pengguna lain');
            } else {
                $sql = "UPDATE users SET full_name = ?, email = ?, phone = ?, address = ? WHERE id = ?";
                if ($db->execute($sql, [$full_name, $email, $phone, $address, $user_id])) {
                    $_SESSION['full_name'] = $full_name;
                    setFlashMessage('success', 'Profil berhasil diupdate');
                } else {
                    setFlashMessage('error', 'Gagal mengupdate profil');
                }
            }
        }
        redirect('profile.php');
    } elseif (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            setFlashMessage('error', 'Semua field password harus diisi');
        } elseif (strlen($new_password) < 6) {
            setFlashMessage('error', 'Password baru minimal 6 karakter');
        } elseif ($new_password !== $confirm_password) {
            setFlashMessage('error', 'Konfirmasi password tidak cocok');
        } else {
            // Verify current password
            $user = $db->fetch("SELECT password FROM users WHERE id = ?", [$user_id]);
            if (!verifyPassword($current_password, $user['password'])) {
                setFlashMessage('error', 'Password saat ini salah');
            } else {
                $hashed_password = hashPassword($new_password);
                $sql = "UPDATE users SET password = ? WHERE id = ?";
                if ($db->execute($sql, [$hashed_password, $user_id])) {
                    setFlashMessage('success', 'Password berhasil diubah');
                } else {
                    setFlashMessage('error', 'Gagal mengubah password');
                }
            }
        }
        redirect('profile.php');
    }
}

// Get user data
$user = getCurrentUser();

// Get user statistics
$total_orders = $db->fetch("SELECT COUNT(*) as total FROM orders WHERE user_id = ?", [$user_id])['total'];
$total_spent = $db->fetch("
    SELECT COALESCE(SUM(total_amount), 0) as total 
    FROM orders 
    WHERE user_id = ? AND status = 'completed'
", [$user_id])['total'];

include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 text-gradient font-weight-bold">Profil Saya</h1>
                    <p class="text-muted">Kelola informasi profil dan keamanan akun Anda</p>
                </div>
                <div>
                    <a href="dashboard.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Kembali ke Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Profile Card -->
        <div class="col-lg-4 mb-4">
            <div class="card shadow">
                <div class="card-body text-center">
                    <div class="profile-avatar mb-3">
                        <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center"
                            style="width: 100px; height: 100px; font-size: 2.5rem;">
                            <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                        </div>
                    </div>
                    <h5 class="font-weight-bold"><?php echo htmlspecialchars($user['full_name']); ?></h5>
                    <p class="text-muted mb-3"><?php echo htmlspecialchars($user['email']); ?></p>
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="h6 text-primary"><?php echo $total_orders; ?></div>
                            <small class="text-muted">Total Pesanan</small>
                        </div>
                        <div class="col-6">
                            <div class="h6 text-success"><?php echo formatRupiah($total_spent); ?></div>
                            <small class="text-muted">Total Belanja</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Account Info -->
            <div class="card shadow mt-4">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">Informasi Akun</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong>Username:</strong><br>
                        <span class="text-primary"><?php echo htmlspecialchars($user['username']); ?></span>
                    </div>
                    <div class="mb-3">
                        <strong>Role:</strong><br>
                        <span class="badge bg-success"><?php echo ucfirst($user['role']); ?></span>
                    </div>
                    <div class="mb-3">
                        <strong>Bergabung:</strong><br>
                        <?php echo formatDate($user['created_at']); ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Profile Forms -->
        <div class="col-lg-8">
            <!-- Update Profile -->
            <div class="card shadow mb-4">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-user-edit me-2"></i>Update Profil
                    </h6>
                </div>
                <div class="card-body">
                    <form method="POST" class="needs-validation" novalidate>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Nama Lengkap *</label>
                                    <input type="text" class="form-control" name="full_name"
                                        value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                                    <div class="invalid-feedback">Nama lengkap harus diisi</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Email *</label>
                                    <input type="email" class="form-control" name="email"
                                        value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                    <div class="invalid-feedback">Email harus diisi dengan format yang valid</div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Nomor Telepon</label>
                                    <input type="tel" class="form-control" name="phone"
                                        value="<?php echo htmlspecialchars($user['phone']); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Username</label>
                                    <input type="text" class="form-control"
                                        value="<?php echo htmlspecialchars($user['username']); ?>" readonly>
                                    <small class="text-muted">Username tidak dapat diubah</small>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Alamat</label>
                            <textarea class="form-control" name="address" rows="3"
                                placeholder="Alamat lengkap..."><?php echo htmlspecialchars($user['address']); ?></textarea>
                        </div>

                        <div class="text-end">
                            <button type="submit" name="update_profile" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Update Profil
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Change Password -->
            <div class="card shadow">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-lock me-2"></i>Ubah Password
                    </h6>
                </div>
                <div class="card-body">
                    <form method="POST" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <label class="form-label">Password Saat Ini *</label>
                            <div class="input-group">
                                <input type="password" class="form-control" name="current_password"
                                    id="currentPassword" required>
                                <button class="btn btn-outline-secondary" type="button" id="toggleCurrentPassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="invalid-feedback">Password saat ini harus diisi</div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Password Baru *</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" name="new_password"
                                            id="newPassword" minlength="6" required>
                                        <button class="btn btn-outline-secondary" type="button" id="toggleNewPassword">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <div class="invalid-feedback">Password baru minimal 6 karakter</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Konfirmasi Password Baru *</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" name="confirm_password"
                                            id="confirmPassword" minlength="6" required>
                                        <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <div class="invalid-feedback">Konfirmasi password harus diisi</div>
                                </div>
                            </div>
                        </div>

                        <!-- Password Strength Indicator -->
                        <div class="mb-3">
                            <div class="progress" style="height: 5px;">
                                <div class="progress-bar" id="passwordStrength" role="progressbar" style="width: 0%"></div>
                            </div>
                            <small class="text-muted" id="passwordStrengthText">Kekuatan password</small>
                        </div>

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Tips Password Kuat:</strong>
                            <ul class="mb-0 mt-2">
                                <li>Minimal 6 karakter</li>
                                <li>Kombinasi huruf besar dan kecil</li>
                                <li>Mengandung angka dan simbol</li>
                                <li>Hindari menggunakan informasi pribadi</li>
                            </ul>
                        </div>

                        <div class="text-end">
                            <button type="submit" name="change_password" class="btn btn-warning">
                                <i class="fas fa-key me-2"></i>Ubah Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

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

    // Toggle password visibility
    function togglePassword(inputId, buttonId) {
        const input = document.getElementById(inputId);
        const button = document.getElementById(buttonId);

        if (input.type === 'password') {
            input.type = 'text';
            button.innerHTML = '<i class="fas fa-eye-slash"></i>';
        } else {
            input.type = 'password';
            button.innerHTML = '<i class="fas fa-eye"></i>';
        }
    }

    document.getElementById('toggleCurrentPassword').addEventListener('click', function() {
        togglePassword('currentPassword', 'toggleCurrentPassword');
    });

    document.getElementById('toggleNewPassword').addEventListener('click', function() {
        togglePassword('newPassword', 'toggleNewPassword');
    });

    document.getElementById('toggleConfirmPassword').addEventListener('click', function() {
        togglePassword('confirmPassword', 'toggleConfirmPassword');
    });

    // Password strength checker
    document.getElementById('newPassword').addEventListener('input', function() {
        const password = this.value;
        let strength = 0;
        let strengthText = '';
        let strengthClass = '';

        // Length check
        if (password.length >= 6) strength += 20;
        if (password.length >= 10) strength += 20;

        // Character type checks
        if (/[a-z]/.test(password)) strength += 15;
        if (/[A-Z]/.test(password)) strength += 15;
        if (/[0-9]/.test(password)) strength += 15;
        if (/[^A-Za-z0-9]/.test(password)) strength += 15;

        // Set strength text and class
        if (strength < 30) {
            strengthText = 'Sangat Lemah';
            strengthClass = 'bg-danger';
        } else if (strength < 50) {
            strengthText = 'Lemah';
            strengthClass = 'bg-warning';
        } else if (strength < 70) {
            strengthText = 'Cukup';
            strengthClass = 'bg-info';
        } else {
            strengthText = 'Kuat';
            strengthClass = 'bg-success';
        }

        document.getElementById('passwordStrength').className = 'progress-bar ' + strengthClass;
        document.getElementById('passwordStrength').style.width = strength + '%';
        document.getElementById('passwordStrengthText').textContent = strengthText;
    });

    // Confirm password validation
    document.getElementById('confirmPassword').addEventListener('input', function() {
        const newPassword = document.getElementById('newPassword').value;
        const confirmPassword = this.value;

        if (confirmPassword && newPassword !== confirmPassword) {
            this.setCustomValidity('Password tidak cocok');
        } else {
            this.setCustomValidity('');
        }
    });
</script>

<?php include '../includes/footer.php'; ?>