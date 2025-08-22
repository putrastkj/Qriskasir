<?php
// proses_pesanan.php
define('_KASIR_', true);

header('Content-Type: application/json');
require_once 'auth_check.php';
require_once 'config/database.php';

$response = ['success' => false, 'message' => 'Data tidak valid.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cart_data'])) {
    $cart = json_decode($_POST['cart_data'], true);

    if (json_last_error() === JSON_ERROR_NONE && is_array($cart) && !empty($cart)) {
        $customerId = !empty($_POST['customer_id']) ? (int)$_POST['customer_id'] : null;
        $globalDiscount = isset($_POST['global_discount']) ? (float)$_POST['global_discount'] : 0;
        $adminFee = isset($_POST['admin_fee']) ? (float)$_POST['admin_fee'] : 0;
        $paymentMethod = isset($_POST['payment_method']) ? $_POST['payment_method'] : 'QRIS';
        $cashAmount = isset($_POST['cash_amount']) ? (float)$_POST['cash_amount'] : 0;
        $changeAmount = isset($_POST['change_amount']) ? (float)$_POST['change_amount'] : 0;

        $customerName = 'Pelanggan';
        if ($customerId) {
            $stmt_cust = $conn->prepare("SELECT name FROM customers WHERE id = ?");
            $stmt_cust->bind_param("i", $customerId);
            $stmt_cust->execute();
            $cust_result = $stmt_cust->get_result();
            if ($cust_row = $cust_result->fetch_assoc()) {
                $customerName = $cust_row['name'];
            }
            $stmt_cust->close();
        }

        // Jika metode Utang tapi tidak ada pelanggan, gagalkan.
        if ($paymentMethod === 'Utang' && !$customerId) {
            $response['message'] = 'Pelanggan harus dipilih untuk transaksi utang.';
            echo json_encode($response);
            exit();
        }

        $subtotal = 0;
        $totalItemDiscount = 0;
        foreach ($cart as $item) {
            $subtotal += (float)$item['price'] * (int)$item['quantity'];
            $totalItemDiscount += (float)($item['discount'] ?? 0);
        }
        $totalAmount = $subtotal - $totalItemDiscount - $globalDiscount + $adminFee;
        $paymentStatus = ($paymentMethod === 'Utang') ? 'Belum Lunas' : 'Lunas';

        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("INSERT INTO orders (customer_id, customer_name, total_amount, cash_amount, change_amount, global_discount, admin_fee, payment_method, payment_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isddddsss", $customerId, $customerName, $totalAmount, $cashAmount, $changeAmount, $globalDiscount, $adminFee, $paymentMethod, $paymentStatus);
            $stmt->execute();
            $orderId = $stmt->insert_id;
            $stmt->close();
            
            // Jika metode Utang, update saldo pelanggan
            if ($paymentMethod === 'Utang') {
                $stmt_balance = $conn->prepare("UPDATE customers SET balance = balance + ? WHERE id = ?");
                $stmt_balance->bind_param("di", $totalAmount, $customerId);
                $stmt_balance->execute();
                $stmt_balance->close();
            }

            $stmt_items = $conn->prepare("INSERT INTO order_items (order_id, product_name, quantity, price, cost_price, discount) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt_get_cost = $conn->prepare("SELECT cost_price FROM products WHERE id = ?");
            foreach ($cart as $item) {
                $cost_price = 0;
                if (is_numeric($item['id'])) {
                    $stmt_get_cost->bind_param("i", $item['id']);
                    $stmt_get_cost->execute();
                    $cost_result = $stmt_get_cost->get_result();
                    if ($cost_row = $cost_result->fetch_assoc()) {
                        $cost_price = $cost_row['cost_price'];
                    }
                }
                $stmt_items->bind_param("isidds", $orderId, $item['name'], $item['quantity'], $item['price'], $cost_price, $item['discount']);
                $stmt_items->execute();
            }
            $stmt_items->close();
            $stmt_get_cost->close();

            $stmt_update_stock = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
            foreach ($cart as $item) {
                if (is_numeric($item['id'])) {
                    $stmt_update_stock->bind_param("ii", $item['quantity'], $item['id']);
                    $stmt_update_stock->execute();
                }
            }
            $stmt_update_stock->close();

            $conn->commit();
            $response = ['success' => true, 'message' => 'Pesanan berhasil disimpan.', 'order_id' => $orderId];

        } catch (mysqli_sql_exception $exception) {
            $conn->rollback();
            $response = ['success' => false, 'message' => 'Gagal menyimpan ke database: ' . $exception->getMessage()];
        }
    } else {
        $response['message'] = 'Keranjang kosong atau data tidak valid.';
    }
}

$conn->close();
echo json_encode($response);
?>
