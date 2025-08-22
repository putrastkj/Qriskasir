<?php
// aksi_tahan_transaksi.php
header('Content-Type: application/json');
require_once 'auth_check.php';
require_once 'config/database.php';

$response = ['success' => false, 'message' => 'Aksi tidak valid.'];
$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'hold') {
    $data = json_decode(file_get_contents('php://input'), true);
    $holdName = $data['name'] ?? 'Transaksi ' . date('H:i:s');
    $cartData = json_encode($data['cart']);

    if (json_last_error() === JSON_ERROR_NONE && !empty($cartData)) {
        $stmt = $conn->prepare("INSERT INTO held_orders (hold_name, cart_data) VALUES (?, ?)");
        $stmt->bind_param("ss", $holdName, $cartData);
        if ($stmt->execute()) {
            $response = ['success' => true, 'message' => "Transaksi '$holdName' berhasil ditahan."];
        } else {
            $response['message'] = 'Gagal menyimpan transaksi ke database.';
        }
        $stmt->close();
    } else {
        $response['message'] = 'Data keranjang tidak valid.';
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'list') {
    $result = $conn->query("SELECT id, hold_name, created_at FROM held_orders ORDER BY created_at DESC");
    $held_orders = [];
    while($row = $result->fetch_assoc()) {
        $held_orders[] = $row;
    }
    $response = ['success' => true, 'data' => $held_orders];
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'resume') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = (int)($data['id'] ?? 0);

    if ($id > 0) {
        $stmt_get = $conn->prepare("SELECT cart_data FROM held_orders WHERE id = ?");
        $stmt_get->bind_param("i", $id);
        $stmt_get->execute();
        $result = $stmt_get->get_result();

        if ($cart = $result->fetch_assoc()) {
            $stmt_del = $conn->prepare("DELETE FROM held_orders WHERE id = ?");
            $stmt_del->bind_param("i", $id);
            if ($stmt_del->execute()) {
                $response = ['success' => true, 'cart_data' => json_decode($cart['cart_data'])];
            } else {
                $response['message'] = "Gagal menghapus data lama.";
            }
            $stmt_del->close();
        } else {
            $response['message'] = 'Transaksi tidak ditemukan.';
        }
        $stmt_get->close();
    }
}

$conn->close();
echo json_encode($response);