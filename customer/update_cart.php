<?php

/**
 * Update Cart
 * File: customer/update_cart.php
 * Toko Online - UAS Pemrograman Web Lanjut
 */

header('Content-Type: application/json');

require_once '../includes/functions.php';

// Proteksi halaman customer
requireCustomer();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$cart_id = (int)($_POST['cart_id'] ?? 0);
$quantity = (int)($_POST['quantity'] ?? 1);
$user_id = $_SESSION['user_id'];

if (!$cart_id || $quantity < 1) {
    echo json_encode(['success' => false, 'message' => 'Data tidak valid']);
    exit;
}

global $db;

try {
    // Verify cart item belongs to user
    $cart_item = $db->fetch("
        SELECT c.*, p.name, p.price, p.stock 
        FROM cart c 
        LEFT JOIN products p ON c.product_id = p.id 
        WHERE c.id = ? AND c.user_id = ?
    ", [$cart_id, $user_id]);

    if (!$cart_item) {
        echo json_encode(['success' => false, 'message' => 'Item keranjang tidak ditemukan']);
        exit;
    }

    // Check stock availability
    if ($quantity > $cart_item['stock']) {
        echo json_encode(['success' => false, 'message' => 'Quantity melebihi stok tersedia: ' . $cart_item['stock']]);
        exit;
    }

    // Update cart quantity
    $sql = "UPDATE cart SET quantity = ? WHERE id = ?";
    if ($db->execute($sql, [$quantity, $cart_id])) {
        $item_total = $cart_item['price'] * $quantity;

        echo json_encode([
            'success' => true,
            'message' => 'Keranjang berhasil diupdate',
            'item_total' => $item_total,
            'formatted_total' => formatRupiah($item_total)
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal mengupdate keranjang']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()]);
}
