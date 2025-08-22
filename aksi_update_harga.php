<?php
// aksi_update_harga.php

require_once 'auth_check.php';
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $cost_prices = $_POST['harga_beli'] ?? [];
    $sell_prices = $_POST['harga_jual'] ?? [];
    $stocks = $_POST['stok'] ?? [];
    $updated_count = 0;

    $conn->begin_transaction();
    try {
        // Siapkan statement terpisah untuk setiap jenis update
        $stmt_cost = $conn->prepare("UPDATE products SET cost_price = ? WHERE id = ?");
        $stmt_sell = $conn->prepare("UPDATE products SET price = ? WHERE id = ?");
        $stmt_stock = $conn->prepare("UPDATE products SET stock = ? WHERE id = ?");

        // Proses update harga beli
        foreach ($cost_prices as $id => $value) {
            if ($value !== '' && is_numeric($value)) {
                $stmt_cost->bind_param("di", $value, $id);
                $stmt_cost->execute();
                $updated_count += $stmt_cost->affected_rows;
            }
        }

        // Proses update harga jual
        foreach ($sell_prices as $id => $value) {
            if ($value !== '' && is_numeric($value)) {
                $stmt_sell->bind_param("di", $value, $id);
                $stmt_sell->execute();
                $updated_count += $stmt_sell->affected_rows;
            }
        }

        // Proses update stok
        foreach ($stocks as $id => $value) {
            if ($value !== '' && is_numeric($value)) {
                $stmt_stock->bind_param("ii", $value, $id);
                $stmt_stock->execute();
                $updated_count += $stmt_stock->affected_rows;
            }
        }
        
        $stmt_cost->close();
        $stmt_sell->close();
        $stmt_stock->close();
        
        $conn->commit();
        
        if ($updated_count > 0) {
            $_SESSION['update_status'] = ['type' => 'success', 'message' => "Berhasil memperbarui data produk."];
        } else {
            $_SESSION['update_status'] = ['type' => 'success', 'message' => "Tidak ada data yang diubah."];
        }

    } catch (mysqli_sql_exception $exception) {
        $conn->rollback();
        $_SESSION['update_status'] = ['type' => 'error', 'message' => "Gagal memperbarui data: " . $exception->getMessage()];
    }

} else {
    $_SESSION['update_status'] = ['type' => 'error', 'message' => "Tidak ada data yang dikirim."];
}

header("Location: update_harga.php");
exit();
?>
