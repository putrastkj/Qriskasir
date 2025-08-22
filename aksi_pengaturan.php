<?php
// aksi_pengaturan.php (Diperbaiki)
define('_KASIR_', true);
require_once 'admin_only_check.php';
require_once 'config/database.php';

$action = $_GET['action'] ?? '';

// --- FUNGSI BACKUP DATABASE ---
if ($action === 'backup') {
    $tables = array();
    $result = $conn->query("SHOW TABLES");
    while ($row = $result->fetch_row()) {
        $tables[] = $row[0];
    }

    $return = '';
    foreach ($tables as $table) {
        $result = $conn->query("SELECT * FROM `" . $table . "`");
        $num_fields = $result->field_count;

        $return .= 'DROP TABLE IF EXISTS `' . $table . '`;';
        $row2 = $conn->query("SHOW CREATE TABLE `" . $table . "`")->fetch_row();
        $return .= "\n\n" . $row2[1] . ";\n\n";

        while ($row = $result->fetch_row()) {
            $return .= 'INSERT INTO `' . $table . '` VALUES(';
            for ($j = 0; $j < $num_fields; $j++) {
                // Perbaikan untuk menangani nilai NULL
                if (is_null($row[$j])) {
                    $return .= 'NULL';
                } else {
                    $row[$j] = addslashes($row[$j]);
                    $row[$j] = preg_replace("/\n/", "\\n", $row[$j]);
                    $return .= '"' . $row[$j] . '"';
                }

                if ($j < ($num_fields - 1)) {
                    $return .= ',';
                }
            }
            $return .= ");\n";
        }
        $return .= "\n\n\n";
    }

    // Simpan file
    $file_name = 'db-backup-' . $dbname . '-' . time() . '.sql';
    header('Content-Type: application/octet-stream');
    header('Content-Transfer-Encoding: Binary');
    header('Content-Disposition: attachment; filename="' . $file_name . '"');
    echo $return;
    exit();
}

// --- FUNGSI HAPUS DATA TRANSAKSI ---
if ($action === 'delete_transactions' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn->begin_transaction();
    try {
        $conn->query("SET FOREIGN_KEY_CHECKS = 0");
        $conn->query("TRUNCATE TABLE order_items");
        $conn->query("TRUNCATE TABLE orders");
        $conn->query("TRUNCATE TABLE held_orders");
        $conn->query("SET FOREIGN_KEY_CHECKS = 1");
        
        $conn->commit();
        $_SESSION['action_status'] = ['type' => 'success', 'message' => 'Semua data transaksi berhasil dihapus.'];
    } catch (mysqli_sql_exception $exception) {
        $conn->rollback();
        $_SESSION['action_status'] = ['type' => 'error', 'message' => 'Gagal menghapus data: ' . $exception->getMessage()];
    }
    header("Location: pengaturan.php");
    exit();
}

$_SESSION['action_status'] = ['type' => 'error', 'message' => 'Aksi tidak valid.'];
header("Location: pengaturan.php");
exit();
?>
