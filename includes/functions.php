<?php

/**
 * Functions Helper
 * File: includes/functions.php
 * Toko Online - UAS Pemrograman Web Lanjut
 */

// Start session jika belum dimulai
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database configuration
require_once __DIR__ . '/../config/database.php';

/**
 * Fungsi untuk redirect halaman
 */
function redirect($url)
{
    header("Location: $url");
    exit();
}

/**
 * Fungsi untuk mengecek apakah user sudah login
 */
function isLoggedIn()
{
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Fungsi untuk mengecek apakah user adalah admin
 */
function isAdmin()
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Fungsi untuk mengecek apakah user adalah customer
 */
function isCustomer()
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'customer';
}

/**
 * Fungsi untuk memproteksi halaman admin
 */
function requireAdmin()
{
    if (!isLoggedIn() || !isAdmin()) {
        redirect('../auth/login.php');
    }
}

/**
 * Fungsi untuk memproteksi halaman customer
 */
function requireCustomer()
{
    if (!isLoggedIn() || !isCustomer()) {
        redirect('../auth/login.php');
    }
}

/**
 * Fungsi untuk memproteksi halaman yang memerlukan login
 */
function requireLogin()
{
    if (!isLoggedIn()) {
        redirect('../auth/login.php');
    }
}

/**
 * Fungsi untuk clean input data
 */
function cleanInput($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Fungsi untuk validasi email
 */
function validateEmail($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Fungsi untuk hash password
 */
function hashPassword($password)
{
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Fungsi untuk verify password
 */
function verifyPassword($password, $hash)
{
    return password_verify($password, $hash);
}

/**
 * Fungsi untuk format rupiah
 */
function formatRupiah($amount)
{
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

/**
 * Fungsi untuk format tanggal Indonesia
 */
function formatDate($date)
{
    $bulan = [
        1 => 'Januari',
        'Februari',
        'Maret',
        'April',
        'Mei',
        'Juni',
        'Juli',
        'Agustus',
        'September',
        'Oktober',
        'November',
        'Desember'
    ];

    $timestamp = strtotime($date);
    $tanggal = date('j', $timestamp);
    $bulan = $bulan[date('n', $timestamp)];
    $tahun = date('Y', $timestamp);

    return $tanggal . ' ' . $bulan . ' ' . $tahun;
}

/**
 * Fungsi untuk generate order number
 */
function generateOrderNumber()
{
    return 'ORD-' . date('Ymd') . '-' . sprintf('%04d', rand(1, 9999));
}

/**
 * Fungsi untuk set flash message
 */
function setFlashMessage($type, $message)
{
    $_SESSION['flash_type'] = $type;
    $_SESSION['flash_message'] = $message;
}

/**
 * Fungsi untuk get dan hapus flash message
 */
function getFlashMessage()
{
    if (isset($_SESSION['flash_message'])) {
        $type = $_SESSION['flash_type'];
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_type']);
        unset($_SESSION['flash_message']);
        return ['type' => $type, 'message' => $message];
    }
    return null;
}

/**
 * Fungsi untuk mendapatkan data user yang sedang login
 */
function getCurrentUser()
{
    global $db;

    if (!isLoggedIn()) {
        return null;
    }

    $sql = "SELECT * FROM users WHERE id = ?";
    return $db->fetch($sql, [$_SESSION['user_id']]);
}

/**
 * Fungsi untuk mendapatkan jumlah item di cart
 */
function getCartCount($user_id)
{
    global $db;

    $sql = "SELECT SUM(quantity) as total FROM cart WHERE user_id = ?";
    $result = $db->fetch($sql, [$user_id]);

    return $result['total'] ?? 0;
}

/**
 * Fungsi untuk validasi file upload gambar
 */
function validateImageUpload($file)
{
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    $max_size = 5 * 1024 * 1024; // 5MB

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Error uploading file'];
    }

    if (!in_array($file['type'], $allowed_types)) {
        return ['success' => false, 'message' => 'Only JPG, JPEG, PNG & GIF files are allowed'];
    }

    if ($file['size'] > $max_size) {
        return ['success' => false, 'message' => 'File size should not exceed 5MB'];
    }

    return ['success' => true];
}

/**
 * Fungsi untuk upload file gambar
 */
function uploadImage($file, $upload_dir = '../assets/images/products/')
{
    // Pastikan directory exists
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // Validate file
    $validation = validateImageUpload($file);
    if (!$validation['success']) {
        return $validation;
    }

    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $target_path = $upload_dir . $filename;

    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        return ['success' => true, 'filename' => $filename];
    } else {
        return ['success' => false, 'message' => 'Failed to upload file'];
    }
}

/**
 * Fungsi untuk mendapatkan status badge HTML
 */
function getStatusBadge($status)
{
    $badges = [
        'pending' => '<span class="badge bg-warning">Pending</span>',
        'processing' => '<span class="badge bg-info">Processing</span>',
        'completed' => '<span class="badge bg-success">Completed</span>',
        'cancelled' => '<span class="badge bg-danger">Cancelled</span>',
        'active' => '<span class="badge bg-success">Active</span>',
        'inactive' => '<span class="badge bg-secondary">Inactive</span>'
    ];

    return $badges[$status] ?? '<span class="badge bg-secondary">' . ucfirst($status) . '</span>';
}

/**
 * Fungsi untuk pagination
 */
function getPagination($current_page, $total_pages, $base_url)
{
    $pagination = '<nav aria-label="Page navigation"><ul class="pagination justify-content-center">';

    // Previous button
    if ($current_page > 1) {
        $prev_page = $current_page - 1;
        $pagination .= '<li class="page-item"><a class="page-link" href="' . $base_url . '?page=' . $prev_page . '">Previous</a></li>';
    }

    // Page numbers
    for ($i = 1; $i <= $total_pages; $i++) {
        $active = ($i == $current_page) ? 'active' : '';
        $pagination .= '<li class="page-item ' . $active . '"><a class="page-link" href="' . $base_url . '?page=' . $i . '">' . $i . '</a></li>';
    }

    // Next button
    if ($current_page < $total_pages) {
        $next_page = $current_page + 1;
        $pagination .= '<li class="page-item"><a class="page-link" href="' . $base_url . '?page=' . $next_page . '">Next</a></li>';
    }

    $pagination .= '</ul></nav>';

    return $pagination;
}
