<?php
// auth_check.php (Versi Final)

// Definisikan konstanta keamanan HANYA JIKA BELUM ADA.
// Ini mencegah error "already defined" dan redirect loop.
if (!defined('_KASIR_')) {
    define('_KASIR_', true);
}

// Mulai session jika belum ada.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Cek apakah pengguna sudah login.
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>