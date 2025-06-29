<?php

/**
 * Admin Export Orders - PDF dan Excel
 * File: admin/export_orders.php
 */

require_once '../includes/functions.php';
requireAdmin();

global $db;

// Get export parameters
$format = $_GET['format'] ?? 'excel'; // excel, pdf, csv
$status_filter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Build where conditions (same as orders.php)
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

// Get all orders (no pagination for export)
$orders_sql = "
    SELECT o.*, u.full_name, u.email, u.phone
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    $where_sql
    ORDER BY o.created_at DESC
";
$orders = $db->query($orders_sql, $params);

// Get statistics
$stats = [
    'total' => count($orders),
    'pending' => count(array_filter($orders, fn($o) => $o['status'] == 'pending')),
    'processing' => count(array_filter($orders, fn($o) => $o['status'] == 'processing')),
    'completed' => count(array_filter($orders, fn($o) => $o['status'] == 'completed')),
    'cancelled' => count(array_filter($orders, fn($o) => $o['status'] == 'cancelled')),
    'total_revenue' => array_sum(array_map(fn($o) => $o['status'] == 'completed' ? $o['total_amount'] : 0, $orders))
];

if ($format == 'csv') {
    // Export to CSV
    $filename = 'laporan_pesanan_' . date('Ymd_His') . '.csv';

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');

    // Add BOM for Excel UTF-8 support
    fwrite($output, "\xEF\xBB\xBF");

    // Headers
    fputcsv($output, [
        'No. Pesanan',
        'Nama Pelanggan',
        'Email',
        'Telepon',
        'Status',
        'Total (Rp)',
        'Tanggal Pesanan',
        'Alamat Pengiriman'
    ]);

    // Data
    foreach ($orders as $order) {
        fputcsv($output, [
            $order['order_number'],
            $order['full_name'],
            $order['email'],
            $order['phone'],
            ucfirst($order['status']),
            number_format($order['total_amount'], 0, ',', '.'),
            date('d/m/Y H:i', strtotime($order['created_at'])),
            $order['shipping_address']
        ]);
    }

    fclose($output);
} elseif ($format == 'excel') {
    // Export to Excel (using simple HTML table that Excel can read)
    $filename = 'laporan_pesanan_' . date('Ymd_His') . '.xls';

    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

?>
    <!DOCTYPE html>
    <html>

    <head>
        <meta charset="UTF-8">
        <title>Laporan Pesanan</title>
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
        </style>
    </head>

    <body>
        <h2>LAPORAN PESANAN TOKO ONLINE</h2>
        <p>Tanggal Export: <?php echo date('d/m/Y H:i:s'); ?></p>

        <!-- Statistics -->
        <h3>Ringkasan</h3>
        <table>
            <tr>
                <td><strong>Total Pesanan:</strong></td>
                <td><?php echo $stats['total']; ?></td>
            </tr>
            <tr>
                <td><strong>Pending:</strong></td>
                <td><?php echo $stats['pending']; ?></td>
            </tr>
            <tr>
                <td><strong>Processing:</strong></td>
                <td><?php echo $stats['processing']; ?></td>
            </tr>
            <tr>
                <td><strong>Completed:</strong></td>
                <td><?php echo $stats['completed']; ?></td>
            </tr>
            <tr>
                <td><strong>Cancelled:</strong></td>
                <td><?php echo $stats['cancelled']; ?></td>
            </tr>
            <tr>
                <td><strong>Total Revenue:</strong></td>
                <td class="currency"><?php echo $stats['total_revenue']; ?></td>
            </tr>
        </table>

        <br><br>

        <!-- Orders Data -->
        <h3>Detail Pesanan</h3>
        <table>
            <thead>
                <tr>
                    <th>No. Pesanan</th>
                    <th>Nama Pelanggan</th>
                    <th>Email</th>
                    <th>Telepon</th>
                    <th>Status</th>
                    <th>Total (Rp)</th>
                    <th>Tanggal</th>
                    <th>Alamat Pengiriman</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($order['order_number']); ?></td>
                        <td><?php echo htmlspecialchars($order['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($order['email']); ?></td>
                        <td><?php echo htmlspecialchars($order['phone']); ?></td>
                        <td><?php echo ucfirst($order['status']); ?></td>
                        <td class="currency"><?php echo $order['total_amount']; ?></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                        <td><?php echo htmlspecialchars($order['shipping_address']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </body>

    </html>
<?php

} elseif ($format == 'pdf') {
    // Export to PDF (using HTML that can be printed as PDF)
    $filename = 'laporan_pesanan_' . date('Ymd_His') . '.html';

    header('Content-Type: text/html; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
?>
    <!DOCTYPE html>
    <html>

    <head>
        <meta charset="UTF-8">
        <title>Laporan Pesanan</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                margin: 20px;
                font-size: 12px;
            }

            .header {
                text-align: center;
                margin-bottom: 30px;
            }

            .stats {
                margin-bottom: 30px;
            }

            .stats table {
                width: 100%;
                max-width: 500px;
            }

            table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 20px;
            }

            th,
            td {
                border: 1px solid #ddd;
                padding: 6px;
                text-align: left;
                font-size: 11px;
            }

            th {
                background-color: #f2f2f2;
                font-weight: bold;
            }

            .text-center {
                text-align: center;
            }

            .text-right {
                text-align: right;
            }

            .page-break {
                page-break-before: always;
            }

            @media print {
                body {
                    margin: 0;
                }

                .no-print {
                    display: none;
                }
            }

            @page {
                size: A4 landscape;
                margin: 1cm;
            }
        </style>
    </head>

    <body>
        <div class="header">
            <h1>LAPORAN PESANAN TOKO ONLINE</h1>
            <p>Tanggal Export: <?php echo date('d/m/Y H:i:s'); ?></p>
            <?php if (!empty($status_filter)): ?>
                <p>Filter Status: <?php echo ucfirst($status_filter); ?></p>
            <?php endif; ?>
            <?php if (!empty($date_from) || !empty($date_to)): ?>
                <p>Period:
                    <?php echo $date_from ? date('d/m/Y', strtotime($date_from)) : 'Awal'; ?> -
                    <?php echo $date_to ? date('d/m/Y', strtotime($date_to)) : 'Sekarang'; ?>
                </p>
            <?php endif; ?>
        </div>

        <div class="stats">
            <h3>Ringkasan Pesanan</h3>
            <table>
                <tr>
                    <td><strong>Total Pesanan</strong></td>
                    <td><?php echo $stats['total']; ?></td>
                </tr>
                <tr>
                    <td><strong>Pending</strong></td>
                    <td><?php echo $stats['pending']; ?></td>
                </tr>
                <tr>
                    <td><strong>Processing</strong></td>
                    <td><?php echo $stats['processing']; ?></td>
                </tr>
                <tr>
                    <td><strong>Completed</strong></td>
                    <td><?php echo $stats['completed']; ?></td>
                </tr>
                <tr>
                    <td><strong>Cancelled</strong></td>
                    <td><?php echo $stats['cancelled']; ?></td>
                </tr>
                <tr>
                    <td><strong>Total Revenue</strong></td>
                    <td>Rp <?php echo number_format($stats['total_revenue'], 0, ',', '.'); ?></td>
                </tr>
            </table>
        </div>

        <h3>Detail Pesanan</h3>
        <table>
            <thead>
                <tr>
                    <th style="width: 12%;">No. Pesanan</th>
                    <th style="width: 15%;">Pelanggan</th>
                    <th style="width: 15%;">Email</th>
                    <th style="width: 10%;">Status</th>
                    <th style="width: 12%;">Total</th>
                    <th style="width: 12%;">Tanggal</th>
                    <th style="width: 24%;">Alamat</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($order['order_number']); ?></td>
                        <td><?php echo htmlspecialchars($order['full_name']); ?></td>
                        <td style="font-size: 10px;"><?php echo htmlspecialchars($order['email']); ?></td>
                        <td class="text-center"><?php echo ucfirst($order['status']); ?></td>
                        <td class="text-right">Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?></td>
                        <td class="text-center"><?php echo date('d/m/Y', strtotime($order['created_at'])); ?></td>
                        <td style="font-size: 10px;"><?php echo htmlspecialchars(substr($order['shipping_address'], 0, 50)) . (strlen($order['shipping_address']) > 50 ? '...' : ''); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="no-print" style="margin-top: 30px; text-align: center; padding: 20px; border: 2px dashed #ccc;">
            <p><strong>Cara Save sebagai PDF:</strong></p>
            <p>1. Tekan Ctrl+P (Windows) atau Cmd+P (Mac)</p>
            <p>2. Pilih "Save as PDF" sebagai printer</p>
            <p>3. Klik Save</p>
        </div>
    </body>

    </html>
<?php
}

exit;
?>