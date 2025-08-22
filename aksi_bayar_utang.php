<?php
// aksi_bayar_utang.php
define('_KASIR_', true);
require_once 'auth_check.php';
require_once 'config/database.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => 'Permintaan tidak valid.'];
$action = $_GET['action'] ?? '';

// Aksi untuk mengambil detail transaksi yang belum lunas
if ($action === 'get_unpaid' && isset($_GET['customer_id'])) {
    $customer_id = (int)$_GET['customer_id'];
    
    $stmt_orders = $conn->prepare("SELECT id, DATE_FORMAT(order_date, '%d %b %Y') as order_date_formatted, DATE_FORMAT(order_date, '%H:%i') as order_time, total_amount FROM orders WHERE customer_id = ? AND payment_status = 'Belum Lunas' ORDER BY order_date ASC");
    $stmt_orders->bind_param("i", $customer_id);
    $stmt_orders->execute();
    $result_orders = $stmt_orders->get_result();
    
    $orders_with_items = [];
    $stmt_items = $conn->prepare("SELECT product_name, quantity FROM order_items WHERE order_id = ?");
    
    while($order = $result_orders->fetch_assoc()) {
        $order_id = $order['id'];
        $stmt_items->bind_param("i", $order_id);
        $stmt_items->execute();
        $result_items = $stmt_items->get_result();
        
        $items = [];
        while($item = $result_items->fetch_assoc()) {
            $items[] = $item;
        }
        
        $order['items'] = $items;
        $order['total_amount'] = (float)$order['total_amount'];
        $orders_with_items[] = $order;
    }
    
    $stmt_orders->close();
    $stmt_items->close();
    
    $response = ['success' => true, 'orders' => $orders_with_items];
}

// Aksi BARU untuk mengambil riwayat pembayaran
elseif ($action === 'get_payment_history' && isset($_GET['customer_id'])) {
    $customer_id = (int)$_GET['customer_id'];
    $stmt = $conn->prepare("SELECT amount_paid, DATE_FORMAT(payment_date, '%d %b %Y, %H:%i') as payment_date_formatted FROM debt_payments WHERE customer_id = ? ORDER BY payment_date DESC");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $payments = [];
    while($row = $result->fetch_assoc()) {
        $row['amount_paid'] = (float)$row['amount_paid'];
        $payments[] = $row;
    }
    $response = ['success' => true, 'payments' => $payments];
}

// Aksi untuk memproses pembayaran
elseif ($action === 'pay' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_id = isset($_POST['customer_id']) ? (int)$_POST['customer_id'] : 0;
    $amount = isset($_POST['amount']) ? (float)$_POST['amount'] : 0;

    if ($customer_id > 0 && $amount > 0) {
        $conn->begin_transaction();
        try {
            // 1. Catat pembayaran ke tabel riwayat
            $stmt_log = $conn->prepare("INSERT INTO debt_payments (customer_id, amount_paid) VALUES (?, ?)");
            $stmt_log->bind_param("id", $customer_id, $amount);
            $stmt_log->execute();
            $stmt_log->close();

            // 2. Kurangi saldo utang pelanggan
            $stmt_balance = $conn->prepare("UPDATE customers SET balance = balance - ? WHERE id = ?");
            $stmt_balance->bind_param("di", $amount, $customer_id);
            $stmt_balance->execute();
            $stmt_balance->close();

            // 3. Tandai pesanan yang relevan sebagai 'Lunas'
            $unpaid_orders = $conn->query("SELECT id, total_amount FROM orders WHERE customer_id = $customer_id AND payment_status = 'Belum Lunas' ORDER BY order_date ASC");
            $remaining_amount = $amount;
            
            while ($order = $unpaid_orders->fetch_assoc()) {
                if ($remaining_amount <= 0) break;
                $order_total = (float)$order['total_amount'];
                if ($remaining_amount >= $order_total) {
                    $conn->query("UPDATE orders SET payment_status = 'Lunas' WHERE id = " . $order['id']);
                    $remaining_amount -= $order_total;
                }
            }

            // 4. Ambil saldo terbaru dan no HP
            $customer_data_res = $conn->query("SELECT balance, phone FROM customers WHERE id = $customer_id");
            $customer_data = $customer_data_res->fetch_assoc();
            $new_balance = $customer_data['balance'];
            $customer_phone = $customer_data['phone'];

            $conn->commit();
            $response = [
                'success' => true, 
                'message' => 'Pembayaran utang berhasil dicatat.',
                'customer_id' => $customer_id,
                'new_balance' => (float)$new_balance,
                'is_fully_paid' => ((float)$new_balance <= 0),
                'customer_phone' => $customer_phone
            ];
        } catch (mysqli_sql_exception $e) {
            $conn->rollback();
            $response['message'] = 'Gagal mencatat pembayaran: ' . $e->getMessage();
        }
    } else {
        $response['message'] = 'Data tidak valid.';
    }
}

echo json_encode($response);
$conn->close();
?>
