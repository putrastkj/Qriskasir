<?php
// aksi_kategori.php
define('_KASIR_', true);
require_once 'auth_check.php';
require_once 'config/database.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $conn->real_escape_string($_POST['name']);
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    if ($action === 'add') {
        $stmt = $conn->prepare("INSERT INTO categories (name) VALUES (?)");
        $stmt->bind_param("s", $name);
        if ($stmt->execute()) {
            $_SESSION['action_status'] = ['type' => 'success', 'message' => 'Kategori baru berhasil ditambahkan.'];
        } else {
            $_SESSION['action_status'] = ['type' => 'error', 'message' => 'Gagal menambahkan kategori: ' . $stmt->error];
        }
        $stmt->close();
    } elseif ($action === 'update' && $id > 0) {
        // Ambil nama kategori lama sebelum diupdate
        $old_name_res = $conn->query("SELECT name FROM categories WHERE id = $id");
        $old_name = $old_name_res->fetch_assoc()['name'];

        $conn->begin_transaction();
        try {
            // Update tabel categories
            $stmt1 = $conn->prepare("UPDATE categories SET name = ? WHERE id = ?");
            $stmt1->bind_param("si", $name, $id);
            $stmt1->execute();
            $stmt1->close();

            // Update tabel products
            $stmt2 = $conn->prepare("UPDATE products SET category = ? WHERE category = ?");
            $stmt2->bind_param("ss", $name, $old_name);
            $stmt2->execute();
            $stmt2->close();
            
            $conn->commit();
            $_SESSION['action_status'] = ['type' => 'success', 'message' => 'Kategori berhasil diperbarui.'];
        } catch (mysqli_sql_exception $e) {
            $conn->rollback();
            $_SESSION['action_status'] = ['type' => 'error', 'message' => 'Gagal memperbarui kategori: ' . $e->getMessage()];
        }
    }
} elseif ($action === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    // Ambil nama kategori yang akan dihapus
    $old_name_res = $conn->query("SELECT name FROM categories WHERE id = $id");
    $old_name = $old_name_res->fetch_assoc()['name'];

    $conn->begin_transaction();
    try {
        // Hapus dari tabel categories
        $stmt1 = $conn->prepare("DELETE FROM categories WHERE id = ?");
        $stmt1->bind_param("i", $id);
        $stmt1->execute();
        $stmt1->close();

        // Ganti kategori produk terkait menjadi 'Lainnya'
        $stmt2 = $conn->prepare("UPDATE products SET category = 'Lainnya' WHERE category = ?");
        $stmt2->bind_param("s", $old_name);
        $stmt2->execute();
        $stmt2->close();
        
        $conn->commit();
        $_SESSION['action_status'] = ['type' => 'success', 'message' => 'Kategori berhasil dihapus.'];
    } catch (mysqli_sql_exception $e) {
        $conn->rollback();
        $_SESSION['action_status'] = ['type' => 'error', 'message' => 'Gagal menghapus kategori: ' . $e->getMessage()];
    }
}

header("Location: admin_kategori.php");
exit();
?>
