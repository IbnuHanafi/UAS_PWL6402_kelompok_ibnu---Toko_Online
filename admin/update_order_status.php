<?php

/**
 * Update Order Status AJAX
 * File: admin/update_order_status.php
 * Toko Online - UAS Pemrograman Web Lanjut
 */

header('Content-Type: application/json');

require_once '../includes/functions.php';

// Proteksi halaman admin
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$order_id = (int)($_POST['order_id'] ?? 0);
$status = $_POST['status'] ?? '';

$valid_statuses = ['pending', 'processing', 'completed', 'cancelled'];

if (!$order_id || !in_array($status, $valid_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

global $db;

// Check if order exists
$order = $db->fetch("SELECT id FROM orders WHERE id = ?", [$order_id]);
if (!$order) {
    echo json_encode(['success' => false, 'message' => 'Order not found']);
    exit;
}

// Update order status
$sql = "UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?";
if ($db->execute($sql, [$status, $order_id])) {
    echo json_encode(['success' => true, 'message' => 'Status berhasil diupdate']);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal mengupdate status']);
}
