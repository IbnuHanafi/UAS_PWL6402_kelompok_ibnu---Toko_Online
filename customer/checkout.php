<?php

/**
 * Customer Checkout
 * File: customer/checkout.php
 * Toko Online - UAS Pemrograman Web Lanjut
 */

require_once '../includes/functions.php';

// Proteksi halaman customer
requireCustomer();

$page_title = 'Checkout';

global $db;

$user_id = $_SESSION['user_id'];

// Get cart items
$cart_items = $db->query("
    SELECT c.*, p.name, p.price, p.image, p.stock
    FROM cart c
    LEFT JOIN products p ON c.product_id = p.id
    WHERE c.user_id = ? AND p.status = 'active'
    ORDER BY c.created_at DESC
", [$user_id]);

// Redirect if cart is empty
if (empty($cart_items)) {
    setFlashMessage('warning', 'Keranjang belanja kosong');
    redirect('cart.php');
}

// Calculate totals
$subtotal = 0;
$total_items = 0;

foreach ($cart_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
    $total_items += $item['quantity'];
}

$shipping_cost = $subtotal > 100000 ? 0 : 15000;
$total = $subtotal + $shipping_cost;

// Get user data
$user = getCurrentUser();

// Process checkout
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['place_order'])) {
    $shipping_address = cleanInput($_POST['shipping_address']);
    $payment_method = cleanInput($_POST['payment_method']);
    $notes = cleanInput($_POST['notes']);

    if (empty($shipping_address) || empty($payment_method)) {
        setFlashMessage('error', 'Alamat pengiriman dan metode pembayaran harus diisi');
    } else {
        try {
            // Start transaction
            $db->getConnection()->beginTransaction();

            // Generate order number
            $order_number = generateOrderNumber();

            // Create order
            $order_sql = "INSERT INTO orders (user_id, order_number, total_amount, shipping_address, notes, status) VALUES (?, ?, ?, ?, ?, 'pending')";
            $db->execute($order_sql, [$user_id, $order_number, $total, $shipping_address, $notes]);

            $order_id = $db->lastInsertId();

            // Create order items
            foreach ($cart_items as $item) {
                $item_total = $item['price'] * $item['quantity'];
                $item_sql = "INSERT INTO order_items (order_id, product_id, quantity, price, subtotal) VALUES (?, ?, ?, ?, ?)";
                $db->execute($item_sql, [$order_id, $item['product_id'], $item['quantity'], $item['price'], $item_total]);

                // Update product stock
                $update_stock_sql = "UPDATE products SET stock = stock - ? WHERE id = ?";
                $db->execute($update_stock_sql, [$item['quantity'], $item['product_id']]);
            }

            // Clear cart
            $clear_cart_sql = "DELETE FROM cart WHERE user_id = ?";
            $db->execute($clear_cart_sql, [$user_id]);

            // Commit transaction
            $db->getConnection()->commit();

            // Redirect to success page
            setFlashMessage('success', 'Pesanan berhasil dibuat! Nomor pesanan: ' . $order_number);
            redirect('orders.php?view=' . $order_id);
        } catch (Exception $e) {
            // Rollback transaction
            $db->getConnection()->rollback();
            setFlashMessage('error', 'Terjadi kesalahan saat memproses pesanan: ' . $e->getMessage());
        }
    }
}

include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 text-gradient font-weight-bold">Checkout</h1>
                    <p class="text-muted">Lengkapi informasi untuk menyelesaikan pesanan</p>
                </div>
                <div>
                    <a href="cart.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Kembali ke Keranjang
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Checkout Steps -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-body">
                    <div class="step-indicator d-flex justify-content-between">
                        <div class="step completed">
                            <div class="step-circle">1</div>
                            <span>Keranjang</span>
                        </div>
                        <div class="step current">
                            <div class="step-circle">2</div>
                            <span>Checkout</span>
                        </div>
                        <div class="step">
                            <div class="step-circle">3</div>
                            <span>Pembayaran</span>
                        </div>
                        <div class="step">
                            <div class="step-circle">4</div>
                            <span>Selesai</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <form method="POST" class="needs-validation" novalidate>
        <div class="row">
            <!-- Checkout Form -->
            <div class="col-lg-8 mb-4">
                <!-- Shipping Information -->
                <div class="card shadow mb-4">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold text-white">
                            <i class="fas fa-shipping-fast me-2"></i>Informasi Pengiriman
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Nama Lengkap</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['full_name']); ?>" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Nomor Telepon</label>
                                    <input type="tel" class="form-control" value="<?php echo htmlspecialchars($user['phone']); ?>" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Kota/Kecamatan</label>
                                    <select class="form-select" required>
                                        <option value="">Pilih kota...</option>
                                        <option value="jakarta">Jakarta</option>
                                        <option value="bandung">Bandung</option>
                                        <option value="surabaya">Surabaya</option>
                                        <option value="medan">Medan</option>
                                        <option value="semarang">Semarang</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Alamat Lengkap *</label>
                            <textarea class="form-control" name="shipping_address" rows="3"
                                placeholder="Masukkan alamat lengkap untuk pengiriman..." required><?php echo htmlspecialchars($user['address']); ?></textarea>
                            <div class="invalid-feedback">Alamat pengiriman harus diisi</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Catatan untuk Penjual</label>
                            <textarea class="form-control" name="notes" rows="2"
                                placeholder="Catatan tambahan (opsional)"></textarea>
                        </div>
                    </div>
                </div>

                <!-- Payment Method -->
                <div class="card shadow mb-4">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold text-white">
                            <i class="fas fa-credit-card me-2"></i>Metode Pembayaran
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="form-check payment-option">
                                    <input class="form-check-input" type="radio" name="payment_method"
                                        id="bank_transfer" value="bank_transfer" required>
                                    <label class="form-check-label w-100" for="bank_transfer">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-university fa-2x text-primary me-3"></i>
                                            <div>
                                                <div class="fw-bold">Transfer Bank</div>
                                                <small class="text-muted">BCA, Mandiri, BNI, BRI</small>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="form-check payment-option">
                                    <input class="form-check-input" type="radio" name="payment_method"
                                        id="e_wallet" value="e_wallet" required>
                                    <label class="form-check-label w-100" for="e_wallet">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-mobile-alt fa-2x text-success me-3"></i>
                                            <div>
                                                <div class="fw-bold">E-Wallet</div>
                                                <small class="text-muted">OVO, GoPay, DANA, LinkAja</small>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="form-check payment-option">
                                    <input class="form-check-input" type="radio" name="payment_method"
                                        id="credit_card" value="credit_card" required>
                                    <label class="form-check-label w-100" for="credit_card">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-credit-card fa-2x text-warning me-3"></i>
                                            <div>
                                                <div class="fw-bold">Kartu Kredit</div>
                                                <small class="text-muted">Visa, Mastercard, JCB</small>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="form-check payment-option">
                                    <input class="form-check-input" type="radio" name="payment_method"
                                        id="cod" value="cod" required>
                                    <label class="form-check-label w-100" for="cod">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-money-bill-wave fa-2x text-danger me-3"></i>
                                            <div>
                                                <div class="fw-bold">Bayar di Tempat (COD)</div>
                                                <small class="text-muted">Bayar saat barang diterima</small>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="invalid-feedback">Pilih metode pembayaran</div>
                    </div>
                </div>

                <!-- Order Items -->
                <div class="card shadow">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold text-white">
                            <i class="fas fa-box me-2"></i>Item Pesanan (<?php echo count($cart_items); ?> produk)
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php foreach ($cart_items as $item): ?>
                            <div class="d-flex align-items-center mb-3 border-bottom pb-3">
                                <div class="me-3">
                                    <?php if ($item['image']): ?>
                                        <img src="../assets/images/products/<?php echo $item['image']; ?>"
                                            class="rounded" width="60" height="60" style="object-fit: cover;">
                                    <?php else: ?>
                                        <div class="bg-light d-flex align-items-center justify-content-center rounded"
                                            style="width: 60px; height: 60px;">
                                            <i class="fas fa-image text-muted"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($item['name']); ?></h6>
                                    <p class="text-muted mb-1"><?php echo formatRupiah($item['price']); ?> x <?php echo $item['quantity']; ?></p>
                                </div>
                                <div class="text-end">
                                    <div class="fw-bold text-primary"><?php echo formatRupiah($item['price'] * $item['quantity']); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Order Summary -->
            <div class="col-lg-4 mb-4">
                <div class="card shadow sticky-top" style="top: 100px;">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold text-white">
                            <i class="fas fa-receipt me-2"></i>Ringkasan Pesanan
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal (<?php echo $total_items; ?> item)</span>
                            <span><?php echo formatRupiah($subtotal); ?></span>
                        </div>

                        <div class="d-flex justify-content-between mb-2">
                            <span>Ongkos Kirim</span>
                            <span>
                                <?php if ($shipping_cost == 0): ?>
                                    <span class="text-success">GRATIS</span>
                                <?php else: ?>
                                    <?php echo formatRupiah($shipping_cost); ?>
                                <?php endif; ?>
                            </span>
                        </div>

                        <div class="d-flex justify-content-between mb-2">
                            <span>Biaya Admin</span>
                            <span>Rp 0</span>
                        </div>

                        <hr>

                        <div class="d-flex justify-content-between mb-3">
                            <span class="h6 font-weight-bold">Total Pembayaran</span>
                            <span class="h6 font-weight-bold text-primary"><?php echo formatRupiah($total); ?></span>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" name="place_order" class="btn btn-primary btn-lg">
                                <i class="fas fa-check me-2"></i>Buat Pesanan
                            </button>
                        </div>

                        <div class="mt-3 text-center">
                            <small class="text-muted">
                                <i class="fas fa-shield-alt me-1"></i>
                                Transaksi Anda aman dan terlindungi
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<style>
    .step-indicator {
        position: relative;
    }

    .step-indicator::before {
        content: '';
        position: absolute;
        top: 15px;
        left: 30px;
        right: 30px;
        height: 2px;
        background: #e9ecef;
        z-index: 1;
    }

    .step {
        text-align: center;
        position: relative;
        z-index: 2;
    }

    .step-circle {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        background: #e9ecef;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 0.5rem;
        font-weight: bold;
        color: #6c757d;
    }

    .step.completed .step-circle {
        background: #28a745;
        color: white;
    }

    .step.current .step-circle {
        background: #007bff;
        color: white;
    }

    .payment-option {
        border: 2px solid #e9ecef;
        border-radius: 8px;
        padding: 1rem;
        transition: all 0.3s ease;
    }

    .payment-option:hover {
        border-color: #007bff;
        background-color: #f8f9fa;
    }

    .payment-option input[type="radio"]:checked+label {
        border-color: #007bff;
        background-color: #e7f3ff;
    }
</style>

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

    // Payment method selection effect
    $('input[name="payment_method"]').change(function() {
        $('.payment-option').removeClass('selected');
        $(this).closest('.payment-option').addClass('selected');
    });
</script>

<?php include '../includes/footer.php'; ?>