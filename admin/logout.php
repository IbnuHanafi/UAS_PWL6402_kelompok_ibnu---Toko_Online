<?php

/**
 * Admin Logout
 * File: admin/logout.php
 * Toko Online - UAS Pemrograman Web Lanjut
 */

require_once '../includes/functions.php';

// Start session jika belum dimulai
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Hapus semua session variables
session_unset();

// Hancurkan session
session_destroy();

// Redirect ke login page dengan pesan
redirect('../auth/login.php?message=logged_out');
