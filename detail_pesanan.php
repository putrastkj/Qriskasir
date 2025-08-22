<?php
// detail_pesanan.php
require_once 'config/database.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: laporan_penjualan.php");
    exit();
}

$order_id = (int)$_GET['id'];

// Ambil data pesanan utama
$stmt_order = $conn->prepare("SELECT * FROM orders WHERE id = ?");
$stmt_order->bind_param("i", $order_id);
$stmt_order->execute();
$order_result = $stmt_order->get_result();
if ($order_result->num_rows === 0) {
    die("Pesanan tidak ditemukan.");
}
$order = $order_result->fetch_assoc();

// Ambil item-item dalam pesanan
$stmt_items = $conn->prepare("SELECT * FROM order_items WHERE order_id = ?");
$stmt_items->bind_param("i", $order_id);
$stmt_items->execute();
$items_result = $stmt_items->get_result();
?>

<?php require_once 'auth_check.php'; ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pesanan #<?= $order_id ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 font-sans">
    <div class="container mx-auto p-6">
        <div class="max-w-3xl mx-auto bg-white p-8 rounded-lg shadow-md">
            <div class="flex justify-between items-center border-b pb-4 mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Detail Pesanan <span class="font-mono">#<?= $order_id ?></span></h1>
                    <p class="text-gray-600">Tanggal: <?= date('d F Y, H:i', strtotime($order['order_date'])) ?></p>
                </div>
                <a href="laporan_penjualan.php" class="text-gray-600 hover:text-gray-800">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
            </div>

            <div class="mb-6">
                <h2 class="text-lg font-semibold text-gray-700">Pelanggan:</h2>
                <p class="text-gray-800"><?= htmlspecialchars($order['customer_name']) ?></p>
            </div>

            <h2 class="text-lg font-semibold text-gray-700 mb-2">Rincian Item:</h2>
            <div class="rounded-lg shadow-md overflow-hidden">
                <table class="min-w-full bg-white">
                    <thead class="bg-gray-200 text-gray-600 uppercase text-sm">
                        <tr>
                            <th class="py-3 px-4 text-left">Nama Produk</th>
                            <th class="py-3 px-4 text-center">Jumlah</th>
                            <th class="py-3 px-4 text-right">Harga Satuan</th>
                            <th class="py-3 px-4 text-right">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-700">
                        <?php while($item = $items_result->fetch_assoc()): ?>
                        <tr class="border-b">
                            <td class="py-3 px-4"><?= htmlspecialchars($item['product_name']) ?></td>
                            <td class="py-3 px-4 text-center"><?= $item['quantity'] ?></td>
                            <td class="py-3 px-4 text-right">Rp<?= number_format($item['price'], 0, ',', '.') ?></td>
                            <td class="py-3 px-4 text-right">Rp<?= number_format($item['price'] * $item['quantity'], 0, ',', '.') ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <div class="flex justify-end mt-6">
                <div class="w-full md:w-1/3">
                    <div class="flex justify-between text-lg font-bold bg-gray-800 text-white p-4 rounded-lg">
                        <span>Total</span>
                        <span>Rp<?= number_format($order['total_amount'], 0, ',', '.') ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>