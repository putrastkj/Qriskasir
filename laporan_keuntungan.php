<?php
// laporan_keuntungan.php
define('_KASIR_', true);
require_once 'auth_check.php';
require_once 'config/database.php';

$filter_periode = $_GET['periode'] ?? '7hari';
// Kondisi default: hanya tampilkan yang lunas
$where_conditions = ["o.payment_status = 'Lunas'"];
$params = [];
$types = '';

switch ($filter_periode) {
    case 'hari_ini': $where_conditions[] = "DATE(o.order_date) = CURDATE()"; break;
    case '7hari': $where_conditions[] = "o.order_date >= CURDATE() - INTERVAL 6 DAY"; break;
    case 'bulan_ini': $where_conditions[] = "YEAR(o.order_date) = YEAR(CURDATE()) AND MONTH(o.order_date) = MONTH(CURDATE())"; break;
}
$where_clause = " WHERE " . implode(" AND ", $where_conditions);

$sql = "SELECT 
            SUM(oi.quantity * oi.price) as total_omzet,
            SUM(oi.quantity * oi.cost_price) as total_modal,
            SUM(oi.quantity * (oi.price - oi.cost_price)) as total_keuntungan,
            COUNT(DISTINCT o.id) as total_transaksi
        FROM order_items oi
        JOIN orders o ON oi.order_id = o.id
        $where_clause";
$stmt = $conn->prepare($sql);
if(!empty($params)) $stmt->bind_param($types, ...$params);
$stmt->execute();
$summary = $stmt->get_result()->fetch_assoc();

$sql_top_profit = "SELECT 
                    oi.product_name,
                    SUM(oi.quantity) as total_terjual,
                    SUM(oi.quantity * (oi.price - oi.cost_price)) as keuntungan_produk
                   FROM order_items oi
                   JOIN orders o ON oi.order_id = o.id
                   $where_clause
                   GROUP BY oi.product_name
                   ORDER BY keuntungan_produk DESC
                   LIMIT 10";
$stmt_top = $conn->prepare($sql_top_profit);
if(!empty($params)) $stmt_top->bind_param($types, ...$params);
$stmt_top->execute();
$top_products = $stmt_top->get_result();

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Keuntungan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 font-sans">
    <div class="container mx-auto p-4 sm:p-6">
        <div class="flex flex-wrap justify-between items-center mb-6 gap-4">
             <h1 class="text-3xl font-bold text-gray-800">Laporan Keuntungan</h1>
             <a href="admin.php" class="bg-indigo-600 text-white py-2 px-4 rounded-lg shadow-sm hover:bg-indigo-700">
                <i class="fas fa-arrow-left mr-2"></i>Kembali
            </a>
        </div>

        <div class="bg-white p-4 rounded-xl shadow-lg mb-6">
            <form method="GET" class="flex flex-col sm:flex-row items-center gap-4">
                <div>
                    <label for="periode" class="block text-sm font-medium text-gray-700">Pilih Periode</label>
                    <select id="periode" name="periode" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm">
                        <option value="hari_ini" <?= $filter_periode == 'hari_ini' ? 'selected' : '' ?>>Hari Ini</option>
                        <option value="7hari" <?= $filter_periode == '7hari' ? 'selected' : '' ?>>7 Hari Terakhir</option>
                        <option value="bulan_ini" <?= $filter_periode == 'bulan_ini' ? 'selected' : '' ?>>Bulan Ini</option>
                    </select>
                </div>
                <button type="submit" class="w-full sm:w-auto mt-6 bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700">Terapkan</button>
            </form>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-blue-500 text-white p-6 rounded-xl shadow-lg"><h2 class="text-lg font-semibold">Total Omzet</h2><p class="text-3xl font-bold mt-2">Rp<?= number_format($summary['total_omzet'] ?? 0, 0, ',', '.') ?></p></div>
            <div class="bg-red-500 text-white p-6 rounded-xl shadow-lg"><h2 class="text-lg font-semibold">Total Modal</h2><p class="text-3xl font-bold mt-2">Rp<?= number_format($summary['total_modal'] ?? 0, 0, ',', '.') ?></p></div>
            <div class="bg-green-500 text-white p-6 rounded-xl shadow-lg"><h2 class="text-lg font-semibold">Estimasi Keuntungan</h2><p class="text-3xl font-bold mt-2">Rp<?= number_format($summary['total_keuntungan'] ?? 0, 0, ',', '.') ?></p></div>
        </div>

        <div class="bg-white p-6 rounded-xl shadow-lg">
            <h2 class="text-xl font-bold text-gray-700 mb-4">Top 10 Produk Paling Menguntungkan</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="p-3 text-sm font-semibold text-left text-gray-600">Produk</th>
                            <th class="p-3 text-sm font-semibold text-center text-gray-600">Terjual</th>
                            <th class="p-3 text-sm font-semibold text-right text-gray-600">Total Keuntungan</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-700 divide-y">
                        <?php if ($top_products->num_rows > 0): while($row = $top_products->fetch_assoc()): ?>
                        <tr>
                            <td class="p-3 font-semibold"><?= htmlspecialchars($row['product_name']) ?></td>
                            <td class="p-3 text-center"><?= $row['total_terjual'] ?></td>
                            <td class="p-3 text-right font-bold text-green-600">Rp<?= number_format($row['keuntungan_produk'], 0, ',', '.') ?></td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr><td colspan="3" class="text-center p-6 text-gray-500">Tidak ada data penjualan pada periode ini.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
