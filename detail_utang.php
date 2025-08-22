<?php
define('_KASIR_', true);
require_once 'auth_check.php';
require_once 'config/database.php';

$customer_id = isset($_GET['customer_id']) ? (int)$_GET['customer_id'] : 0;
if ($customer_id === 0) {
    header("Location: laporan_utang.php");
    exit();
}

// Ambil data pelanggan
$stmt_customer = $conn->prepare("SELECT * FROM customers WHERE id = ?");
$stmt_customer->bind_param("i", $customer_id);
$stmt_customer->execute();
$customer = $stmt_customer->get_result()->fetch_assoc();

// Ambil riwayat transaksi yang belum lunas
$stmt_orders = $conn->prepare("SELECT * FROM orders WHERE customer_id = ? AND payment_status = 'Belum Lunas' ORDER BY order_date DESC");
$stmt_orders->bind_param("i", $customer_id);
$stmt_orders->execute();
$unpaid_orders = $stmt_orders->get_result();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Utang Pelanggan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 font-sans">
    <div class="container mx-auto p-4 sm:p-6">
        <div class="max-w-4xl mx-auto">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">Detail Utang</h1>
                    <p class="text-gray-600 font-semibold text-lg"><?= htmlspecialchars($customer['name']) ?></p>
                </div>
                <a href="laporan_utang.php" class="text-sm text-gray-600 hover:text-blue-600"><i class="fas fa-arrow-left mr-2"></i>Kembali</a>
            </div>

            <?php if (isset($_SESSION['action_status'])): ?>
                <div class="bg-<?= $_SESSION['action_status']['type'] === 'success' ? 'green' : 'red' ?>-100 border-l-4 p-4 mb-6 rounded-lg" role="alert">
                    <p><?= htmlspecialchars($_SESSION['action_status']['message']) ?></p>
                </div>
                <?php unset($_SESSION['action_status']); ?>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Form Pembayaran -->
                <div class="lg:col-span-1">
                    <div class="bg-white p-6 rounded-xl shadow-lg">
                        <h2 class="text-xl font-bold text-gray-800 mb-4">Bayar Utang</h2>
                        <div class="mb-4">
                            <p class="text-gray-600">Total Utang Saat Ini:</p>
                            <p class="text-3xl font-bold text-red-600">Rp<?= number_format($customer['balance'], 0, ',', '.') ?></p>
                        </div>
                        <form action="aksi_bayar_utang.php" method="POST">
                            <input type="hidden" name="customer_id" value="<?= $customer_id ?>">
                            <div>
                                <label for="amount" class="block text-gray-700 font-semibold mb-2">Jumlah Pembayaran</label>
                                <input type="number" name="amount" id="amount" class="w-full px-4 py-2 border rounded-lg" required min="1" max="<?= $customer['balance'] ?>">
                            </div>
                            <div class="mt-6">
                                <button type="submit" class="w-full bg-green-600 text-white py-3 rounded-lg hover:bg-green-700 font-semibold">
                                    <i class="fas fa-check mr-2"></i>Konfirmasi Pembayaran
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Riwayat Transaksi Utang -->
                <div class="lg:col-span-2">
                    <div class="bg-white p-6 rounded-xl shadow-lg">
                        <h2 class="text-xl font-bold text-gray-800 mb-4">Riwayat Transaksi Belum Lunas</h2>
                        <div class="space-y-3 max-h-96 overflow-y-auto">
                            <?php if ($unpaid_orders->num_rows > 0): while($order = $unpaid_orders->fetch_assoc()): ?>
                            <div class="bg-gray-50 border p-3 rounded-lg">
                                <div class="flex justify-between items-center">
                                    <p class="font-semibold">Pesanan #<?= $order['id'] ?></p>
                                    <p class="font-bold text-lg">Rp<?= number_format($order['total_amount'], 0, ',', '.') ?></p>
                                </div>
                                <p class="text-xs text-gray-500"><?= date('d M Y, H:i', strtotime($order['order_date'])) ?></p>
                            </div>
                            <?php endwhile; else: ?>
                            <p class="text-gray-500 text-center">Tidak ada riwayat utang.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
