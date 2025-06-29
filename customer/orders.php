<?php

/**
 * Customer Orders
 * File: customer/orders.php
 * Toko Online - UAS Pemrograman Web Lanjut
 */

require_once '../includes/functions.php';

// Proteksi halaman customer
requireCustomer();

$page_title = 'Pesanan Saya';

global $db;

$user_id = $_SESSION['user_id'];

// Get view mode
$view = $_GET['view'] ?? 'list';
$order_id = $_GET['id'] ?? null;

if ($view == 'detail' && $order_id) {
    // Get order detail
    $order = $db->fetch("
        SELECT * FROM orders 
        WHERE id = ? AND user_id = ?
    ", [$order_id, $user_id]);

    if (!$order) {
        redirect('orders.php');
    }

    // Get order items
    $order_items = $db->query("
        SELECT oi.*, p.name as product_name, p.image
        FROM order_items oi
        LEFT JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = ?
    ", [$order_id]);
} else {
    // Get orders with pagination
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;

    $status_filter = $_GET['status'] ?? '';

    $where_sql = "WHERE user_id = ?";
    $params = [$user_id];

    if (!empty($status_filter)) {
        $where_sql .= " AND status = ?";
        $params[] = $status_filter;
    }

    // Get total count
    $count_sql = "SELECT COUNT(*) as total FROM orders $where_sql";
    $total_orders = $db->fetch($count_sql, $params)['total'];
    $total_pages = ceil($total_orders / $limit);

    // Get orders
    $orders_sql = "
        SELECT * FROM orders 
        $where_sql 
        ORDER BY created_at DESC 
        LIMIT $limit OFFSET $offset
    ";
    $orders = $db->query($orders_sql, $params);

    // Get statistics
    $stats = [
        'total' => $db->fetch("SELECT COUNT(*) as count FROM orders WHERE user_id = ?", [$user_id])['count'],
        'pending' => $db->fetch("SELECT COUNT(*) as count FROM orders WHERE user_id = ? AND status = 'pending'", [$user_id])['count'],
        'processing' => $db->fetch("SELECT COUNT(*) as count FROM orders WHERE user_id = ? AND status = 'processing'", [$user_id])['count'],
        'completed' => $db->fetch("SELECT COUNT(*) as count FROM orders WHERE user_id = ? AND status = 'completed'", [$user_id])['count'],
        'cancelled' => $db->fetch("SELECT COUNT(*) as count FROM orders WHERE user_id = ? AND status = 'cancelled'", [$user_id])['count']
    ];

    $total_spent = $db->fetch("
        SELECT COALESCE(SUM(total_amount), 0) as total 
        FROM orders 
        WHERE user_id = ? AND status = 'completed'
    ", [$user_id])['total'];
}

include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <?php if ($view == 'detail'): ?>
        <!-- Order Detail View -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="h3 text-gradient font-weight-bold">Detail Pesanan</h1>
                        <p class="text-muted">Pesanan #<?php echo htmlspecialchars($order['order_number']); ?></p>
                    </div>
                    <div>
                        <a href="orders.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Kembali
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Order Status Timeline -->
            <div class="col-lg-4 mb-4">
                <div class="card shadow">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold text-white">Status Pesanan</h6>
                    </div>
                    <div class="card-body">
                        <div class="order-timeline">
                            <div class="timeline-item <?php echo in_array($order['status'], ['pending', 'processing', 'completed']) ? 'completed' : ''; ?>">
                                <div class="timeline-content">
                                    <h6>Pesanan Dibuat</h6>
                                    <small class="text-muted"><?php echo formatDate($order['created_at']); ?></small>
                                </div>
                            </div>

                            <div class="timeline-item <?php echo in_array($order['status'], ['processing', 'completed']) ? 'completed' : ''; ?>">
                                <div class="timeline-content">
                                    <h6>Sedang Diproses</h6>
                                    <small class="text-muted">
                                        <?php echo $order['status'] == 'processing' ? 'Pesanan sedang disiapkan' : '-'; ?>
                                    </small>
                                </div>
                            </div>

                            <div class="timeline-item <?php echo $order['status'] == 'completed' ? 'completed' : ''; ?>">
                                <div class="timeline-content">
                                    <h6>Pesanan Selesai</h6>
                                    <small class="text-muted">
                                        <?php echo $order['status'] == 'completed' ? 'Pesanan telah selesai' : '-'; ?>
                                    </small>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3 text-center">
                            <?php echo getStatusBadge($order['status']); ?>
                        </div>
                    </div>
                </div>

                <!-- Order Information -->
                <div class="card shadow mt-4">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold text-white">Informasi Pesanan</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <strong>Nomor Pesanan:</strong><br>
                            <span class="text-primary"><?php echo htmlspecialchars($order['order_number']); ?></span>
                        </div>

                        <div class="mb-3">
                            <strong>Tanggal Pesanan:</strong><br>
                            <?php echo formatDate($order['created_at']); ?>
                        </div>

                        <div class="mb-3">
                            <strong>Total Pembayaran:</strong><br>
                            <span class="h6 text-success"><?php echo formatRupiah($order['total_amount']); ?></span>
                        </div>

                        <div class="mb-3">
                            <strong>Alamat Pengiriman:</strong><br>
                            <address class="small">
                                <?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?>
                            </address>
                        </div>

                        <?php if ($order['notes']): ?>
                            <div class="mb-3">
                                <strong>Catatan:</strong><br>
                                <p class="small"><?php echo nl2br(htmlspecialchars($order['notes'])); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Order Items -->
            <div class="col-lg-8 mb-4">
                <div class="card shadow">
                    <div class="card-header">
                        <h6 class="m-0 font-weight-bold text-white">Item Pesanan</h6>
                    </div>
                    <div class="card-body">
                        <?php foreach ($order_items as $item): ?>
                            <div class="d-flex align-items-center mb-3 border-bottom pb-3">
                                <div class="me-3">
                                    <?php if ($item['image']): ?>
                                        <img src="../assets/images/products/<?php echo $item['image']; ?>"
                                            class="rounded" width="80" height="80" style="object-fit: cover;">
                                    <?php else: ?>
                                        <div class="bg-light d-flex align-items-center justify-content-center rounded"
                                            style="width: 80px; height: 80px;">
                                            <i class="fas fa-image text-muted"></i>
                                        </div>
                                    <?php endif; ?>

                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($item['product_name']); ?></h6>
                                    <p class="text-muted mb-1">
                                        <?php echo formatRupiah($item['price']); ?> x <?php echo $item['quantity']; ?>
                                    </p>
                                </div>
                                <div class="text-end">
                                    <div class="h6 font-weight-bold text-success">
                                        <?php echo formatRupiah($item['subtotal']); ?>
                                    </div>
                                    
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <!-- Total -->
                        <div class="text-end">
                            <div class="h5 font-weight-bold text-primary">
                                Total: <?php echo formatRupiah($order['total_amount']); ?>
                            </div>
                        </div>
                        <div class="text-center" style="padding: 1rem; margin-top: 1.5rem; background-color: #f8d7da; color: #6a1a21; border: 1px solid #f5c2c7; border-radius: 0.375rem;" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Harap hubungi nomor admin yang tertera di footer (Customer Service) jika ingin membatalkan pesanan Anda ataupun mencari informasi lainnya.
                        </div>
                    </div>
                </div>
            </div>
        </div>

    <?php else: ?>
        <!-- Orders List View -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="h3 text-gradient font-weight-bold">Pesanan Saya</h1>
                        <p class="text-muted">Riwayat dan status pesanan Anda</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-2 mb-3">
                <div class="card bg-primary text-white shadow">
                    <div class="card-body text-center">
                        <div class="h4 mb-0"><?php echo $stats['total']; ?></div>
                        <small>Total Pesanan</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2 mb-3">
                <div class="card bg-warning text-dark shadow">
                    <div class="card-body text-center">
                        <div class="h4 mb-0"><?php echo $stats['pending']; ?></div>
                        <small>Pending</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2 mb-3">
                <div class="card bg-info text-white shadow">
                    <div class="card-body text-center">
                        <div class="h4 mb-0"><?php echo $stats['processing']; ?></div>
                        <small>Diproses</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2 mb-3">
                <div class="card bg-success text-white shadow">
                    <div class="card-body text-center">
                        <div class="h4 mb-0"><?php echo $stats['completed']; ?></div>
                        <small>Selesai</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2 mb-3">
                <div class="card bg-danger text-white shadow">
                    <div class="card-body text-center">
                        <div class="h4 mb-0"><?php echo $stats['cancelled']; ?></div>
                        <small>Dibatalkan</small>
                    </div>
                </div>
            </div>
            <div class="col-md-2 mb-3">
                <div class="card bg-secondary text-white shadow">
                    <div class="card-body text-center">
                        <div class="h6 mb-0"><?php echo formatRupiah($total_spent); ?></div>
                        <small>Total Belanja</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-4">
                                <select class="form-select" onchange="filterOrders(this.value)">
                                    <option value="">Semua Status</option>
                                    <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="processing" <?php echo $status_filter == 'processing' ? 'selected' : ''; ?>>Diproses</option>
                                    <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>Selesai</option>
                                    <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Dibatalkan</option>
                                </select>
                            </div>
                            <div class="col-md-8 text-md-end">
                                <span class="text-muted">
                                    Menampilkan <?php echo count($orders); ?> dari <?php echo $total_orders; ?> pesanan
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Orders List -->
        <div class="row">
            <div class="col-12">
                <?php if (empty($orders)): ?>
                    <div class="card shadow">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Belum ada pesanan</h5>
                            <p class="text-muted">Mulai berbelanja untuk melihat riwayat pesanan Anda</p>
                            <a href="products.php" class="btn btn-primary">
                                <i class="fas fa-shopping-bag me-2"></i>Mulai Belanja
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($orders as $order): ?>
                        <div class="card shadow mb-3">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-lg-3">
                                        <div class="font-weight-bold"><?php echo htmlspecialchars($order['order_number']); ?></div>
                                        <small class="text-muted"><?php echo formatDate($order['created_at']); ?></small>
                                    </div>
                                    <div class="col-lg-3">
                                        <div class="font-weight-bold text-success"><?php echo formatRupiah($order['total_amount']); ?></div>
                                    </div>
                                    <div class="col-lg-3">
                                        <?php echo getStatusBadge($order['status']); ?>
                                    </div>
                                    <div class="col-lg-3 text-lg-end">
                                        <a href="?view=detail&id=<?php echo $order['id']; ?>"
                                            class="btn btn-outline-primary btn-sm me-2">
                                            <i class="fas fa-eye me-1"></i>Detail
                                        </a>
                                        <?php if ($order['status'] == 'pending'): ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <div class="text-center" style="padding: 1rem; margin-top: 1.5rem; background-color: #f8d7da; color: #6a1a21; border: 1px solid #f5c2c7; border-radius: 0.375rem;" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Harap hubungi nomor admin yang tertera di footer (Customer Service) jika ingin membatalkan pesanan Anda ataupun mencari informasi lainnya.
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="d-flex justify-content-center mt-4">
                            <?php echo getPagination($page, $total_pages, 'orders.php'); ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
    .order-timeline {
        position: relative;
        padding-left: 1rem;
    }

    .order-timeline::before {
        content: '';
        position: absolute;
        left: 0.5rem;
        top: 0;
        bottom: 0;
        width: 2px;
        background: #e9ecef;
    }

    .timeline-item {
        position: relative;
        margin-bottom: 1.5rem;
    }

    .timeline-item::before {
        content: '';
        position: absolute;
        left: -0.5rem;
        top: 0.5rem;
        width: 1rem;
        height: 1rem;
        border-radius: 50%;
        background: #e9ecef;
        border: 2px solid white;
    }

    .timeline-item.completed::before {
        background: #28a745;
    }

    .timeline-content {
        margin-left: 1rem;
    }
</style>

<script>
    function filterOrders(status) {
        const url = new URL(window.location);
        if (status) {
            url.searchParams.set('status', status);
        } else {
            url.searchParams.delete('status');
        }
        window.location.href = url.toString();
    }
</script>

<?php include '../includes/footer.php'; ?>