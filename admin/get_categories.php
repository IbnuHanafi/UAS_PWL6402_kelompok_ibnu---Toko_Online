<?php

/**
 * Get Category Data for AJAX
 * File: admin/get_category.php
 * Toko Online - UAS Pemrograman Web Lanjut
 */

header('Content-Type: application/json');

require_once '../includes/functions.php';

// Proteksi halaman admin
requireAdmin();

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Category ID required']);
    exit;
}

$category_id = (int)$_GET['id'];

global $db;

$category = $db->fetch("SELECT * FROM categories WHERE id = ?", [$category_id]);

if (!$category) {
    http_response_code(404);
    echo json_encode(['error' => 'Category not found']);
    exit;
}

echo json_encode($category);
