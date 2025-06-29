<?php

/**
 * Admin Export Customers - CSV, Excel, dan PDF (Updated)
 * File: admin/export_customers.php
 */

// Muat pustaka dari Composer, diperlukan untuk Dompdf
require_once '../vendor/autoload.php';
require_once '../includes/functions.php';
requireAdmin();

// Impor kelas Dompdf
use Dompdf\Dompdf;
use Dompdf\Options;

global $db;

// Get export parameters
$format = $_GET['format'] ?? 'csv'; // csv, excel, pdf
$search = $_GET['search'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

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

// Get customers data (Updated query method dan field name)
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
";
$customers = $db->query($customers_sql, $params);

// Calculate statistics (NEW FEATURE)
$stats = [
    'total' => count($customers),
    'new_today' => count(array_filter($customers, fn($c) => date('Y-m-d', strtotime($c['created_at'])) == date('Y-m-d'))),
    'with_orders' => count(array_filter($customers, fn($c) => $c['total_orders'] > 0)),
    'total_revenue' => array_sum(array_column($customers, 'total_spent'))
];

// Calculate monthly registrations (NEW FEATURE)
$monthly_registrations = [];
for ($i = 2; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $count = count(array_filter($customers, function ($c) use ($month) {
        return date('Y-m', strtotime($c['created_at'])) == $month;
    }));
    $monthly_registrations[$month] = $count;
}

// Generate HTML content (digunakan untuk Excel dan PDF) - UPDATED WITH NEW FEATURES
ob_start();
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Data Pelanggan</title>
    <style>
        body {
            font-family: sans-serif;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            font-size: 12px;
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
            text-align: center;
        }

        .currency {
            mso-number-format: "#,##0";
            text-align: right;
        }

        .center {
            text-align: center;
        }

        h2,
        h3 {
            font-family: sans-serif;
        }
    </style>
</head>

<body>
    <h2>DATA PELANGGAN TOKO ONLINE</h2>
    <p>Tanggal Export: <?php echo date('d/m/Y H:i:s'); ?></p>
    <hr>

    <!-- Statistics Section (NEW) -->
    <h3>Ringkasan</h3>
    <table>
        <tr>
            <td><strong>Total Pelanggan:</strong></td>
            <td class="number"><?php echo $stats['total']; ?></td>
        </tr>
        <tr>
            <td><strong>Pelanggan Baru Hari Ini:</strong></td>
            <td class="number"><?php echo $stats['new_today']; ?></td>
        </tr>
        <tr>
            <td><strong>Pelanggan dengan Pesanan:</strong></td>
            <td class="number"><?php echo $stats['with_orders']; ?></td>
        </tr>
        <tr>
            <td><strong>Total Revenue:</strong></td>
            <td class="currency">Rp <?php echo number_format($stats['total_revenue'], 0, ',', '.'); ?></td>
        </tr>
    </table>

    <br><br>

    <h3>Detail Pelanggan</h3>
    <table>
        <thead>
            <tr>
                <th>Nama Lengkap</th>
                <th>Email</th>
                <th>Telepon</th>
                <th>Alamat</th>
                <th>Total Pesanan</th>
                <th>Total Pembelian (Rp)</th>
                <th>Pesanan Terakhir</th>
                <th>Tanggal Bergabung</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($customers as $customer): ?>
                <tr>
                    <td><?php echo htmlspecialchars($customer['full_name']); ?></td>
                    <td><?php echo htmlspecialchars($customer['email']); ?></td>
                    <td><?php echo htmlspecialchars($customer['phone']); ?></td>
                    <td><?php echo htmlspecialchars($customer['address']); ?></td>
                    <td class="center"><?php echo $customer['total_orders']; ?></td>
                    <td class="currency"><?php echo number_format($customer['total_spent'], 0, ',', '.'); ?></td>
                    <td class="center"><?php echo $customer['last_order_date'] ? date('d/m/Y', strtotime($customer['last_order_date'])) : 'Belum ada'; ?></td>
                    <td class="center"><?php echo date('d/m/Y', strtotime($customer['created_at'])); ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($customers)): ?>
                <tr>
                    <td colspan="8" class="center">Tidak ada data ditemukan.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Monthly Registration Section (NEW) -->
    <br><br>
    <h3>Registrasi Per Bulan (3 Bulan Terakhir)</h3>
    <table>
        <thead>
            <tr>
                <th>Bulan</th>
                <th>Jumlah Registrasi</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($monthly_registrations as $month => $count): ?>
                <tr>
                    <td><?php echo date('F Y', strtotime($month . '-01')); ?></td>
                    <td class="number center"><?php echo $count; ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>

</html>
<?php
$html_content = ob_get_clean();


// =================================================================
// LOGIKA EXPORT BERDASARKAN FORMAT (UPDATED)
// =================================================================

if ($format == 'csv') {
    // Export to CSV (UPDATED WITH STATISTICS)
    $filename = 'data_pelanggan_' . date('Ymd_His') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');
    fwrite($output, "\xEF\xBB\xBF"); // BOM for UTF-8

    // Statistics header (NEW)
    fputcsv($output, ['DATA PELANGGAN TOKO ONLINE']);
    fputcsv($output, ['Tanggal Export:', date('d/m/Y H:i:s')]);
    fputcsv($output, []);
    fputcsv($output, ['RINGKASAN']);
    fputcsv($output, ['Total Pelanggan', $stats['total']]);
    fputcsv($output, ['Pelanggan Baru Hari Ini', $stats['new_today']]);
    fputcsv($output, ['Pelanggan dengan Pesanan', $stats['with_orders']]);
    fputcsv($output, ['Total Revenue dari Pelanggan', 'Rp ' . number_format($stats['total_revenue'], 0, ',', '.')]);
    fputcsv($output, []);
    fputcsv($output, ['DETAIL PELANGGAN']);

    // Headers (UPDATED with address field)
    fputcsv($output, [
        'Nama Lengkap',
        'Email',
        'Telepon',
        'Alamat',
        'Total Pesanan',
        'Total Pembelian (Rp)',
        'Pesanan Terakhir',
        'Tanggal Bergabung'
    ]);

    // Data (UPDATED)
    foreach ($customers as $customer) {
        fputcsv($output, [
            $customer['full_name'],
            $customer['email'],
            $customer['phone'],
            $customer['address'],
            $customer['total_orders'],
            number_format($customer['total_spent'], 0, ',', '.'),
            $customer['last_order_date'] ? date('d/m/Y', strtotime($customer['last_order_date'])) : 'Belum ada',
            date('d/m/Y', strtotime($customer['created_at']))
        ]);
    }
    fclose($output);
} elseif ($format == 'pdf') {
    // =======================================================
    // ===== EXPORT PDF (UNCHANGED) =====
    // =======================================================
    $filename = 'data_pelanggan_' . date('Ymd_His') . '.pdf';

    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', true);

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html_content);
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->render();

    $dompdf->stream($filename, ["Attachment" => true]);
} elseif ($format == 'excel') {
    // Export to Excel (UPDATED - now uses the improved HTML content)
    $filename = 'data_pelanggan_' . date('Ymd_His') . '.xls';
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    // Tampilkan konten HTML yang sudah diupdate dengan fitur baru
    echo $html_content;
}

exit;
?>