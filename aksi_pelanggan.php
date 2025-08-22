<?php
// aksi_pelanggan.php

// Definisikan konstanta keamanan SEBELUM memanggil file lain.
define('_KASIR_', true);

require_once 'config/database.php';
require_once 'auth_check.php';

// Logika untuk Tambah atau Edit Pelanggan
if (isset($_POST['submit'])) {
    $id = (int)$_POST['id'];
    $name = $conn->real_escape_string($_POST['name']);
    $phone = $conn->real_escape_string($_POST['phone']);
    $email = $conn->real_escape_string($_POST['email']);
    $address = $conn->real_escape_string($_POST['address']);

    if ($id > 0) { // Proses Edit
        $sql = "UPDATE customers SET name = ?, phone = ?, email = ?, address = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssi", $name, $phone, $email, $address, $id);
    } else { // Proses Tambah
        $sql = "INSERT INTO customers (name, phone, email, address) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $name, $phone, $email, $address);
    }

    if ($stmt->execute()) {
        header("Location: admin_pelanggan.php");
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }
    $stmt->close();
}

// Logika untuk Hapus Pelanggan
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $sql = "DELETE FROM customers WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        header("Location: admin_pelanggan.php");
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }
    $stmt->close();
}

$conn->close();
?>
