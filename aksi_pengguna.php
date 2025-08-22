<?php
// aksi_pengguna.php

// Definisikan konstanta keamanan SEBELUM memanggil file lain.
define('_KASIR_', true);

require_once 'admin_only_check.php';
require_once 'config/database.php';

// Logika untuk Tambah atau Edit Pengguna
if (isset($_POST['submit'])) {
    $id = (int)$_POST['id'];
    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password'];
    $role = $_POST['role'];

    if ($id > 0) { // Proses Edit
        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql = "UPDATE users SET username = ?, password = ?, role = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssi", $username, $hashed_password, $role, $id);
        } else {
            $sql = "UPDATE users SET username = ?, role = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssi", $username, $role, $id);
        }
    } else { // Proses Tambah
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (username, password, role) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $username, $hashed_password, $role);
    }

    if ($stmt->execute()) {
        header("Location: admin_pengguna.php");
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }
    $stmt->close();
}

// Logika untuk Hapus Pengguna
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];

    // Cegah pengguna menghapus dirinya sendiri
    if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $id) {
        die("Error: Anda tidak dapat menghapus akun Anda sendiri.");
    }

    $sql = "DELETE FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        header("Location: admin_pengguna.php");
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }
    $stmt->close();
}

$conn->close();
?>
