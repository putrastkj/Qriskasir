<?php
// aksi_import_produk.php

require_once 'auth_check.php';
require_once 'config/database.php';

if (isset($_POST['submit'])) {
    if (isset($_FILES['file_csv']) && $_FILES['file_csv']['error'] == 0) {
        $file = $_FILES['file_csv']['tmp_name'];
        
        // Buka file CSV untuk dibaca
        if (($handle = fopen($file, "r")) !== FALSE) {
            // Lewati baris header
            fgetcsv($handle, 1000, ";");

            $conn->begin_transaction();
            try {
                $stmt = $conn->prepare("INSERT INTO products (name, barcode, cost_price, price, category, stock) VALUES (?, ?, ?, ?, ?, ?)");
                
                $imported_count = 0;
                while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
                    // Pastikan data memiliki 6 kolom
                    if (count($data) == 6) {
                        $name = $data[0];
                        $barcode = $data[1];
                        $cost_price = (float)$data[2];
                        $price = (float)$data[3];
                        $category = $data[4];
                        $stock = (int)$data[5];

                        $stmt->bind_param("ssddsi", $name, $barcode, $cost_price, $price, $category, $stock);
                        $stmt->execute();
                        $imported_count++;
                    }
                }
                
                $conn->commit();
                $stmt->close();
                fclose($handle);

                $_SESSION['import_status'] = ['type' => 'success', 'message' => "Berhasil mengimpor {$imported_count} produk baru."];

            } catch (mysqli_sql_exception $exception) {
                $conn->rollback();
                $_SESSION['import_status'] = ['type' => 'error', 'message' => "Gagal mengimpor data: " . $exception->getMessage()];
            }
        } else {
            $_SESSION['import_status'] = ['type' => 'error', 'message' => 'Gagal membuka file CSV.'];
        }
    } else {
        $_SESSION['import_status'] = ['type' => 'error', 'message' => 'Tidak ada file yang diunggah atau terjadi error saat upload.'];
    }
} else {
    $_SESSION['import_status'] = ['type' => 'error', 'message' => 'Aksi tidak valid.'];
}

header("Location: import_produk.php");
exit();
?>
