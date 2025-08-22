<?php
// aksi_update_stok.php
define('_KASIR_', true);
require_once 'auth_check.php';
require_once 'config/database.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => 'Permintaan tidak valid.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $quantity_to_add = isset($_POST['quantity_to_add']) ? (int)$_POST['quantity_to_add'] : 0;

    if ($product_id > 0 && $quantity_to_add > 0) {
        try {
            // Gunakan query 'stock = stock + ?' untuk keamanan
            $stmt = $conn->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
            $stmt->bind_param("ii", $quantity_to_add, $product_id);
            $stmt->execute();

            // Ambil stok terbaru untuk dikirim kembali
            $stmt_get = $conn->prepare("SELECT stock FROM products WHERE id = ?");
            $stmt_get->bind_param("i", $product_id);
            $stmt_get->execute();
            $new_stock = $stmt_get->get_result()->fetch_assoc()['stock'];
            
            $response = [
                'success' => true,
                'message' => 'Stok berhasil ditambahkan.',
                'new_stock' => $new_stock
            ];
            $stmt->close();
            $stmt_get->close();

        } catch (mysqli_sql_exception $e) {
            $response['message'] = 'Gagal memperbarui database: ' . $e->getMessage();
        }
    } else {
        $response['message'] = 'ID produk atau jumlah tidak valid.';
    }
}

$conn->close();
echo json_encode($response);
?>
