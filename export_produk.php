<?php
// export_produk.php
define('_KASIR_', true);
require_once 'auth_check.php';
require_once 'config/database.php';

// Nama file yang akan diunduh
$filename = "produk_export_" . date('Y-m-d') . ".csv";

// Header untuk memberitahu browser agar mengunduh file
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Buka output stream PHP
$output = fopen('php://output', 'w');

// Tulis baris header ke file CSV (gunakan ; sebagai pemisah)
fputcsv($output, array('nama_produk', 'barcode', 'harga_beli', 'harga_jual', 'kategori', 'stok'), ';');

// Ambil semua data produk dari database
$query = "SELECT name, barcode, cost_price, price, category, stock FROM products ORDER BY name ASC";
$result = $conn->query($query);

if ($result->num_rows > 0) {
    // Loop melalui setiap baris data produk
    while ($row = $result->fetch_assoc()) {
        // Tulis baris data ke file CSV
        fputcsv($output, $row, ';');
    }
}

fclose($output);
$conn->close();
exit();
?>
