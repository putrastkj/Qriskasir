<?php
// config/database.php

// Penjaga: Pastikan file ini tidak diakses langsung.
// Ia hanya akan berjalan jika file pemanggilnya sudah memiliki "kunci" _KASIR_.
if (!defined('_KASIR_')) {
    // Hentikan eksekusi dengan pesan sederhana untuk mencegah redirect loop.
    die('Akses langsung tidak diizinkan.');
}

$host = "192.168.1.17";
$username = "root";
$password = "password";
$dbname = "demokasir";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($host, $username, $password, $dbname);
    $conn->set_charset("utf8mb4");

} catch (mysqli_sql_exception $e) {
    http_response_code(503);
    die('
        <!DOCTYPE html>
        <html lang="id">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Kesalahan Koneksi</title>
            <script src="https://cdn.tailwindcss.com"></script>
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        </head>
        <body class="bg-gray-100 flex items-center justify-center h-screen">
            <div class="text-center bg-white p-10 rounded-lg shadow-lg">
                <i class="fas fa-database fa-3x text-red-500 mb-4"></i>
                <h1 class="text-2xl font-bold text-gray-800 mb-2">Aplikasi Mengalami Gangguan</h1>
                <p class="text-gray-600">Tidak dapat terhubung ke database.</p>
            </div>
        </body>
        </html>
    ');
}
?>
