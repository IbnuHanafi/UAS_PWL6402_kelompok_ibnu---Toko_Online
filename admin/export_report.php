<?php

/**
 * Admin Export Sales Report - PDF dan Excel
 * File: admin/export_report.php
 */

require_once '../includes/functions.php';
requireAdmin();

global $db;

// Get export parameters
$format = $_GET['format'] ?? 'pdf'; // pdf, excel
$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to = $_GET['date_to'] ?? date('Y-m-d');
$status_filter = $_GET['status'] ?? '';
$period = $_GET['period'] ?? 'custom';

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
}

// Build where conditions
$where_conditions = ["DATE(o.created_at) BETWEEN ? AND ?"];
$params = [$date_from, $date_to];

if (!empty($status_filter)) {
    $where_conditions[] = "o.status = ?";
    $params[] = $status_filter;
}

$where_sql = "WHERE " . implode(" AND ", $where_conditions);

// Get all data for report
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

$detailed_orders = $db->query("
    SELECT o.*, u.full_name, u.email
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    $where_sql
    ORDER BY o.created_at DESC
", $params);

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

$total_customers = $db->fetch("SELECT COUNT(DISTINCT user_id) as count FROM orders o $where_sql", $params)['count'];
$conversion_rate = $total_customers > 0 ? ($sales_overview['completed_orders'] / $total_customers) * 100 : 0;

if ($format == 'excel') {
    // Export to Excel
    $filename = 'laporan_penjualan_' . date('Ymd_His') . '.xls';

    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
?>
    <!DOCTYPE html>
    <html>

    <head>
        <meta charset="UTF-8">
        <title>Laporan Penjualan</title>
        <style>
            table {
                border-collapse: collapse;
                width: 100%;
            }

            th,
            td {
                border: 1px solid #ddd;
                padding: 8px;
                text-align: left;
            }

            th {
                background-color: #f2f2f2;
                font-weight: bold;
            }

            .number {
                mso-number-format: "#,##0";
            }

            .currency {
                mso-number-format: "#,##0";
            }

            .center {
                text-align: center;
            }

            .right {
                text-align: right;
            }
        </style>
    </head>

    <body>
        <h2>LAPORAN PENJUALAN TOKO ONLINE</h2>
        <p>Periode: <?php echo date('d/m/Y', strtotime($date_from)); ?> - <?php echo date('d/m/Y', strtotime($date_to)); ?></p>
        <p>Tanggal Export: <?php echo date('d/m/Y H:i:s'); ?></p>

        <!-- Sales Overview -->
        <h3>Ringkasan Penjualan</h3>
        <table>
            <tr>
                <td><strong>Total Pesanan:</strong></td>
                <td class="number"><?php echo $sales_overview['total_orders']; ?></td>
            </tr>
            <tr>
                <td><strong>Completed:</strong></td>
                <td class="number"><?php echo $sales_overview['completed_orders']; ?></td>
            </tr>
            <tr>
                <td><strong>Pending:</strong></td>
                <td class="number"><?php echo $sales_overview['pending_orders']; ?></td>
            </tr>
            <tr>
                <td><strong>Processing:</strong></td>
                <td class="number"><?php echo $sales_overview['processing_orders']; ?></td>
            </tr>
            <tr>
                <td><strong>Cancelled:</strong></td>
                <td class="number"><?php echo $sales_overview['cancelled_orders']; ?></td>
            </tr>
            <tr>
                <td><strong>Total Revenue:</strong></td>
                <td class="currency"><?php echo $sales_overview['total_revenue']; ?></td>
            </tr>
            <tr>
                <td><strong>Gross Sales:</strong></td>
                <td class="currency"><?php echo $sales_overview['gross_sales']; ?></td>
            </tr>
            <tr>
                <td><strong>Average Order Value:</strong></td>
                <td class="currency"><?php echo $sales_overview['avg_order_value']; ?></td>
            </tr>
            <tr>
                <td><strong>Unique Customers:</strong></td>
                <td class="number"><?php echo $total_customers; ?></td>
            </tr>
            <tr>
                <td><strong>Conversion Rate:</strong></td>
                <td><?php echo number_format($conversion_rate, 2); ?>%</td>
            </tr>
        </table>

        <br><br>

        <!-- Top Products -->
        <h3>Top 10 Produk Terlaris</h3>
        <table>
            <thead>
                <tr>
                    <th>Nama Produk</th>
                    <th>Quantity Terjual</th>
                    <th>Total Penjualan (Rp)</th>
                    <th>Jumlah Order</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($top_products as $product): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                        <td class="number center"><?php echo $product['total_quantity']; ?></td>
                        <td class="currency"><?php echo $product['total_sales']; ?></td>
                        <td class="number center"><?php echo $product['order_count']; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <br><br>

        <!-- Daily Sales -->
        <h3>Penjualan Harian</h3>
        <table>
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Jumlah Order</th>
                    <th>Revenue (Rp)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($daily_sales as $daily): ?>
                    <tr>
                        <td class="center"><?php echo date('d/m/Y', strtotime($daily['sale_date'])); ?></td>
                        <td class="number center"><?php echo $daily['order_count']; ?></td>
                        <td class="currency"><?php echo $daily['daily_revenue']; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <br><br>

        <!-- Detailed Orders -->
        <h3>Detail Transaksi</h3>
        <table>
            <thead>
                <tr>
                    <th>No. Pesanan</th>
                    <th>Tanggal</th>
                    <th>Pelanggan</th>
                    <th>Email</th>
                    <th>Total (Rp)</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($detailed_orders as $order): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($order['order_number']); ?></td>
                        <td class="center"><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                        <td><?php echo htmlspecialchars($order['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($order['email']); ?></td>
                        <td class="currency"><?php echo $order['total_amount']; ?></td>
                        <td class="center"><?php echo ucfirst($order['status']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </body>

    </html>
<?php

} else {
    // Export to PDF (HTML format for print)
    $filename = 'laporan_penjualan_' . date('Ymd_His') . '.html';

    header('Content-Type: text/html; charset=utf-8');
    header('Content-Disposition: inline; filename="' . $filename . '"');
?>
    <!DOCTYPE html>
    <html>

    <head>
        <meta charset="UTF-8">
        <title>Laporan Penjualan</title>
        <style>
            * {
                box-sizing: border-box;
            }

            body {
                font-family: 'Arial', sans-serif;
                margin: 0;
                padding: 20px;
                background: white;
                color: #333;
                line-height: 1.4;
                font-size: 12px;
            }

            .container {
                max-width: 1200px;
                margin: 0 auto;
            }

            .header {
                text-align: center;
                margin-bottom: 30px;
                border-bottom: 3px solid #007bff;
                padding-bottom: 20px;
            }

            .header h1 {
                color: #2c3e50;
                margin: 0 0 10px 0;
                font-size: 28px;
                font-weight: bold;
            }

            .header p {
                margin: 5px 0;
                color: #7f8c8d;
                font-size: 14px;
            }

            .overview {
                background: #f8f9fa;
                padding: 20px;
                border-radius: 8px;
                margin-bottom: 30px;
                border-left: 4px solid #007bff;
            }

            .overview h3 {
                margin-top: 0;
                color: #2c3e50;
            }

            .overview-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 15px;
                margin-top: 15px;
            }

            .overview-item {
                background: white;
                padding: 15px;
                border-radius: 5px;
                border: 1px solid #dee2e6;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }

            .overview-item .label {
                font-weight: bold;
                color: #495057;
            }

            .overview-item .value {
                font-size: 16px;
                font-weight: bold;
                color: #007bff;
            }

            .section {
                margin-bottom: 40px;
            }

            .section h3 {
                color: #2c3e50;
                border-bottom: 2px solid #007bff;
                padding-bottom: 10px;
                margin-bottom: 20px;
            }

            .data-table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 20px;
                background: white;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
                border-radius: 8px;
                overflow: hidden;
            }

            .data-table th,
            .data-table td {
                border: 1px solid #dee2e6;
                padding: 10px;
                text-align: left;
            }

            .data-table th {
                background: #007bff;
                color: white;
                font-weight: bold;
                font-size: 11px;
                text-transform: uppercase;
            }

            .data-table tr:nth-child(even) {
                background: #f8f9fa;
            }

            .data-table tr:hover {
                background: #e3f2fd;
            }

            .text-right {
                text-align: right;
            }

            .text-center {
                text-align: center;
            }

            .export-info {
                background: #e7f3ff;
                border: 2px dashed #007bff;
                padding: 20px;
                text-align: center;
                margin-top: 30px;
                border-radius: 8px;
            }

            .export-info h4 {
                margin-top: 0;
                color: #007bff;
            }

            .stats-grid {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 20px;
                margin-bottom: 30px;
            }

            @media print {
                body {
                    margin: 0;
                    padding: 10px;
                    font-size: 10px;
                }

                .export-info {
                    display: none;
                }

                .data-table th {
                    background: #f0f0f0 !important;
                    color: black !important;
                }

                .overview {
                    background: #f5f5f5 !important;
                }

                @page {
                    size: A4 landscape;
                    margin: 0.5cm;
                }
            }
        </style>
    </head>

    <body>
        <div class="container">
            <div class="header">
                <h1>📊 LAPORAN PENJUALAN TOKO ONLINE</h1>
                <p><strong>Periode:</strong> <?php echo date('d F Y', strtotime($date_from)); ?> - <?php echo date('d F Y', strtotime($date_to)); ?></p>
                <p><strong>Tanggal Export:</strong> <?php echo date('d F Y H:i:s'); ?></p>
                <?php if (!empty($status_filter)): ?>
                    <p><strong>Filter Status:</strong> <?php echo ucfirst($status_filter); ?></p>
                <?php endif; ?>
            </div>

            <div class="overview">
                <h3>📈 RINGKASAN PENJUALAN</h3>
                <div class="overview-grid">
                    <div class="overview-item">
                        <span class="label">Total Pesanan</span>
                        <span class="value"><?php echo number_format($sales_overview['total_orders']); ?></span>
                    </div>
                    <div class="overview-item">
                        <span class="label">Completed Orders</span>
                        <span class="value"><?php echo number_format($sales_overview['completed_orders']); ?></span>
                    </div>
                    <div class="overview-item">
                        <span class="label">Pending Orders</span>
                        <span class="value"><?php echo number_format($sales_overview['pending_orders']); ?></span>
                    </div>
                    <div class="overview-item">
                        <span class="label">Processing Orders</span>
                        <span class="value"><?php echo number_format($sales_overview['processing_orders']); ?></span>
                    </div>
                    <div class="overview-item">
                        <span class="label">Cancelled Orders</span>
                        <span class="value"><?php echo number_format($sales_overview['cancelled_orders']); ?></span>
                    </div>
                    <div class="overview-item">
                        <span class="label">Total Revenue</span>
                        <span class="value">Rp <?php echo number_format($sales_overview['total_revenue'], 0, ',', '.'); ?></span>
                    </div>
                    <div class="overview-item">
                        <span class="label">Gross Sales</span>
                        <span class="value">Rp <?php echo number_format($sales_overview['gross_sales'], 0, ',', '.'); ?></span>
                    </div>
                    <div class="overview-item">
                        <span class="label">Average Order Value</span>
                        <span class="value">Rp <?php echo number_format($sales_overview['avg_order_value'], 0, ',', '.'); ?></span>
                    </div>
                    <div class="overview-item">
                        <span class="label">Unique Customers</span>
                        <span class="value"><?php echo number_format($total_customers); ?></span>
                    </div>
                    <div class="overview-item">
                        <span class="label">Conversion Rate</span>
                        <span class="value"><?php echo number_format($conversion_rate, 2); ?>%</span>
                    </div>
                </div>
            </div>

            <div class="stats-grid">
                <div class="section">
                    <h3>🏆 TOP 10 PRODUK TERLARIS</h3>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Ranking</th>
                                <th>Nama Produk</th>
                                <th>Qty Terjual</th>
                                <th>Total Sales</th>
                                <th>Jumlah Order</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($top_products)): ?>
                                <tr>
                                    <td colspan="5" class="text-center" style="padding: 20px; color: #6c757d;">
                                        Tidak ada data produk
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($top_products as $index => $product): ?>
                                    <tr>
                                        <td class="text-center"><strong><?php echo $index + 1; ?></strong></td>
                                        <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                        <td class="text-center"><?php echo number_format($product['total_quantity']); ?></td>
                                        <td class="text-right"><strong>Rp <?php echo number_format($product['total_sales'], 0, ',', '.'); ?></strong></td>
                                        <td class="text-center"><?php echo number_format($product['order_count']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="section">
                    <h3>📅 PENJUALAN HARIAN</h3>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Orders</th>
                                <th>Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($daily_sales)): ?>
                                <tr>
                                    <td colspan="3" class="text-center" style="padding: 20px; color: #6c757d;">
                                        Tidak ada data penjualan harian
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($daily_sales as $daily): ?>
                                    <tr>
                                        <td class="text-center"><?php echo date('d/m/Y', strtotime($daily['sale_date'])); ?></td>
                                        <td class="text-center"><?php echo number_format($daily['order_count']); ?></td>
                                        <td class="text-right"><strong>Rp <?php echo number_format($daily['daily_revenue'], 0, ',', '.'); ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="section">
                <h3>📋 DETAIL TRANSAKSI (<?php echo count($detailed_orders); ?> transaksi)</h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>No. Pesanan</th>
                            <th>Tanggal</th>
                            <th>Pelanggan</th>
                            <th>Email</th>
                            <th>Total</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($detailed_orders)): ?>
                            <tr>
                                <td colspan="6" class="text-center" style="padding: 30px; color: #6c757d;">
                                    <strong>Tidak ada data transaksi dalam periode ini</strong>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($detailed_orders as $order): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($order['order_number']); ?></strong></td>
                                    <td class="text-center"><?php echo date('d/m/y H:i', strtotime($order['created_at'])); ?></td>
                                    <td><?php echo htmlspecialchars($order['full_name']); ?></td>
                                    <td style="font-size: 10px;"><?php echo htmlspecialchars($order['email']); ?></td>
                                    <td class="text-right"><strong>Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?></strong></td>
                                    <td class="text-center">
                                        <span style="padding: 2px 6px; border-radius: 4px; font-size: 10px; 
                                        <?php
                                        echo $order['status'] == 'completed' ? 'background: #d4edda; color: #155724;' : ($order['status'] == 'pending' ? 'background: #fff3cd; color: #856404;' : ($order['status'] == 'processing' ? 'background: #d1ecf1; color: #0c5460;' :
                                                    'background: #f8d7da; color: #721c24;'));
                                        ?>">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="export-info">
                <h4>💡 Cara Save sebagai PDF:</h4>
                <p><strong>Desktop:</strong> Tekan <kbd>Ctrl+P</kbd> (Windows) atau <kbd>Cmd+P</kbd> (Mac) → Pilih "Save as PDF"</p>
                <p><strong>Mobile:</strong> Tap menu browser → Print → Save as PDF</p>
                <p><em>💡 Tip: Pilih orientasi "Landscape" untuk hasil terbaik</em></p>
            </div>
        </div>

        <script>
            // Auto print if requested
            if (window.location.hash === '#print') {
                window.onload = function() {
                    setTimeout(() => window.print(), 500);
                };
            }
        </script>
    </body>

    </html>
<?php
}

exit;
?>