<?php

/**
 * Admin Sales Reports
 * File: admin/reports.php
 * Toko Online - UAS Pemrograman Web Lanjut
 */

require_once '../includes/functions.php';

// Proteksi halaman admin
requireAdmin();

$page_title = 'Laporan Penjualan';

global $db;

// Get filter parameters
$date_from = $_GET['date_from'] ?? date('Y-m-01'); // Default: awal bulan ini
$date_to = $_GET['date_to'] ?? date('Y-m-d'); // Default: hari ini
$status_filter = $_GET['status'] ?? '';
$period = $_GET['period'] ?? 'this_month';

// Set date range based on period
switch ($period) {
    case 'today':
        $date_from = $date_to = date('Y-m-d');
        break;
    case 'yesterday':
        $date_from = $date_to = date('Y-m-d', strtotime('-1 day'));
        break;
    case 'this_week':
        $date_from = date('Y-m-d', strtotime('monday this week'));
        $date_to = date('Y-m-d');
        break;
    case 'last_week':
        $date_from = date('Y-m-d', strtotime('monday last week'));
        $date_to = date('Y-m-d', strtotime('sunday last week'));
        break;
    case 'this_month':
        $date_from = date('Y-m-01');
        $date_to = date('Y-m-d');
        break;
    case 'last_month':
        $date_from = date('Y-m-01', strtotime('first day of last month'));
        $date_to = date('Y-m-t', strtotime('last day of last month'));
        break;
    case 'this_year':
        $date_from = date('Y-01-01');
        $date_to = date('Y-m-d');
        break;
    case 'custom':
        // Keep the submitted values
        break;
}

// Build where conditions for orders
$where_conditions = ["DATE(o.created_at) BETWEEN ? AND ?"];
$params = [$date_from, $date_to];

if (!empty($status_filter)) {
    $where_conditions[] = "o.status = ?";
    $params[] = $status_filter;
}

$where_sql = "WHERE " . implode(" AND ", $where_conditions);

// Get sales overview
$sales_overview = $db->fetch("
    SELECT 
        COUNT(*) as total_orders,
        COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_orders,
        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_orders,
        COUNT(CASE WHEN status = 'processing' THEN 1 END) as processing_orders,
        COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_orders,
        COALESCE(SUM(CASE WHEN status = 'completed' THEN total_amount ELSE 0 END), 0) as total_revenue,
        COALESCE(SUM(total_amount), 0) as gross_sales,
        COALESCE(AVG(CASE WHEN status = 'completed' THEN total_amount END), 0) as avg_order_value
    FROM orders o
    $where_sql
", $params);

// Get daily sales for chart
$daily_sales = $db->query("
    SELECT 
        DATE(created_at) as sale_date,
        COUNT(*) as order_count,
        COALESCE(SUM(CASE WHEN status = 'completed' THEN total_amount ELSE 0 END), 0) as daily_revenue
    FROM orders o
    $where_sql
    GROUP BY DATE(created_at)
    ORDER BY sale_date ASC
", $params);

// Get top products
$top_products = $db->query("
    SELECT 
        p.name as product_name,
        SUM(oi.quantity) as total_quantity,
        SUM(oi.subtotal) as total_sales,
        COUNT(DISTINCT o.id) as order_count
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.id
    JOIN products p ON oi.product_id = p.id
    $where_sql
    AND o.status = 'completed'
    GROUP BY p.id, p.name
    ORDER BY total_sales DESC
    LIMIT 10
", $params);

// Get detailed orders for table
$detailed_orders = $db->query("
    SELECT o.*, u.full_name, u.email
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    $where_sql
    ORDER BY o.created_at DESC
    LIMIT 50
", $params);

// Calculate conversion rate
$total_customers = $db->fetch("SELECT COUNT(DISTINCT user_id) as count FROM orders o $where_sql", $params)['count'];
$conversion_rate = $total_customers > 0 ? ($sales_overview['completed_orders'] / $total_customers) * 100 : 0;

include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 text-gradient fw-bold"> Laporan Penjualan</h1>
                    <p class="text-muted">
                        Periode: <?php echo date('d M Y', strtotime($date_from)); ?> - <?php echo date('d M Y', strtotime($date_to)); ?>
                    </p>
                </div>
                <div>
                    <button class="btn btn-danger" onclick="exportReportPDF()">
                        <i class="fas fa-file-pdf me-2"></i>Export PDF
                    </button>
                    <button class="btn btn-success ms-2" onclick="exportReportExcel()">
                        <i class="fas fa-file-excel me-2"></i>Export Excel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header">
                    <h6 class="m-0 fw-bold text-white">Filter Laporan</h6>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Periode</label>
                            <select class="form-select" name="period" id="periodSelect" onchange="toggleCustomDates()">
                                <option value="today" <?php echo $period == 'today' ? 'selected' : ''; ?>>Hari Ini</option>
                                <option value="yesterday" <?php echo $period == 'yesterday' ? 'selected' : ''; ?>>Kemarin</option>
                                <option value="this_week" <?php echo $period == 'this_week' ? 'selected' : ''; ?>>Minggu Ini</option>
                                <option value="last_week" <?php echo $period == 'last_week' ? 'selected' : ''; ?>>Minggu Lalu</option>
                                <option value="this_month" <?php echo $period == 'this_month' ? 'selected' : ''; ?>>Bulan Ini</option>
                                <option value="last_month" <?php echo $period == 'last_month' ? 'selected' : ''; ?>>Bulan Lalu</option>
                                <option value="this_year" <?php echo $period == 'this_year' ? 'selected' : ''; ?>>Tahun Ini</option>
                                <option value="custom" <?php echo $period == 'custom' ? 'selected' : ''; ?>>Custom</option>
                            </select>
                        </div>
                        <div class="col-md-2" id="dateFromDiv">
                            <label class="form-label">Dari Tanggal</label>
                            <input type="date" class="form-control" name="date_from" value="<?php echo $date_from; ?>">
                        </div>
                        <div class="col-md-2" id="dateToDiv">
                            <label class="form-label">Sampai Tanggal</label>
                            <input type="date" class="form-control" name="date_to" value="<?php echo $date_to; ?>">
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
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid gap-2 d-md-flex">
                                <button type="submit" class="btn btn-primary flex-fill">
                                    <i class="fas fa-search"></i> Terapkan Filter
                                </button>
                                <a href="reports.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-undo"></i> Reset
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Overview Statistics -->
    <div class="row mb-4">
        <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
            <div class="card bg-primary text-white shadow h-100">
                <div class="card-body text-center">
                    <div class="h4 mb-0"><?php echo number_format($sales_overview['total_orders']); ?></div>
                    <small>Total Pesanan</small>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
            <div class="card bg-success text-white shadow h-100">
                <div class="card-body text-center">
                    <div class="h4 mb-0"><?php echo number_format($sales_overview['completed_orders']); ?></div>
                    <small>Completed</small>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
            <div class="card bg-warning text-white shadow h-100">
                <div class="card-body text-center">
                    <div class="h4 mb-0"><?php echo number_format($sales_overview['pending_orders']); ?></div>
                    <small>Pending</small>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
            <div class="card bg-info text-white shadow h-100">
                <div class="card-body text-center">
                    <div class="h4 mb-0"><?php echo number_format($sales_overview['processing_orders']); ?></div>
                    <small>Processing</small>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
            <div class="card bg-danger text-white shadow h-100">
                <div class="card-body text-center">
                    <div class="h4 mb-0"><?php echo number_format($sales_overview['cancelled_orders']); ?></div>
                    <small>Cancelled</small>
                </div>
            </div>
        </div>
        <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
            <div class="card bg-secondary text-white shadow h-100">
                <div class="card-body text-center">
                    <div class="h6 mb-0"><?php echo number_format($total_customers); ?></div>
                    <small>Unique Customers</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Revenue Cards -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-0 shadow-lg bg-gradient-success text-white">
                <div class="card-body text-center">
                    <i class="fas fa-money-bill-wave fa-3x mb-3"></i>
                    <h5>Total Revenue</h5>
                    <h3 class="fw-bold"><?php echo formatRupiah($sales_overview['total_revenue']); ?></h3>
                    <small>Completed Orders Only</small>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-0 shadow-lg bg-gradient-info text-white">
                <div class="card-body text-center">
                    <i class="fas fa-chart-line fa-3x mb-3"></i>
                    <h5>Gross Sales</h5>
                    <h3 class="fw-bold"><?php echo formatRupiah($sales_overview['gross_sales']); ?></h3>
                    <small>All Orders</small>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-0 shadow-lg bg-gradient-warning text-white">
                <div class="card-body text-center">
                    <i class="fas fa-shopping-cart fa-3x mb-3"></i>
                    <h5>Average Order</h5>
                    <h3 class="fw-bold"><?php echo formatRupiah($sales_overview['avg_order_value']); ?></h3>
                    <small>Per Completed Order</small>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-0 shadow-lg bg-gradient-primary text-white">
                <div class="card-body text-center">
                    <i class="fas fa-percentage fa-3x mb-3"></i>
                    <h5>Conversion Rate</h5>
                    <h3 class="fw-bold"><?php echo number_format($conversion_rate, 1); ?>%</h3>
                    <small>Completed vs Total</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts -->
    <div class="row mb-4">
        <!-- Daily Sales Chart -->
        <div class="col-lg-8 mb-4">
            <div class="card shadow">
                <div class="card-header">
                    <h6 class="m-0 fw-bold text-white">Grafik Penjualan Harian</h6>
                </div>
                <div class="card-body">
                    <canvas id="dailySalesChart" width="100%" height="50"></canvas>
                </div>
            </div>
        </div>

        <!-- Top Products -->
        <div class="col-lg-4 mb-4">
            <div class="card shadow">
                <div class="card-header">
                    <h6 class="m-0 fw-bold text-white">Top 10 Produk</h6>
                </div>
                <div class="card-body">
                    <?php if (empty($top_products)): ?>
                        <div class="text-center text-muted py-3">
                            <i class="fas fa-box fa-2x mb-2"></i>
                            <br>Belum ada data produk
                        </div>
                    <?php else: ?>
                        <?php foreach ($top_products as $index => $product): ?>
                            <div class="d-flex align-items-center mb-3">
                                <div class="flex-shrink-0">
                                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center"
                                        style="width: 30px; height: 30px; font-size: 12px;">
                                        <?php echo $index + 1; ?>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <div class="fw-bold text-truncate" style="max-width: 150px;">
                                        <?php echo htmlspecialchars($product['product_name']); ?>
                                    </div>
                                    <small class="text-muted">
                                        Qty: <?php echo $product['total_quantity']; ?> |
                                        Sales: <?php echo formatRupiah($product['total_sales']); ?>
                                    </small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Orders Table -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header">
                    <h6 class="m-0 fw-bold text-white">Detail Transaksi (50 Terbaru)</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>No. Pesanan</th>
                                    <th>Tanggal</th>
                                    <th>Pelanggan</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($detailed_orders)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">
                                            <i class="fas fa-shopping-cart fa-2x mb-2"></i>
                                            <br>Tidak ada data transaksi dalam periode ini
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($detailed_orders as $order): ?>
                                        <tr>
                                            <td><span class="fw-bold"><?php echo htmlspecialchars($order['order_number']); ?></span></td>
                                            <td><small><?php echo formatDate($order['created_at']); ?></small></td>
                                            <td>
                                                <div><?php echo htmlspecialchars($order['full_name']); ?></div>
                                                <small class="text-muted"><?php echo htmlspecialchars($order['email']); ?></small>
                                            </td>
                                            <td class="fw-bold text-success"><?php echo formatRupiah($order['total_amount']); ?></td>
                                            <td><?php echo getStatusBadge($order['status']); ?></td>
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
    </div>
</div>

<script>
    // Toggle custom date inputs
    function toggleCustomDates() {
        const periodSelect = document.getElementById('periodSelect');
        const dateFromDiv = document.getElementById('dateFromDiv');
        const dateToDiv = document.getElementById('dateToDiv');

        if (periodSelect.value === 'custom') {
            dateFromDiv.style.display = 'block';
            dateToDiv.style.display = 'block';
        } else {
            dateFromDiv.style.display = 'none';
            dateToDiv.style.display = 'none';
        }
    }

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        toggleCustomDates();
    });

    // Chart for daily sales
    const dailySalesData = {
        labels: <?php echo json_encode(array_column($daily_sales, 'sale_date')); ?>,
        datasets: [{
            label: 'Revenue',
            data: <?php echo json_encode(array_column($daily_sales, 'daily_revenue')); ?>,
            borderColor: 'rgb(75, 192, 192)',
            backgroundColor: 'rgba(75, 192, 192, 0.1)',
            tension: 0.1
        }, {
            label: 'Orders',
            data: <?php echo json_encode(array_column($daily_sales, 'order_count')); ?>,
            borderColor: 'rgb(255, 99, 132)',
            backgroundColor: 'rgba(255, 99, 132, 0.1)',
            yAxisID: 'y1',
            tension: 0.1
        }]
    };

    // Export functions
    function exportReportPDF() {
        const currentUrl = new URL(window.location);
        currentUrl.pathname = currentUrl.pathname.replace('reports.php', 'export_report.php');
        currentUrl.searchParams.set('format', 'pdf');

        Swal.fire({
            title: 'Export Laporan PDF',
            text: 'Laporan akan didownload dalam format PDF',
            icon: 'info',
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-file-pdf"></i> Download PDF',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#dc3545'
        }).then((result) => {
            if (result.isConfirmed) {
                window.open(currentUrl.toString(), '_blank');
            }
        });
    }

    function exportReportExcel() {
        const currentUrl = new URL(window.location);
        currentUrl.pathname = currentUrl.pathname.replace('reports.php', 'export_report.php');
        currentUrl.searchParams.set('format', 'excel');

        Swal.fire({
            title: 'Export Laporan Excel',
            text: 'Laporan akan didownload dalam format Excel',
            icon: 'info',
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-file-excel"></i> Download Excel',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#28a745'
        }).then((result) => {
            if (result.isConfirmed) {
                window.open(currentUrl.toString(), '_blank');
            }
        });
    }
</script>

<style>
    .bg-gradient-success {
        background: linear-gradient(45deg, #28a745, #20c997);
    }

    .bg-gradient-info {
        background: linear-gradient(45deg, #17a2b8, #6f42c1);
    }

    .bg-gradient-warning {
        background: linear-gradient(45deg, #ffc107, #fd7e14);
    }

    .bg-gradient-primary {
        background: linear-gradient(45deg, #007bff, #6610f2);
    }
</style>

<?php include '../includes/footer.php'; ?>