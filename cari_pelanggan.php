<?php
// kasir/cari_pelanggan.php

// Definisikan konstanta keamanan agar file ini diizinkan mengakses file lain.
define('_KASIR_', true);
require_once 'auth_check.php';
require_once 'config/database.php';

header('Content-Type: application/json');
$term = $_GET['term'] ?? '';
$customers = [];

// Lakukan pencarian hanya jika ada input
if (strlen($term) >= 2) {
    $search_term = "%{$term}%";
    $stmt = $conn->prepare("SELECT id, name, phone FROM customers WHERE name LIKE ? OR phone LIKE ? LIMIT 10");
    $stmt->bind_param("ss", $search_term, $search_term);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $customers[] = $row;
    }
    $stmt->close();
}

echo json_encode($customers);
$conn->close();
?>
