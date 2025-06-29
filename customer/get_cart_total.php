<?php

/**
 * Get Cart Total
 * File: customer/get_cart_total.php
 * Toko Online - UAS Pemrograman Web Lanjut
 */

header('Content-Type: application/json');

require_once '../includes/functions.php';

// Proteksi halaman customer
requireCustomer();

$user_id = $_SESSION['user_id'];

global $db;

try {
    // Get cart items with prices
    $cart_items = $db->query("
        SELECT c.quantity, p.price 
        FROM cart c 
        LEFT JOIN products p ON c.product_id = p.id 
        WHERE c.user_id = ? AND p.status = 'active'
    ", [$user_id]);

    $subtotal = 0;
    $total_items = 0;

    foreach ($cart_items as $item) {
        $subtotal += $item['price'] * $item['quantity'];
        $total_items += $item['quantity'];
    }

    // Calculate shipping cost
    $shipping_cost = $subtotal > 100000 ? 0 : 15000; // Free shipping above 100k

    // Calculate total
    $total = $subtotal + $shipping_cost;

    echo json_encode([
        'success' => true,
        'subtotal' => $subtotal,
        'shipping_cost' => $shipping_cost,
        'total' => $total,
        'total_items' => $total_items,
        'free_shipping_eligible' => $subtotal > 100000
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()]);
}
