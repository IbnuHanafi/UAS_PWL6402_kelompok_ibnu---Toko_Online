<?php

/**
 * Admin Orders Management
 * File: admin/orders.php
 * Toko Online - UAS Pemrograman Web Lanjut
 */

require_once '../includes/functions.php';

// Proteksi halaman admin
requireAdmin();

$page_title = 'Kelola Pesanan';

global $db;

// Handle status update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $order_id = (int)$_POST['order_id'];
    $new_status = $_POST['status'];

    $valid_statuses = ['pending', 'processing', 'completed', 'cancelled'];
    if (in_array($new_status, $valid_statuses)) {
        $sql = "UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?";
        if ($db->execute($sql, [$new_status, $order_id])) {
            setFlashMessage('success', 'Status pesanan berhasil diupdate');
        } else {
            setFlashMessage('error', 'Gagal mengupdate status pesanan');
        }
    } else {
        setFlashMessage('error', 'Status tidak valid');
    }
    redirect('orders.php');
}

// Get view mode
$view = $_GET['view'] ?? 'list';
$order_id = $_GET['id'] ?? null;

if ($view == 'detail' && $order_id) {
    // Get order detail
    $order = $db->fetch("
        SELECT o.*, u.full_name, u.email, u.phone, u.address as user_address
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        WHERE o.id = ?
    ", [$order_id]);

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
    // Get filters
    $status_filter = $_GET['status'] ?? '';
    $search = $_GET['search'] ?? '';
    $date_from = $_GET['date_from'] ?? '';
    $date_to = $_GET['date_to'] ?? '';

    // Pagination
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 15;
    $offset = ($page - 1) * $limit;

    // Build where conditions
    $where_conditions = [];
    $params = [];

    if (!empty($status_filter)) {
        $where_conditions[] = "o.status = ?";
        $params[] = $status_filter;
    }

    if (!empty($search)) {
        $where_conditions[] = "(o.order_number LIKE ? OR u.full_name LIKE ? OR u.email LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    if (!empty($date_from)) {
        $where_conditions[] = "DATE(o.created_at) >= ?";
        $params[] = $date_from;
    }

    if (!empty($date_to)) {
        $where_conditions[] = "DATE(o.created_at) <= ?";
        $params[] = $date_to;
    }

    $where_sql = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

    // Get total count
    $count_sql = "SELECT COUNT(*) as total FROM orders o LEFT JOIN users u ON o.user_id = u.id $where_sql";
    $total_orders = $db->fetch($count_sql, $params)['total'];
    $total_pages = ceil($total_orders / $limit);

    // Get orders
    $orders_sql = "
        SELECT o.*, u.full_name, u.email
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        $where_sql
        ORDER BY o.created_at DESC
        LIMIT $limit OFFSET $offset
    ";
    $orders = $db->query($orders_sql, $params);

    // Get statistics
    $stats = [
        'total' => $db->fetch("SELECT COUNT(*) as count FROM orders")['count'],
        'pending' => $db->fetch("SELECT COUNT(*) as count FROM orders WHERE status = 'pending'")['count'],
        'processing' => $db->fetch("SELECT COUNT(*) as count FROM orders WHERE status = 'processing'")['count'],
        'completed' => $db->fetch("SELECT COUNT(*) as count FROM orders WHERE status = 'completed'")['count'],
        'cancelled' => $db->fetch("SELECT COUNT(*) as count FROM orders WHERE status = 'cancelled'")['count']
    ];

    $revenue_today = $db->fetch("
        SELECT COALESCE(SUM(total_amount), 0) as revenue 
        FROM orders 
        WHERE DATE(created_at) = CURDATE() AND status = 'completed'
    ")['revenue'];
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
                        <h1 class="h3 text-gradient fw-bold">Detail Pesanan</h1>
                        <p class="text-muted">Pesanan #<?php echo htmlspecialchars($order['order_number']); ?></p>
                    </div>
                    <div>
                        <a href="orders.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Kembali ke Daftar
                        </a>
                        <button class="btn btn-primary ms-2" onclick="window.print()">
                            <i class="fas fa-print me-2"></i>Cetak
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Order Information -->
            <div class="col-lg-8 mb-4">
                <div class="card shadow">
                    <div class="card-header">
                        <h6 class="m-0 fw-bold text-white">Informasi Pesanan</h6>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Nomor Pesanan:</strong><br>
                                <span class="badge bg-primary fs-6"><?php echo htmlspecialchars($order['order_number']); ?></span>
                            </div>
                            <div class="col-md-6">
                                <strong>Status:</strong><br>
                                <?php echo getStatusBadge($order['status']); ?>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Tanggal Pesanan:</strong><br>
                                <?php echo formatDate($order['created_at']); ?>
                            </div>
                            <div class="col-md-6">
                                <strong>Total Amount:</strong><br>
                                <span class="h5 text-success"><?php echo formatRupiah($order['total_amount']); ?></span>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <strong>Alamat Pengiriman:</strong><br>
                                <address class="mt-2">
                                    <?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?>
                                </address>
                            </div>
                        </div>

                        <?php if ($order['notes']): ?>
                            <div class="row">
                                <div class="col-12">
                                    <strong>Catatan:</strong><br>
                                    <p class="mt-2"><?php echo nl2br(htmlspecialchars($order['notes'])); ?></p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Order Items -->
                <div class="card shadow mt-4">
                    <div class="card-header">
                        <h6 class="m-0 fw-bold text-white">Item Pesanan</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>Produk</th>
                                        <th>Harga</th>
                                        <th>Qty</th>
                                        <th>Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($order_items as $item): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="me-3">
                                                        <?php if ($item['image']): ?>
                                                            <img src="../assets/images/products/<?php echo $item['image']; ?>"
                                                                class="img-thumbnail" width="50" height="50" style="object-fit: cover;">
                                                        <?php else: ?>
                                                            <div class="bg-light d-flex align-items-center justify-content-center rounded"
                                                                style="width: 50px; height: 50px;">
                                                                <i class="fas fa-image text-muted"></i>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold"><?php echo htmlspecialchars($item['product_name']); ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo formatRupiah($item['price']); ?></td>
                                            <td><span class="badge bg-secondary"><?php echo $item['quantity']; ?></span></td>
                                            <td class="fw-bold text-success"><?php echo formatRupiah($item['subtotal']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th colspan="3" class="text-end">Total:</th>
                                        <th class="text-success"><?php echo formatRupiah($order['total_amount']); ?></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Customer Information & Actions -->
            <div class="col-lg-4 mb-4">
                <!-- Customer Info -->
                <div class="card shadow">
                    <div class="card-header">
                        <h6 class="m-0 fw-bold text-white">Informasi Pelanggan</h6>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-3">
                            <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center"
                                style="width: 60px; height: 60px;">
                                <i class="fas fa-user fa-2x"></i>
                            </div>
                        </div>

                        <div class="text-center">
                            <h6 class="fw-bold"><?php echo htmlspecialchars($order['full_name']); ?></h6>
                            <p class="text-muted mb-2">
                                <i class="fas fa-envelope me-2"></i>
                                <?php echo htmlspecialchars($order['email']); ?>
                            </p>
                            <?php if ($order['phone']): ?>
                                <p class="text-muted mb-0">
                                    <i class="fas fa-phone me-2"></i>
                                    <?php echo htmlspecialchars($order['phone']); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Update Status -->
                <div class="card shadow mt-4">
                    <div class="card-header">
                        <h6 class="m-0 fw-bold text-white">Update Status</h6>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                            <div class="mb-3">
                                <label class="form-label">Status Pesanan</label>
                                <select class="form-select" name="status" required>
                                    <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="processing" <?php echo $order['status'] == 'processing' ? 'selected' : ''; ?>>Processing</option>
                                    <option value="completed" <?php echo $order['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="cancelled" <?php echo $order['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </div>
                            <button type="submit" name="update_status" class="btn btn-primary w-100">
                                <i class="fas fa-save me-2"></i>Update Status
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card shadow mt-4">
                    <div class="card-header">
                        <h6 class="m-0 fw-bold text-white">Aksi Cepat</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button class="btn btn-outline-primary" onclick="window.print()">
                                <i class="fas fa-print me-2"></i>Cetak Invoice
                            </button>
                            <button class="btn btn-outline-success" onclick="sendWhatsApp('<?php echo $order['phone']; ?>')">
                                <i class="fab fa-whatsapp me-2"></i>Kirim WhatsApp
                            </button>
                            <button class="btn btn-outline-info" onclick="sendEmail('<?php echo $order['email']; ?>')">
                                <i class="fas fa-envelope me-2"></i>Kirim Email
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    <?php else: ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="h3 text-gradient fw-bold">Kelola Pesanan</h1>
                        <p class="text-muted">Manajemen pesanan pelanggan</p>
                    </div>
                    <div>
                        <!-- Export Buttons -->
                        <div class="btn-group me-2" role="group">
                            <button class="btn btn-success" onclick="exportOrders()">
                                <i class="fas fa-download me-2"></i>Export Data
                            </button>
                            <button type="button" class="btn btn-success dropdown-toggle dropdown-toggle-split"
                                data-bs-toggle="dropdown">
                                <span class="visually-hidden">Toggle Dropdown</span>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#" onclick="quickExportExcel()">
                                        <i class="fas fa-file-excel text-success me-2"></i>Excel
                                    </a></li>
                                <li><a class="dropdown-item" href="#" onclick="quickExportCSV()">
                                        <i class="fas fa-file-csv text-info me-2"></i>CSV
                                    </a></li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li><a class="dropdown-item" href="#" onclick="quickExportPDF()">
                                        <i class="fas fa-file-pdf text-danger me-2"></i>PDF (HTML)
                                    </a></li>
                            </ul>
                        </div>

                        <button class="btn btn-danger" onclick="exportOrdersPDF()">
                            <i class="fas fa-file-pdf me-2"></i>Export PDF
                        </button>
                    </div>
                </div>
            </div>
        </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
        <div class="card bg-primary text-white shadow">
            <div class="card-body text-center">
                <div class="h4 mb-0"><?php echo $stats['total']; ?></div>
                <small>Total Pesanan</small>
            </div>
        </div>
    </div>
    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
        <div class="card bg-warning text-white shadow">
            <div class="card-body text-center">
                <div class="h4 mb-0"><?php echo $stats['pending']; ?></div>
                <small>Pending</small>
            </div>
        </div>
    </div>
    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
        <div class="card bg-info text-white shadow">
            <div class="card-body text-center">
                <div class="h4 mb-0"><?php echo $stats['processing']; ?></div>
                <small>Processing</small>
            </div>
        </div>
    </div>
    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
        <div class="card bg-success text-white shadow">
            <div class="card-body text-center">
                <div class="h4 mb-0"><?php echo $stats['completed']; ?></div>
                <small>Completed</small>
            </div>
        </div>
    </div>
    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
        <div class="card bg-danger text-white shadow">
            <div class="card-body text-center">
                <div class="h4 mb-0"><?php echo $stats['cancelled']; ?></div>
                <small>Cancelled</small>
            </div>
        </div>
    </div>
    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
        <div class="card bg-secondary text-white shadow">
            <div class="card-body text-center">
                <div class="h6 mb-0"><?php echo formatRupiah($revenue_today); ?></div>
                <small>Revenue Hari Ini</small>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Cari Pesanan</label>
                        <input type="text" class="form-control" name="search" placeholder="Nomor pesanan, nama, email..."
                            value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <option value="">Semua Status</option>
                            <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="processing" <?php echo $status_filter == 'processing' ? 'selected' : ''; ?>>Processing</option>
                            <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Dari Tanggal</label>
                        <input type="date" class="form-control" name="date_from" value="<?php echo $date_from; ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Sampai Tanggal</label>
                        <input type="date" class="form-control" name="date_to" value="<?php echo $date_to; ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid gap-2 d-md-flex">
                            <button type="submit" class="btn btn-primary flex-fill">
                                <i class="fas fa-search"></i> Cari
                            </button>
                            <a href="orders.php" class="btn btn-outline-secondary">
                                <i class="fas fa-undo"></i> Reset
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Orders Table -->
<div class="row">
    <div class="col-12">
        <div class="card shadow">
            <div class="card-header">
                <h6 class="m-0 fw-bold text-white">
                    Daftar Pesanan (<?php echo number_format($total_orders); ?> pesanan)
                </h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>No. Pesanan</th>
                                <th>Pelanggan</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Tanggal</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($orders)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        <i class="fas fa-shopping-cart fa-2x mb-2"></i>
                                        <br>Tidak ada pesanan ditemukan
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td>
                                            <span class="fw-bold"><?php echo htmlspecialchars($order['order_number']); ?></span>
                                        </td>
                                        <td>
                                            <div class="fw-bold"><?php echo htmlspecialchars($order['full_name']); ?></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($order['email']); ?></small>
                                        </td>
                                        <td class="fw-bold text-success"><?php echo formatRupiah($order['total_amount']); ?></td>
                                        <td><?php echo getStatusBadge($order['status']); ?></td>
                                        <td>
                                            <small><?php echo formatDate($order['created_at']); ?></small>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="?view=detail&id=<?php echo $order['id']; ?>"
                                                    class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <select class="form-select form-select-sm update-order-status"
                                                    data-order-id="<?php echo $order['id']; ?>" style="width: auto;">
                                                    <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                    <option value="processing" <?php echo $order['status'] == 'processing' ? 'selected' : ''; ?>>Processing</option>
                                                    <option value="completed" <?php echo $order['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                                    <option value="cancelled" <?php echo $order['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                                </select>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="d-flex justify-content-center mt-4">
                        <?php echo getPagination($page, $total_pages, 'orders.php'); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
</div>

<script>
    function sendWhatsApp(phone) {
        if (phone) {
            const message = encodeURIComponent('Halo, pesanan Anda sedang diproses. Terima kasih telah berbelanja di toko kami!');
            window.open(`https://wa.me/${phone.replace(/[^0-9]/g, '')}?text=${message}`, '_blank');
        } else {
            showAlert('error', 'Nomor WhatsApp tidak tersedia');
        }
    }

    function sendEmail(email) {
        if (email) {
            const subject = encodeURIComponent('Update Status Pesanan');
            const body = encodeURIComponent('Halo,\n\nPesanan Anda sedang diproses. Kami akan memberitahu Anda ketika pesanan siap dikirim.\n\nTerima kasih telah berbelanja di toko kami!');
            window.open(`mailto:${email}?subject=${subject}&body=${body}`);
        } else {
            showAlert('error', 'Email tidak tersedia');
        }
    }

    // Replace the existing exportOrders function and add exportOrdersPDF function

    function exportOrders() {
        // Get current filters
        const status = new URLSearchParams(window.location.search).get('status') || '';
        const search = new URLSearchParams(window.location.search).get('search') || '';
        const dateFrom = new URLSearchParams(window.location.search).get('date_from') || '';
        const dateTo = new URLSearchParams(window.location.search).get('date_to') || '';

        Swal.fire({
            title: 'Export Data Pesanan',
            text: 'Pilih format export yang diinginkan',
            icon: 'question',
            showCancelButton: true,
            showDenyButton: true,
            confirmButtonText: '<i class="fas fa-file-excel"></i> Excel',
            denyButtonText: '<i class="fas fa-file-csv"></i> CSV',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#28a745',
            denyButtonColor: '#17a2b8'
        }).then((result) => {
            if (result.isConfirmed) {
                // Export Excel
                exportData('excel', status, search, dateFrom, dateTo);
            } else if (result.isDenied) {
                // Export CSV
                exportData('csv', status, search, dateFrom, dateTo);
            }
        });
    }

    function exportOrdersPDF() {
        // Get current filters
        const status = new URLSearchParams(window.location.search).get('status') || '';
        const search = new URLSearchParams(window.location.search).get('search') || '';
        const dateFrom = new URLSearchParams(window.location.search).get('date_from') || '';
        const dateTo = new URLSearchParams(window.location.search).get('date_to') || '';

        Swal.fire({
            title: 'Export PDF',
            text: 'File HTML akan didownload yang bisa di-convert ke PDF',
            icon: 'info',
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-file-pdf"></i> Download HTML untuk PDF',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#dc3545'
        }).then((result) => {
            if (result.isConfirmed) {
                exportData('pdf', status, search, dateFrom, dateTo);
            }
        });
    }

    function exportData(format, status = '', search = '', dateFrom = '', dateTo = '') {
        // Show loading
        Swal.fire({
            title: 'Sedang memproses...',
            text: `Export ${format.toUpperCase()} sedang diproses`,
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            willOpen: () => {
                Swal.showLoading();
            }
        });

        // Build URL with filters
        let url = `export_orders.php?format=${format}`;
        if (status) url += `&status=${status}`;
        if (search) url += `&search=${encodeURIComponent(search)}`;
        if (dateFrom) url += `&date_from=${dateFrom}`;
        if (dateTo) url += `&date_to=${dateTo}`;

        // Create temporary link for download
        const link = document.createElement('a');
        link.href = url;
        link.download = '';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        // Close loading and show success
        setTimeout(() => {
            Swal.fire({
                title: 'Export Berhasil!',
                text: `File ${format.toUpperCase()} telah didownload`,
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            });
        }, 1000);
    }

    // Quick export functions for specific formats
    function quickExportExcel() {
        exportData('excel');
    }

    function quickExportCSV() {
        exportData('csv');
    }

    function quickExportPDF() {
        exportData('pdf');
    }

    // Update order status via AJAX
    $('.update-order-status').change(function() {
        const orderId = $(this).data('order-id');
        const newStatus = $(this).val();

        $.ajax({
            url: 'update_order_status.php',
            method: 'POST',
            data: {
                order_id: orderId,
                status: newStatus
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showAlert('success', 'Status pesanan berhasil diupdate');
                    location.reload();
                } else {
                    showAlert('error', response.message);
                }
            },
            error: function() {
                showAlert('error', 'Terjadi kesalahan saat mengupdate status');
            }
        });
    });
</script>

<?php include '../includes/footer.php'; ?>