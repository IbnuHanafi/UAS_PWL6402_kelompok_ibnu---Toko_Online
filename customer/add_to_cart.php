<?php

/**
 * Customer Add to Cart
 * File: customer/add_to_cart.php
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

$product_id = (int)($_POST['product_id'] ?? 0);
$quantity = (int)($_POST['quantity'] ?? 1);
$user_id = $_SESSION['user_id'];

if (!$product_id || $quantity < 1) {
    echo json_encode(['success' => false, 'message' => 'Data tidak valid']);
    exit;
}

global $db;

// Check if product exists and is active
$product = $db->fetch("SELECT * FROM products WHERE id = ? AND status = 'active'", [$product_id]);
if (!$product) {
    echo json_encode(['success' => false, 'message' => 'Produk tidak ditemukan atau tidak aktif']);
    exit;
}

// Check stock availability
if ($product['stock'] < $quantity) {
    echo json_encode(['success' => false, 'message' => 'Stok tidak mencukupi. Stok tersedia: ' . $product['stock']]);
    exit;
}

try {
    // Check if product already in cart
    $existing_cart = $db->fetch("SELECT * FROM cart WHERE user_id = ? AND product_id = ?", [$user_id, $product_id]);

    if ($existing_cart) {
        // Update quantity if product already in cart
        $new_quantity = $existing_cart['quantity'] + $quantity;

        // Check total quantity against stock
        if ($new_quantity > $product['stock']) {
            echo json_encode([
                'success' => false,
                'message' => 'Total quantity melebihi stok. Stok tersedia: ' . $product['stock'] . ', di keranjang: ' . $existing_cart['quantity']
            ]);
            exit;
        }

        $sql = "UPDATE cart SET quantity = ? WHERE id = ?";
        $params = [$new_quantity, $existing_cart['id']];
        $message = 'Quantity produk di keranjang berhasil diupdate';
    } else {
        // Add new item to cart
        $sql = "INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)";
        $params = [$user_id, $product_id, $quantity];
        $message = 'Produk berhasil ditambahkan ke keranjang';
    }

    if ($db->execute($sql, $params)) {
        // Get updated cart count
        $cart_count = $db->fetch("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?", [$user_id])['total'];

        echo json_encode([
            'success' => true,
            'message' => $message,
            'cart_count' => (int)$cart_count
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal menambahkan produk ke keranjang']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()]);
}
