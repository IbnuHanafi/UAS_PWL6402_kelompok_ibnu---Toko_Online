<?php

/**
 * Get Product Data for AJAX
 * File: admin/get_product.php
 * Toko Online - UAS Pemrograman Web Lanjut
 */

require_once '../includes/functions.php';

// Proteksi halaman admin
requireAdmin();

// Set content type ke JSON
header('Content-Type: application/json');

// Cek apakah ID ada
if (!isset($_GET['id']) || empty($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Product ID is required']);
    exit;
}

$product_id = (int)$_GET['id'];
global $db;

try {
    // Ambil data produk dari database
    $product = $db->fetch("SELECT * FROM products WHERE id = ?", [$product_id]);

    if (!$product) {
        http_response_code(404);
        echo json_encode(['error' => 'Product not found']);
        exit;
    }

    // Kirim data produk dalam format JSON apa adanya (tanpa format harga)
    // JavaScript di halaman products.php yang akan menanganinya
    echo json_encode($product);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}

exit;
