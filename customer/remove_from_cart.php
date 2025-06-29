<?php

/**
 * Remove from Cart
 * File: customer/remove_from_cart.php
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
$user_id = $_SESSION['user_id'];

if (!$cart_id) {
    echo json_encode(['success' => false, 'message' => 'Cart ID tidak valid']);
    exit;
}

global $db;

try {
    // Verify cart item belongs to user
    $cart_item = $db->fetch("SELECT * FROM cart WHERE id = ? AND user_id = ?", [$cart_id, $user_id]);

    if (!$cart_item) {
        echo json_encode(['success' => false, 'message' => 'Item keranjang tidak ditemukan']);
        exit;
    }

    // Delete cart item
    $sql = "DELETE FROM cart WHERE id = ?";
    if ($db->execute($sql, [$cart_id])) {
        // Get updated cart count
        $cart_count = $db->fetch("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?", [$user_id])['total'];

        echo json_encode([
            'success' => true,
            'message' => 'Item berhasil dihapus dari keranjang',
            'cart_count' => (int)$cart_count
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal menghapus item dari keranjang']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()]);
}
