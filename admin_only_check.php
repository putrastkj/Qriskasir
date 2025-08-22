<?php
// admin_only_check.php (Versi Final)

// Penjaga: Pastikan file ini tidak diakses langsung.
if (!defined('_KASIR_')) {
    header('Location: login.php');
    exit();
}

// Panggil auth_check utama untuk memastikan pengguna sudah login
require_once 'auth_check.php';

// Sekarang, cek peran pengguna.
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    // Jika bukan admin, kembalikan ke dasbor dengan pesan error.
    $_SESSION['action_status'] = ['type' => 'error', 'message' => 'Akses ditolak. Fitur ini hanya untuk Admin.'];
    header("Location: admin.php");
    exit();
}
?>
