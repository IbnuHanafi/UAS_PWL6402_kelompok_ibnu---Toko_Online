<?php

/**
 * Export Products
 * File: admin/export_products.php
 * Toko Online - UAS Pemrograman Web Lanjut
 */

// PENTING: Semua require dan use statement diletakkan di paling atas
require_once '../includes/functions.php';
require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Dompdf\Dompdf;
use Dompdf\Options;

// Proteksi halaman admin
requireAdmin();

global $db;

// Get parameters
$format = $_GET['format'] ?? 'excel';
$search = $_GET['search'] ?? '';
$category_filter = $_GET['category'] ?? '';
$status_filter = $_GET['status'] ?? '';

// Build query conditions (sama seperti di products.php)
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(p.name LIKE ? OR p.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if (!empty($category_filter)) {
    $where_conditions[] = "p.category_id = ?";
    $params[] = $category_filter;
}
if (!empty($status_filter)) {
    $where_conditions[] = "p.status = ?";
    $params[] = $status_filter;
}
$where_sql = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get all products for export (tanpa limit)
$products_sql = "
    SELECT p.*, c.name as category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    $where_sql 
    ORDER BY p.created_at DESC
";
// ===== PERBAIKAN DILAKUKAN DI BARIS INI =====
$products = $db->query($products_sql, $params);


// Menyiapkan konten HTML (akan digunakan untuk PDF dan Excel)
ob_start();
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Data Produk</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 12px;
        }

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

        .currency {
            text-align: right;
        }

        .center {
            text-align: center;
        }

        h2 {
            font-family: sans-serif;
        }
    </style>
</head>

<body>
    <h2>DATA PRODUK TOKO ONLINE</h2>
    <p>Tanggal Export: <?php echo date('d F Y, H:i:s'); ?></p>
    <hr>
    <table>
        <thead>
            <tr>
                <th>Nama Produk</th>
                <th>Kategori</th>
                <th>Harga (Rp)</th>
                <th>Stok</th>
                <th>Status</th>
                <th>Tanggal Dibuat</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($products as $product): ?>
                <tr>
                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                    <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                    <td class="currency"><?php echo number_format($product['price'], 0, ',', '.'); ?></td>
                    <td class="center"><?php echo $product['stock']; ?></td>
                    <td class="center"><?php echo ucfirst($product['status']); ?></td>
                    <td class="center"><?php echo date('d/m/Y', strtotime($product['created_at'])); ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($products)): ?>
                <tr>
                    <td colspan="6" class="center">Tidak ada produk ditemukan.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>

</html>
<?php
$html_content = ob_get_clean();


// Logika Export berdasarkan format
if ($format == 'pdf') {
    $filename = 'data_produk_' . date('Ymd_His') . '.pdf';
    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html_content);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $dompdf->stream($filename, ["Attachment" => true]);
} elseif ($format == 'csv') {
    $filename = 'data_produk_' . date('Ymd_His') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');
    fwrite($output, "\xEF\xBB\xBF");

    fputcsv($output, ['Nama Produk', 'Kategori', 'Harga', 'Stok', 'Status', 'Tanggal Dibuat']);

    foreach ($products as $product) {
        fputcsv($output, [
            $product['name'],
            $product['category_name'],
            $product['price'],
            $product['stock'],
            $product['status'],
            $product['created_at']
        ]);
    }
    fclose($output);
} elseif ($format == 'excel') {
    $filename = 'data_produk_' . date('Ymd_His') . '.xls';
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    echo $html_content;
}

exit;
?>