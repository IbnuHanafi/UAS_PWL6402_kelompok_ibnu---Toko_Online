<?php

/**
 * Customer Dashboard
 * File: customer/dashboard.php
 * Toko Online - UAS Pemrograman Web Lanjut
 */

require_once '../includes/functions.php';

// Proteksi halaman customer
requireCustomer();

$page_title = 'Dashboard Customer';

global $db;

$user_id = $_SESSION['user_id'];

// Get customer statistics
$total_orders = $db->fetch("SELECT COUNT(*) as total FROM orders WHERE user_id = ?", [$user_id])['total'];
$pending_orders = $db->fetch("SELECT COUNT(*) as total FROM orders WHERE user_id = ? AND status = 'pending'", [$user_id])['total'];
$completed_orders = $db->fetch("SELECT COUNT(*) as total FROM orders WHERE user_id = ? AND status = 'completed'", [$user_id])['total'];
$cart_items = $db->fetch("SELECT COUNT(*) as total FROM cart WHERE user_id = ?", [$user_id])['total'];

// Get total spent
$total_spent = $db->fetch("
    SELECT COALESCE(SUM(total_amount), 0) as total 
    FROM orders 
    WHERE user_id = ? AND status = 'completed'
", [$user_id])['total'];

// Get recent orders
$recent_orders = $db->query("
    SELECT * FROM orders 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 5
", [$user_id]);

// Get featured products
$featured_products = $db->query("
    SELECT p.*, c.name as category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE p.status = 'active' 
    ORDER BY p.created_at DESC 
    LIMIT 8
");

// Get categories
$categories = $db->query("SELECT * FROM categories ORDER BY name");

include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <!-- Welcome Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card bg-gradient-primary text-white shadow">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-lg-8">
                            <div style=" padding: 1rem; border-radius: 8px;">
                                <h2 style="color: black;" class="mb-2">Selamat Datang, <?php echo htmlspecialchars($current_user['full_name']); ?>! 👋</h2>
                                <p style="color: black;" class="mb-0">Temukan produk favorit Anda dan nikmati pengalaman berbelanja yang menyenangkan</p>
                            </div>
                        </div>
                        <div class="col-lg-4 text-lg-end">
                            <div class="d-flex gap-2 justify-content-lg-end">
                                <a href="products.php" class="btn btn-warning">
                                    <i class="fas fa-shopping-bag me-2"></i>Mulai Belanja
                                </a>
                                <a href="cart.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-shopping-cart me-2"></i>Keranjang
                                    <?php if ($cart_items > 0): ?>
                                        <span class="badge bg-warning text-dark"><?php echo $cart_items; ?></span>
                                    <?php endif; ?>
                                </a>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Pesanan</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_orders; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-shopping-cart fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Pesanan Pending</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $pending_orders; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Pesanan Selesai</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $completed_orders; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total Belanja</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo formatRupiah($total_spent); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Recent Orders -->
        <div class="col-lg-8 mb-4">
            <div class="card shadow">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-white">Pesanan Terbaru</h6>
                    <a href="orders.php" class="btn btn-primary btn-sm">Lihat Semua</a>
                </div>
                <div class="card-body">
                    <?php if (empty($recent_orders)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Belum ada pesanan</h5>
                            <p class="text-muted">Mulai berbelanja untuk melihat riwayat pesanan Anda</p>
                            <a href="products.php" class="btn btn-primary">
                                <i class="fas fa-shopping-bag me-2"></i>Mulai Belanja
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>No. Pesanan</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                        <th>Tanggal</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_orders as $order): ?>
                                        <tr>
                                            <td>
                                                <span class="font-weight-bold"><?php echo htmlspecialchars($order['order_number']); ?></span>
                                            </td>
                                            <td class="font-weight-bold text-success">
                                                <?php echo formatRupiah($order['total_amount']); ?>
                                            </td>
                                            <td><?php echo getStatusBadge($order['status']); ?></td>
                                            <td>
                                                <small><?php echo formatDate($order['created_at']); ?></small>
                                            </td>
                                            <td>
                                                <a href="orders.php?view=<?php echo $order['id']; ?>"
                                                    class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i> Detail
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="col-lg-4 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-white">Aksi Cepat</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-3">
                        <a href="products.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-shopping-bag fa-lg me-3"></i>
                            <div class="text-start">
                                <div class="fw-bold">Lihat Produk</div>
                                <small>Jelajahi produk terbaru</small>
                            </div>
                        </a>

                        <a href="cart.php" class="btn btn-warning btn-lg">
                            <i class="fas fa-shopping-cart fa-lg me-3"></i>
                            <div class="text-start">
                                <div class="fw-bold">Keranjang Belanja</div>
                                <small><?php echo $cart_items; ?> item di keranjang</small>
                            </div>
                        </a>

                        <a href="orders.php" class="btn btn-info btn-lg">
                            <i class="fas fa-history fa-lg me-3"></i>
                            <div class="text-start">
                                <div class="fw-bold">Riwayat Pesanan</div>
                                <small>Lihat semua pesanan Anda</small>
                            </div>
                        </a>

                        <a href="profile.php" class="btn btn-secondary btn-lg">
                            <i class="fas fa-user-edit fa-lg me-3"></i>
                            <div class="text-start">
                                <div class="fw-bold">Edit Profil</div>
                                <small>Update informasi akun</small>
                            </div>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Categories Widget -->
            <div class="card shadow mt-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-white">Kategori Produk</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach ($categories as $category): ?>
                            <div class="col-6 mb-3">
                                <a href="products.php?category=<?php echo $category['id']; ?>"
                                    class="btn btn-outline-primary w-100 text-truncate">
                                    <i class="fas fa-tag me-2"></i>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Featured Products -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-white">Produk Tersedia</h6>
                    <a href="products.php" class="btn btn-primary btn-sm">Lihat Semua</a>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach (array_slice($featured_products, 0, 4) as $product): ?>
                            <div class="col-lg-3 col-md-6 mb-4">
                                <div class="card h-100 shadow-sm">
                                    <div class="product-image bg-light d-flex align-items-center justify-content-center"
                                        style="height: 200px;">
                                        <?php if ($product['image']): ?>
                                            <img src="../assets/images/products/<?php echo $product['image']; ?>"
                                                class="card-img-top" alt="<?php echo htmlspecialchars($product['name']); ?>"
                                                style="max-height: 200px; object-fit: cover;">
                                        <?php else: ?>
                                            <i class="fas fa-image fa-3x text-muted"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="card-body">
                                        <h6 class="card-title font-weight-bold text-truncate">
                                            <?php echo htmlspecialchars($product['name']); ?>
                                        </h6>
                                        <p class="card-text text-muted small">
                                            <?php echo htmlspecialchars(substr($product['description'], 0, 60)) . '...'; ?>
                                        </p>
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="h6 text-primary font-weight-bold mb-0">
                                                <?php echo formatRupiah($product['price']); ?>
                                            </span>
                                            <small class="text-muted">Stok: <?php echo $product['stock']; ?></small>
                                        </div>
                                    </div>
                                    <div class="card-footer bg-transparent">
                                        <button class="btn btn-primary btn-sm w-100 add-to-cart"
                                            data-product-id="<?php echo $product['id']; ?>">
                                            <i class="fas fa-cart-plus me-2"></i>Tambah ke Keranjang
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .border-left-primary {
        border-left: 0.25rem solid #4e73df !important;
    }

    .border-left-success {
        border-left: 0.25rem solid #1cc88a !important;
    }

    .border-left-warning {
        border-left: 0.25rem solid #f6c23e !important;
    }

    .border-left-info {
        border-left: 0.25rem solid #36b9cc !important;
    }

    .text-xs {
        font-size: 0.7rem;
    }

    .font-weight-bold {
        font-weight: 700 !important;
    }

    .text-gray-300 {
        color: #dddfeb !important;
    }

    .text-gray-800 {
        color: #5a5c69 !important;
    }
</style>

<?php include '../includes/footer.php'; ?>