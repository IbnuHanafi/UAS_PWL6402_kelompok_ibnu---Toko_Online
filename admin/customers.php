<?php

/**
 * Admin Customers Management
 * File: admin/customers.php
 * Toko Online - UAS Pemrograman Web Lanjut
 */

require_once '../vendor/autoload.php';
require_once '../includes/functions.php';

// Proteksi halaman admin
requireAdmin();

$page_title = 'Kelola Pelanggan';

global $db;

// Get view mode
$view = $_GET['view'] ?? 'list';
$customer_id = $_GET['id'] ?? null;

if ($view == 'detail' && $customer_id) {
    // Get customer detail
    $customer = $db->fetch("
        SELECT * FROM users 
        WHERE id = ? AND role = 'customer'
    ", [$customer_id]);

    if (!$customer) {
        redirect('customers.php');
    }

    // Get customer orders
    $customer_orders = $db->query("
        SELECT * FROM orders 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 10
    ", [$customer_id]);

    // Get customer statistics
    $customer_stats = [
        'total_orders' => $db->fetch("SELECT COUNT(*) as count FROM orders WHERE user_id = ?", [$customer_id])['count'],
        'total_spent' => $db->fetch("SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE user_id = ? AND status = 'completed'", [$customer_id])['total'],
        'last_order' => $db->fetch("SELECT created_at FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 1", [$customer_id])['created_at'] ?? null
    ];
} else {
    // Get filters
    $search = $_GET['search'] ?? '';
    $status_filter = $_GET['status'] ?? '';
    $date_from = $_GET['date_from'] ?? '';
    $date_to = $_GET['date_to'] ?? '';

    // Pagination
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 15;
    $offset = ($page - 1) * $limit;

    // Build where conditions
    $where_conditions = ["role = 'customer'"];
    $params = [];

    if (!empty($search)) {
        $where_conditions[] = "(full_name LIKE ? OR email LIKE ? OR phone LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    if (!empty($date_from)) {
        $where_conditions[] = "DATE(created_at) >= ?";
        $params[] = $date_from;
    }

    if (!empty($date_to)) {
        $where_conditions[] = "DATE(created_at) <= ?";
        $params[] = $date_to;
    }

    $where_sql = "WHERE " . implode(" AND ", $where_conditions);

    // Get total count
    $count_sql = "SELECT COUNT(*) as total FROM users $where_sql";
    $total_customers = $db->fetch($count_sql, $params)['total'];
    $total_pages = ceil($total_customers / $limit);

    // Get customers with order statistics
    $customers_sql = "
        SELECT u.*, 
               COUNT(o.id) as total_orders,
               COALESCE(SUM(CASE WHEN o.status = 'completed' THEN o.total_amount ELSE 0 END), 0) as total_spent,
               MAX(o.created_at) as last_order_date
        FROM users u
        LEFT JOIN orders o ON u.id = o.user_id
        $where_sql
        GROUP BY u.id
        ORDER BY u.created_at DESC
        LIMIT $limit OFFSET $offset
    ";
    $customers = $db->query($customers_sql, $params);

    // Get overall statistics
    $stats = [
        'total' => $db->fetch("SELECT COUNT(*) as count FROM users WHERE role = 'customer'")['count'],
        'new_today' => $db->fetch("SELECT COUNT(*) as count FROM users WHERE role = 'customer' AND DATE(created_at) = CURDATE()")['count'],
        'new_week' => $db->fetch("SELECT COUNT(*) as count FROM users WHERE role = 'customer' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")['count'],
        'with_orders' => $db->fetch("SELECT COUNT(DISTINCT user_id) as count FROM orders")['count']
    ];

    $total_revenue_from_customers = $db->fetch("
        SELECT COALESCE(SUM(total_amount), 0) as revenue 
        FROM orders 
        WHERE status = 'completed'
    ")['revenue'];
}

include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <?php if ($view == 'detail'): ?>
        <!-- Customer Detail View -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="h3 text-gradient fw-bold">Detail Pelanggan</h1>
                        <p class="text-muted"><?php echo htmlspecialchars($customer['full_name']); ?></p>
                    </div>
                    <div>
                        <a href="customers.php" class="btn btn-secondary">
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
            <!-- Customer Information -->
            <div class="col-lg-4 mb-4">
                <div class="card shadow">
                    <div class="card-header">
                        <h6 class="m-0 fw-bold text-white">Informasi Pelanggan</h6>
                    </div>
                    <div class="card-body text-center">
                        <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                            style="width: 80px; height: 80px;">
                            <i class="fas fa-user fa-3x"></i>
                        </div>

                        <h5 class="fw-bold"><?php echo htmlspecialchars($customer['full_name']); ?></h5>

                        <div class="text-start mt-4">
                            <div class="mb-3">
                                <strong><i class="fas fa-envelope text-primary me-2"></i>Email:</strong><br>
                                <span class="text-muted"><?php echo htmlspecialchars($customer['email']); ?></span>
                            </div>

                            <?php if ($customer['phone']): ?>
                                <div class="mb-3">
                                    <strong><i class="fas fa-phone text-success me-2"></i>Telepon:</strong><br>
                                    <span class="text-muted"><?php echo htmlspecialchars($customer['phone']); ?></span>
                                </div>
                            <?php endif; ?>

                            <?php if ($customer['address']): ?>
                                <div class="mb-3">
                                    <strong><i class="fas fa-map-marker-alt text-danger me-2"></i>Alamat:</strong><br>
                                    <span class="text-muted"><?php echo nl2br(htmlspecialchars($customer['address'])); ?></span>
                                </div>
                            <?php endif; ?>

                            <div class="mb-3">
                                <strong><i class="fas fa-calendar text-info me-2"></i>Bergabung:</strong><br>
                                <span class="text-muted"><?php echo formatDate($customer['created_at']); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Customer Statistics -->
                <div class="card shadow mt-4">
                    <div class="card-header">
                        <h6 class="m-0 fw-bold text-white">Statistik Pelanggan</h6>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-12 mb-3">
                                <div class="h4 text-primary mb-0"><?php echo $customer_stats['total_orders']; ?></div>
                                <small class="text-muted">Total Pesanan</small>
                            </div>
                            <div class="col-12 mb-3">
                                <div class="h5 text-success mb-0"><?php echo formatRupiah($customer_stats['total_spent']); ?></div>
                                <small class="text-muted">Total Pembelian</small>
                            </div>
                            <div class="col-12">
                                <div class="text-muted">
                                    <strong>Pesanan Terakhir:</strong><br>
                                    <?php echo $customer_stats['last_order'] ? formatDate($customer_stats['last_order']) : 'Belum ada pesanan'; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card shadow mt-4">
                    <div class="card-header">
                        <h6 class="m-0 fw-bold text-white">Aksi Cepat</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <?php if ($customer['phone']): ?>
                                <button class="btn btn-outline-success" onclick="sendWhatsApp('<?php echo $customer['phone']; ?>')">
                                    <i class="fab fa-whatsapp me-2"></i>Kirim WhatsApp
                                </button>
                            <?php endif; ?>
                            <button class="btn btn-outline-info" onclick="sendEmail('<?php echo $customer['email']; ?>')">
                                <i class="fas fa-envelope me-2"></i>Kirim Email
                            </button>
                            <a href="orders.php?search=<?php echo urlencode($customer['email']); ?>" class="btn btn-outline-primary">
                                <i class="fas fa-shopping-cart me-2"></i>Lihat Pesanan
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Orders -->
            <div class="col-lg-8 mb-4">
                <div class="card shadow">
                    <div class="card-header">
                        <h6 class="m-0 fw-bold text-white">Pesanan Terbaru</h6>
                    </div>
                    <div class="card-body">
                        <?php if (empty($customer_orders)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                                <h6 class="text-muted">Belum ada pesanan</h6>
                                <p class="text-muted">Pelanggan ini belum melakukan pemesanan</p>
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
                                        <?php foreach ($customer_orders as $order): ?>
                                            <tr>
                                                <td><span class="fw-bold"><?php echo htmlspecialchars($order['order_number']); ?></span></td>
                                                <td class="fw-bold text-success"><?php echo formatRupiah($order['total_amount']); ?></td>
                                                <td><?php echo getStatusBadge($order['status']); ?></td>
                                                <td><small><?php echo formatDate($order['created_at']); ?></small></td>
                                                <td>
                                                    <a href="orders.php?view=detail&id=<?php echo $order['id']; ?>"
                                                        class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <?php if (count($customer_orders) >= 10): ?>
                                <div class="text-center mt-3">
                                    <a href="orders.php?search=<?php echo urlencode($customer['email']); ?>"
                                        class="btn btn-outline-primary">
                                        Lihat Semua Pesanan
                                    </a>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

    <?php else: ?>
        <!-- Customers List View -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="h3 text-gradient fw-bold">Kelola Pelanggan</h1>
                        <p class="text-muted">Manajemen data pelanggan toko online</p>
                    </div>
                    <div>
                        <button class="btn btn-success" onclick="exportCustomers()">
                            <i class="fas fa-download me-2"></i>Export Data
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card bg-primary text-white shadow">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col">
                                <div class="text-xs fw-bold text-uppercase mb-1">Total Pelanggan</div>
                                <div class="h4 mb-0"><?php echo number_format($stats['total']); ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-users fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card bg-success text-white shadow">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col">
                                <div class="text-xs fw-bold text-uppercase mb-1">Baru Hari Ini</div>
                                <div class="h4 mb-0"><?php echo number_format($stats['new_today']); ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-user-plus fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card bg-info text-white shadow">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col">
                                <div class="text-xs fw-bold text-uppercase mb-1">Baru Minggu Ini</div>
                                <div class="h4 mb-0"><?php echo number_format($stats['new_week']); ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-calendar-week fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card bg-warning text-white shadow">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col">
                                <div class="text-xs fw-bold text-uppercase mb-1">Ada Pesanan</div>
                                <div class="h4 mb-0"><?php echo number_format($stats['with_orders']); ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-shopping-cart fa-2x"></i>
                            </div>
                        </div>
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
                            <div class="col-md-4">
                                <label class="form-label">Cari Pelanggan</label>
                                <input type="text" class="form-control" name="search" placeholder="Nama, email, atau telepon..."
                                    value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Dari Tanggal</label>
                                <input type="date" class="form-control" name="date_from" value="<?php echo $date_from; ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Sampai Tanggal</label>
                                <input type="date" class="form-control" name="date_to" value="<?php echo $date_to; ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search"></i> Cari
                                    </button>
                                    <a href="customers.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-undo"></i> Reset
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Customers Table -->
        <div class="row">
            <div class="col-12">
                <div class="card shadow">
                    <div class="card-header">
                        <h6 class="m-0 fw-bold text-white">
                            Daftar Pelanggan (<?php echo number_format($total_customers); ?> pelanggan)
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Nama Lengkap</th>
                                        <th>Email</th>
                                        <th>Telepon</th>
                                        <th>Total Pesanan</th>
                                        <th>Total Pembelian</th>
                                        <th>Bergabung</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($customers)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-4">
                                                <i class="fas fa-users fa-2x mb-2"></i>
                                                <br>Tidak ada pelanggan ditemukan
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($customers as $customer): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3"
                                                            style="width: 40px; height: 40px;">
                                                            <i class="fas fa-user"></i>
                                                        </div>
                                                        <div>
                                                            <div class="fw-bold"><?php echo htmlspecialchars($customer['full_name']); ?></div>
                                                            <?php if ($customer['last_order_date']): ?>
                                                                <small class="text-success">
                                                                    <i class="fas fa-circle fa-xs"></i> Aktif
                                                                </small>
                                                            <?php else: ?>
                                                                <small class="text-muted">
                                                                    <i class="fas fa-circle fa-xs"></i> Belum berbelanja
                                                                </small>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div><?php echo htmlspecialchars($customer['email']); ?></div>
                                                </td>
                                                <td>
                                                    <?php echo $customer['phone'] ? htmlspecialchars($customer['phone']) : '<span class="text-muted">-</span>'; ?>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-info"><?php echo $customer['total_orders']; ?></span>
                                                </td>
                                                <td class="fw-bold text-success">
                                                    <?php echo formatRupiah($customer['total_spent']); ?>
                                                </td>
                                                <td>
                                                    <small><?php echo formatDate($customer['created_at']); ?></small>
                                                </td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <a href="?view=detail&id=<?php echo $customer['id']; ?>"
                                                            class="btn btn-sm btn-outline-primary">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <?php if ($customer['phone']): ?>
                                                            <button class="btn btn-sm btn-outline-success"
                                                                onclick="sendWhatsApp('<?php echo $customer['phone']; ?>')">
                                                                <i class="fab fa-whatsapp"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                        <button class="btn btn-sm btn-outline-info"
                                                            onclick="sendEmail('<?php echo $customer['email']; ?>')">
                                                            <i class="fas fa-envelope"></i>
                                                        </button>
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
                                <?php echo getPagination($page, $total_pages, 'customers.php'); ?>
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
            const message = encodeURIComponent('Halo! Terima kasih telah menjadi pelanggan setia toko kami. Ada yang bisa kami bantu?');
            window.open(`https://wa.me/${phone.replace(/[^0-9]/g, '')}?text=${message}`, '_blank');
        } else {
            showAlert('error', 'Nomor WhatsApp tidak tersedia');
        }
    }

    function sendEmail(email) {
        if (email) {
            const subject = encodeURIComponent('Informasi dari Toko Online');
            const body = encodeURIComponent('Halo,\n\nTerima kasih telah menjadi pelanggan setia kami.\n\nSalam,\nTim Toko Online');
            window.open(`mailto:${email}?subject=${subject}&body=${body}`);
        } else {
            showAlert('error', 'Email tidak tersedia');
        }
    }

    // export
    function exportCustomers() {
        Swal.fire({
            title: 'Export Data Pelanggan',
            input: 'select',
            inputOptions: {
                'excel': 'Excel (.xlsx)',
                'pdf': 'PDF (.pdf)',
                'csv': 'CSV (.csv)'
            },
            inputPlaceholder: 'Pilih format file...',
            showCancelButton: true,
            confirmButtonText: 'Export Sekarang <i class="fas fa-download ms-2"></i>',
            cancelButtonText: 'Batal',
            inputValidator: (value) => {
                if (!value) {
                    return 'Anda harus memilih format terlebih dahulu!'
                }
            }
        }).then((result) => {
            if (result.isConfirmed && result.value) {
                exportData(result.value);
            }
        });
    }

    function exportData(format) {
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

        // Get current filters
        const search = new URLSearchParams(window.location.search).get('search') || '';
        const dateFrom = new URLSearchParams(window.location.search).get('date_from') || '';
        const dateTo = new URLSearchParams(window.location.search).get('date_to') || '';

        // Build URL with filters
        let url = `export_customers.php?format=${format}`;
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
</script>

<?php include '../includes/footer.php'; ?>