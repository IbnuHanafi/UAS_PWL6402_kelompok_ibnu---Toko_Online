<?php

/**
 * Admin Dashboard
 * File: admin/dashboard.php
 * Toko Online - UAS Pemrograman Web Lanjut
 */

require_once '../includes/functions.php';

// Proteksi halaman admin
requireAdmin();

$page_title = 'Dashboard Admin';

global $db;

// Get statistics
$total_products = $db->fetch("SELECT COUNT(*) as total FROM products")['total'];
$total_orders = $db->fetch("SELECT COUNT(*) as total FROM orders")['total'];
$total_customers = $db->fetch("SELECT COUNT(*) as total FROM users WHERE role = 'customer'")['total'];
$total_categories = $db->fetch("SELECT COUNT(*) as total FROM categories")['total'];

// Get revenue statistics
$today_revenue = $db->fetch("
    SELECT COALESCE(SUM(total_amount), 0) as revenue 
    FROM orders 
    WHERE DATE(created_at) = CURDATE() AND status = 'completed'
")['revenue'];

$month_revenue = $db->fetch("
    SELECT COALESCE(SUM(total_amount), 0) as revenue 
    FROM orders 
    WHERE MONTH(created_at) = MONTH(CURDATE()) 
    AND YEAR(created_at) = YEAR(CURDATE()) 
    AND status = 'completed'
")['revenue'];

// Get pending orders
$pending_orders = $db->query("
    SELECT COUNT(*) as count 
    FROM orders 
    WHERE status = 'pending'
")['0']['count'];

// Get low stock products
$low_stock_products = $db->query("
    SELECT * FROM products 
    WHERE stock <= 5 AND status = 'active' 
    ORDER BY stock ASC 
    LIMIT 5
");

// Get recent orders
$recent_orders = $db->query("
    SELECT o.*, u.full_name 
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    ORDER BY o.created_at DESC 
    LIMIT 10
");

// ==========================================
// UPDATED CHART DATA - 30 HARI + DATE PICKER
// ==========================================

// Get date parameters from URL or default
$chart_date_from = $_GET['chart_from'] ?? date('Y-m-d', strtotime('-30 days'));
$chart_date_to = $_GET['chart_to'] ?? date('Y-m-d');

// Calculate number of days between dates
$date_from_obj = new DateTime($chart_date_from);
$date_to_obj = new DateTime($chart_date_to);
$interval = $date_from_obj->diff($date_to_obj);
$days_diff = $interval->days;

// Limit to maximum 90 days for performance
if ($days_diff > 90) {
    $days_diff = 90;
    $chart_date_from = date('Y-m-d', strtotime($chart_date_to . ' -90 days'));
}

// Get sales data for chart (custom date range)
$sales_data = [];
$labels = [];
$order_counts = [];

for ($i = 0; $i <= $days_diff; $i++) {
    $date = date('Y-m-d', strtotime($chart_date_from . " +$i days"));

    // Format label berdasarkan range tanggal
    if ($days_diff <= 7) {
        $labels[] = date('d M', strtotime($date)); // 15 Jan
    } elseif ($days_diff <= 30) {
        $labels[] = date('d/m', strtotime($date)); // 15/01
    } else {
        $labels[] = date('d/m', strtotime($date)); // 15/01
    }

    $daily_sales = $db->fetch("
        SELECT 
            COALESCE(SUM(total_amount), 0) as sales,
            COUNT(*) as order_count
        FROM orders 
        WHERE DATE(created_at) = ? AND status = 'completed'
    ", [$date]);

    $sales_data[] = (float)$daily_sales['sales'];
    $order_counts[] = (int)$daily_sales['order_count'];
}

// Get total for the selected period
$period_total = $db->fetch("
    SELECT 
        COALESCE(SUM(total_amount), 0) as total_sales,
        COUNT(*) as total_orders
    FROM orders 
    WHERE DATE(created_at) BETWEEN ? AND ? AND status = 'completed'
", [$chart_date_from, $chart_date_to]);

// Get category distribution for pie chart
$category_data = $db->query("
    SELECT c.name, COUNT(p.id) as product_count
    FROM categories c
    LEFT JOIN products p ON c.id = p.category_id
    GROUP BY c.id, c.name
    ORDER BY product_count DESC
");

include '../includes/header.php';
?>

<!-- CSS Animasi Berkedip + Chart Styling -->
<style>
    @keyframes blink {
        0% {
            opacity: 1;
            transform: scale(1);
        }

        50% {
            opacity: 0.7;
            transform: scale(1.05);
        }

        100% {
            opacity: 1;
            transform: scale(1);
        }
    }

    @keyframes pulse {
        0% {
            box-shadow: 0 4px 15px rgba(23, 162, 184, 0.3);
        }

        50% {
            box-shadow: 0 6px 25px rgba(23, 162, 184, 0.6);
        }

        100% {
            box-shadow: 0 4px 15px rgba(23, 162, 184, 0.3);
        }
    }

    .blinking-link {
        animation: blink 2s infinite;
        font-weight: bold !important;
        background: linear-gradient(45deg, #ffc107, #fd7e14) !important;
        padding: 8px 12px !important;
        border-radius: 20px !important;
        display: inline-block !important;
        transition: all 0.3s ease !important;
    }

    .blinking-link:hover {
        transform: translateY(-2px) !important;
        animation-duration: 1s !important;
    }

    .blinking-button {
        animation: blink 1.8s infinite, pulse 2s infinite !important;
        background: linear-gradient(45deg, #17a2b8, #20c997) !important;
        color: white !important;
        border: none !important;
        box-shadow: 0 4px 15px rgba(23, 162, 184, 0.3) !important;
        position: relative !important;
        overflow: hidden !important;
    }

    .blinking-button::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.3), transparent);
        transform: rotate(45deg);
        animation: shimmer 3s infinite;
    }

    @keyframes shimmer {
        0% {
            transform: translateX(-100%) translateY(-100%) rotate(45deg);
        }

        100% {
            transform: translateX(100%) translateY(100%) rotate(45deg);
        }
    }

    .blinking-button:hover {
        transform: translateY(-3px) !important;
        box-shadow: 0 8px 25px rgba(23, 162, 184, 0.5) !important;
        animation-duration: 1s !important;
    }

    .revenue-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        animation: glow 3s ease-in-out infinite alternate !important;
    }

    @keyframes glow {
        from {
            box-shadow: 0 4px 20px rgba(102, 126, 234, 0.3);
        }

        to {
            box-shadow: 0 8px 30px rgba(118, 75, 162, 0.4);
        }
    }

    /* Chart Styling */
    .chart-area {
        position: relative;
        height: 350px;
    }

    .chart-pie {
        position: relative;
        height: 250px;
    }

    #datePicker {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-top: 1px solid #dee2e6;
        transition: all 0.3s ease;
    }

    .btn-group .btn {
        font-size: 11px;
        padding: 4px 8px;
    }

    .border {
        border: 1px solid #dee2e6 !important;
    }

    .small {
        font-size: 0.875rem;
    }

    /* Chart container responsiveness */
    @media (max-width: 768px) {
        .chart-area {
            height: 250px;
        }

        .chart-pie {
            height: 200px;
        }
    }
</style>

<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 text-gradient fw-bold">Dashboard Admin</h1>
                    <p class="text-muted">Selamat datang kembali, <?php echo htmlspecialchars($current_user['full_name']); ?>!</p>
                </div>
                <div>
                    <span id="realtime-clock" class="badge bg-primary fs-6">
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-0 shadow-lg h-100">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col">
                            <div class="text-xs fw-bold text-primary text-uppercase mb-1">Total Produk</div>
                            <div class="h5 mb-0 fw-bold text-gray-800"><?php echo number_format($total_products); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-box fa-2x text-primary"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-primary text-white text-center py-2">
                    <a href="products.php" class="text-white text-decoration-none">
                        <small>Kelola Produk <i class="fas fa-arrow-right"></i></small>
                    </a>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-0 shadow-lg h-100">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col">
                            <div class="text-xs fw-bold text-success text-uppercase mb-1">Total Pesanan</div>
                            <div class="h5 mb-0 fw-bold text-gray-800"><?php echo number_format($total_orders); ?></div>
                            <?php if ($pending_orders > 0): ?>
                                <small class="text-warning">
                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                    <?php echo $pending_orders; ?> menunggu
                                </small>
                            <?php endif; ?>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-shopping-cart fa-2x text-success"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-success text-white text-center py-2">
                    <a href="orders.php" class="text-white text-decoration-none">
                        <small>Kelola Pesanan <i class="fas fa-arrow-right"></i></small>
                    </a>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-0 shadow-lg h-100">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col">
                            <div class="text-xs fw-bold text-info text-uppercase mb-1">Total Pelanggan</div>
                            <div class="h5 mb-0 fw-bold text-gray-800"><?php echo number_format($total_customers); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-info"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-info text-white text-center py-2">
                    <a href="customers.php" class="text-white text-decoration-none">
                        <small>Lihat Detail <i class="fas fa-arrow-right"></i></small>
                    </a>
                </div>
            </div>
        </div>

        <!-- Card Pendapatan dengan Efek Berkedip -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-0 shadow-lg h-100 revenue-card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col">
                            <div class="text-xs fw-bold text-warning text-uppercase mb-1">Pendapatan Bulan Ini</div>
                            <div class="h5 mb-0 fw-bold text-white"><?php echo formatRupiah($month_revenue); ?></div>
                            <small class="text-light">Hari ini: <?php echo formatRupiah($today_revenue); ?></small>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-dollar-sign fa-2x text-warning"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-warning text-dark text-center py-2">
                    <a href="reports.php" class="text-dark text-decoration-none blinking-link">
                        <small><i class="fas fa-chart-line me-1"></i>Laporan Lengkap <i class="fas fa-arrow-right ms-1"></i></small>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- UPDATED Charts Row dengan Date Picker -->
    <div class="row mb-4">
        <!-- Sales Chart dengan Date Picker -->
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 fw-bold text-white">
                        📈 Grafik Penjualan
                        <small class="text-light">(<?php echo date('d M Y', strtotime($chart_date_from)); ?> - <?php echo date('d M Y', strtotime($chart_date_to)); ?>)</small>
                    </h6>
                    <button class="btn btn-light btn-sm" onclick="toggleDatePicker()">
                        <i class="fas fa-calendar-alt me-1"></i>Pilih Tanggal
                    </button>
                </div>

                <!-- Date Picker Panel -->
                <div class="card-body border-bottom" id="datePicker" style="display: none;">
                    <form method="GET" id="chartDateForm" class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Dari Tanggal</label>
                            <input type="date" class="form-control form-control-sm" name="chart_from"
                                value="<?php echo $chart_date_from; ?>" max="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Sampai Tanggal</label>
                            <input type="date" class="form-control form-control-sm" name="chart_to"
                                value="<?php echo $chart_date_to; ?>" max="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="col-md-4">
                            <div class="btn-group w-100" role="group">
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="fas fa-chart-line me-1"></i>Update
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setQuickDate('7d')">7H</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setQuickDate('30d')">30H</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="setQuickDate('90d')">3B</button>
                            </div>
                        </div>
                    </form>

                    <!-- Quick Stats -->
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <div class="bg-primary text-white rounded p-2 text-center">
                                <small>Total Revenue</small>
                                <div class="fw-bold"><?php echo formatRupiah($period_total['total_sales']); ?></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="bg-success text-white rounded p-2 text-center">
                                <small>Total Orders</small>
                                <div class="fw-bold"><?php echo number_format($period_total['total_orders']); ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="salesChart" width="100%" height="50"></canvas>
                    </div>

                    <!-- Chart Summary -->
                    <div class="row mt-3">
                        <div class="col-md-4 text-center">
                            <div class="border rounded p-2">
                                <small class="text-muted">Periode</small>
                                <div class="fw-bold small"><?php echo $days_diff + 1; ?> Hari</div>
                            </div>
                        </div>
                        <div class="col-md-4 text-center">
                            <div class="border rounded p-2">
                                <small class="text-muted">Rata-rata/Hari</small>
                                <div class="fw-bold small"><?php echo formatRupiah($days_diff > 0 ? $period_total['total_sales'] / ($days_diff + 1) : 0); ?></div>
                            </div>
                        </div>
                        <div class="col-md-4 text-center">
                            <div class="border rounded p-2">
                                <small class="text-muted">Trend</small>
                                <div class="fw-bold small">
                                    <?php
                                    $recent_avg = array_sum(array_slice($sales_data, -7)) / 7;
                                    $earlier_avg = array_sum(array_slice($sales_data, 0, 7)) / 7;
                                    $trend = $recent_avg > $earlier_avg ? 'UP' : ($recent_avg < $earlier_avg ? 'DOWN' : 'FLAT');
                                    $trend_color = $trend == 'UP' ? 'text-success' : ($trend == 'DOWN' ? 'text-danger' : 'text-warning');
                                    $trend_icon = $trend == 'UP' ? 'fa-arrow-up' : ($trend == 'DOWN' ? 'fa-arrow-down' : 'fa-minus');
                                    ?>
                                    <span class="<?php echo $trend_color; ?>">
                                        <i class="fas <?php echo $trend_icon; ?> me-1"></i><?php echo $trend; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Category Distribution -->
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 fw-bold text-white">📊 Distribusi Kategori</h6>
                </div>
                <div class="card-body">
                    <div class="chart-pie pt-4 pb-2">
                        <canvas id="categoryChart" width="100%" height="50"></canvas>
                    </div>

                    <!-- Category Stats -->
                    <div class="mt-3">
                        <?php foreach (array_slice($category_data, 0, 3) as $index => $cat): ?>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="small"><?php echo htmlspecialchars($cat['name']); ?></span>
                                <span class="badge bg-primary"><?php echo $cat['product_count']; ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tables Row -->
    <div class="row">
        <!-- Recent Orders -->
        <div class="col-lg-8 mb-4">
            <div class="card shadow">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 fw-bold text-white">Pesanan Terbaru</h6>
                    <a href="orders.php" class="btn btn-primary btn-sm">Lihat Semua</a>
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
                                <?php if (empty($recent_orders)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">
                                            <i class="fas fa-shopping-cart fa-2x mb-2"></i>
                                            <br>Belum ada pesanan
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($recent_orders as $order): ?>
                                        <tr>
                                            <td>
                                                <span class="fw-bold"><?php echo htmlspecialchars($order['order_number']); ?></span>
                                            </td>
                                            <td><?php echo htmlspecialchars($order['full_name']); ?></td>
                                            <td class="fw-bold text-success"><?php echo formatRupiah($order['total_amount']); ?></td>
                                            <td><?php echo getStatusBadge($order['status']); ?></td>
                                            <td>
                                                <small><?php echo formatDate($order['created_at']); ?></small>
                                            </td>
                                            <td>
                                                <a href="orders.php?view=detail&id=<?php echo $order['id']; ?>"
                                                    class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Low Stock Alert -->
        <div class="col-lg-4 mb-4">
            <div class="card shadow">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 fw-bold text-white">Stok Menipis</h6>
                    <i class="fas fa-exclamation-triangle text-danger"></i>
                </div>
                <div class="card-body">
                    <?php if (empty($low_stock_products)): ?>
                        <div class="text-center text-muted py-3">
                            <i class="fas fa-check-circle fa-2x mb-2 text-success"></i>
                            <br>Semua produk stok aman
                        </div>
                    <?php else: ?>
                        <?php foreach ($low_stock_products as $product): ?>
                            <div class="d-flex align-items-center mb-3">
                                <div class="flex-shrink-0">
                                    <div class="bg-warning text-white rounded-circle d-flex align-items-center justify-content-center"
                                        style="width: 40px; height: 40px;">
                                        <i class="fas fa-box"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <div class="fw-bold text-truncate" style="max-width: 150px;">
                                        <?php echo htmlspecialchars($product['name']); ?>
                                    </div>
                                    <small class="text-danger">
                                        <i class="fas fa-exclamation-triangle me-1"></i>
                                        Sisa: <?php echo $product['stock']; ?> unit
                                    </small>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <div class="text-center mt-3">
                            <a href="products.php?filter=low_stock" class="btn btn-warning btn-sm">
                                Kelola Stok
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header">
                    <h6 class="m-0 fw-bold text-white">Aksi Cepat</h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-3 mb-3">
                            <a href="products.php" class="btn btn-outline-primary btn-lg w-100">
                                <i class="fas fa-plus-circle fa-2x mb-2"></i>
                                <br>Tambah Produk
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="categories.php?action=add" class="btn btn-outline-success btn-lg w-100">
                                <i class="fas fa-tags fa-2x mb-2"></i>
                                <br>Tambah Kategori
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="orders.php?status=pending" class="btn btn-outline-warning btn-lg w-100">
                                <i class="fas fa-clock fa-2x mb-2"></i>
                                <br>Pesanan Pending
                            </a>
                        </div>
                        <!-- Tombol Laporan Penjualan dengan Efek Berkedip -->
                        <div class="col-md-3 mb-3">
                            <a href="reports.php" class="btn btn-outline-info btn-lg w-100 blinking-button">
                                <i class="fas fa-chart-bar fa-2x mb-2"></i>
                                <br>Laporan Penjualan
                                <small class="d-block mt-1">📊 Click Here!</small>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    //=============================================
    // UPDATED Chart Data dan Functions
    //=============================================

    // Chart data dengan data yang sudah diupdate
    var salesData = {
        labels: <?php echo json_encode($labels); ?>,
        salesData: <?php echo json_encode($sales_data); ?>,
        orderData: <?php echo json_encode($order_counts); ?>
    };

    var categoryData = {
        labels: <?php echo json_encode(array_column($category_data, 'name')); ?>,
        data: <?php echo json_encode(array_column($category_data, 'product_count')); ?>
    };

    // Toggle date picker
    function toggleDatePicker() {
        const datePicker = document.getElementById('datePicker');
        if (datePicker.style.display === 'none') {
            datePicker.style.display = 'block';
            datePicker.scrollIntoView({
                behavior: 'smooth'
            });
        } else {
            datePicker.style.display = 'none';
        }
    }

    // Quick date selection
    function setQuickDate(period) {
        const today = new Date();
        const fromInput = document.querySelector('input[name="chart_from"]');
        const toInput = document.querySelector('input[name="chart_to"]');

        toInput.value = today.toISOString().split('T')[0];

        let fromDate = new Date();
        switch (period) {
            case '7d':
                fromDate.setDate(today.getDate() - 7);
                break;
            case '30d':
                fromDate.setDate(today.getDate() - 30);
                break;
            case '90d':
                fromDate.setDate(today.getDate() - 90);
                break;
        }

        fromInput.value = fromDate.toISOString().split('T')[0];

        // Auto submit
        document.getElementById('chartDateForm').submit();
    }

    var isAdmin = true;

    // Generate Report Function
    function generateReport() {
        window.location.href = 'reports.php';
    }

    //=============================================
    // Jam Real-Time (tetap sama)
    //=============================================
    function updateClock() {
        const now = new Date();
        const dateOptions = {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        };
        const timeOptions = {
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: false
        };

        const formattedDate = now.toLocaleDateString('id-ID', dateOptions);
        const formattedTime = now.toLocaleTimeString('id-ID', timeOptions).replace(/\./g, ':');
        const fullDateTimeString = `${formattedDate}, ${formattedTime}`;

        const clockElement = document.getElementById('realtime-clock');
        if (clockElement) {
            clockElement.innerHTML = `<i class="fas fa-clock me-1"></i> ${fullDateTimeString}`;
        }
    }

    setInterval(updateClock, 1000);
    updateClock();

    //=============================================
    // Chart Initialization
    //=============================================
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize sales chart
        const ctx = document.getElementById('salesChart');
        if (ctx) {
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: salesData.labels,
                    datasets: [{
                        label: 'Revenue (Rp)',
                        data: salesData.salesData,
                        borderColor: 'rgb(75, 192, 192)',
                        backgroundColor: 'rgba(75, 192, 192, 0.1)',
                        tension: 0.1,
                        fill: true,
                        yAxisID: 'y'
                    }, {
                        label: 'Orders',
                        data: salesData.orderData,
                        borderColor: 'rgb(255, 99, 132)',
                        backgroundColor: 'rgba(255, 99, 132, 0.1)',
                        tension: 0.1,
                        yAxisID: 'y1'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.datasetIndex === 0) {
                                        // Format currency for revenue
                                        label += 'Rp ' + new Intl.NumberFormat('id-ID').format(context.parsed.y);
                                    } else {
                                        // Format number for orders
                                        label += context.parsed.y + ' orders';
                                    }
                                    return label;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            display: true,
                            title: {
                                display: true,
                                text: 'Tanggal'
                            }
                        },
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            title: {
                                display: true,
                                text: 'Revenue (Rp)'
                            },
                            ticks: {
                                callback: function(value) {
                                    return 'Rp ' + new Intl.NumberFormat('id-ID').format(value);
                                }
                            }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            title: {
                                display: true,
                                text: 'Orders'
                            },
                            grid: {
                                drawOnChartArea: false,
                            },
                        }
                    }
                }
            });
        }

        // Initialize category chart
        const ctxCat = document.getElementById('categoryChart');
        if (ctxCat && categoryData.data.length > 0) {
            new Chart(ctxCat, {
                type: 'doughnut',
                data: {
                    labels: categoryData.labels,
                    datasets: [{
                        data: categoryData.data,
                        backgroundColor: [
                            '#FF6384',
                            '#36A2EB',
                            '#FFCE56',
                            '#4BC0C0',
                            '#9966FF',
                            '#FF9F40'
                        ],
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.label + ': ' + context.parsed + ' produk';
                                }
                            }
                        }
                    }
                }
            });
        }

        //=============================================
        // Animation dan Notification
        //=============================================

        // Add extra attention to report buttons
        setTimeout(() => {
            const reportButtons = document.querySelectorAll('.blinking-button, .blinking-link');
            reportButtons.forEach(btn => {
                btn.style.animationDuration = '1.5s';
            });
        }, 3000);

        // Show notification about new report feature
        if (localStorage.getItem('reportNotificationShown') !== 'true') {
            setTimeout(() => {
                Swal.fire({
                    title: '📊 Fitur Baru!',
                    html: `
                        <div style="text-align: left;">
                            <p>🎉 <strong>Laporan Penjualan</strong> telah tersedia!</p>
                            <ul style="margin: 10px 0;">
                                <li>📈 Grafik penjualan interaktif</li>
                                <li>📊 Statistik lengkap</li>
                                <li>📄 Export PDF & Excel</li>
                                <li>🔍 Filter periode custom</li>
                            </ul>
                            <p>Klik tombol yang berkedip untuk mengakses! 💫</p>
                        </div>
                    `,
                    icon: 'info',
                    confirmButtonText: 'Siap! 🚀',
                    confirmButtonColor: '#17a2b8',
                    timer: 8000,
                    timerProgressBar: true
                });
                localStorage.setItem('reportNotificationShown', 'true');
            }, 2000);
        }
    });
</script>

<?php include '../includes/footer.php'; ?>