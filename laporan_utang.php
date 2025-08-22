<?php
define('_KASIR_', true);
require_once 'auth_check.php';
require_once 'config/database.php';

// Ambil semua pelanggan yang memiliki saldo utang (balance > 0)
$customers_with_debt = $conn->query("SELECT id, name, phone, balance FROM customers WHERE balance > 0 ORDER BY name ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Piutang Pelanggan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 font-sans">
    <div class="container mx-auto p-4 sm:p-6">
        <div class="flex flex-wrap justify-between items-center mb-6 gap-4">
            <h1 class="text-3xl font-bold text-gray-800">Laporan Piutang</h1>
            <a href="admin.php" class="bg-indigo-600 text-white py-2 px-4 rounded-lg shadow-sm hover:bg-indigo-700 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>Kembali ke Dasbor
            </a>
        </div>

        <div class="bg-white p-4 sm:p-6 rounded-xl shadow-lg">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Daftar Pelanggan dengan Utang</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="p-3 text-sm font-semibold text-left text-gray-600">Nama Pelanggan</th>
                            <th class="p-3 text-sm font-semibold text-left text-gray-600">No. Telepon</th>
                            <th class="p-3 text-sm font-semibold text-right text-gray-600">Total Utang</th>
                            <th class="p-3 text-sm font-semibold text-center text-gray-600">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-700 divide-y">
                        <?php if ($customers_with_debt->num_rows > 0): while ($customer = $customers_with_debt->fetch_assoc()): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="p-3 font-semibold"><?= htmlspecialchars($customer['name']) ?></td>
                                <td class="p-3"><?= htmlspecialchars($customer['phone']) ?></td>
                                <td class="p-3 text-right font-bold text-red-600">Rp<?= number_format($customer['balance'], 0, ',', '.') ?></td>
                                <td class="p-3 text-center">
                                    <a href="detail_utang.php?customer_id=<?= $customer['id'] ?>" class="bg-green-500 text-white py-1 px-3 rounded-md text-sm hover:bg-green-600">
                                        <i class="fas fa-money-bill-wave mr-1"></i> Bayar
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; else: ?>
                            <tr><td colspan="4" class="text-center p-6 text-gray-500">Tidak ada pelanggan yang memiliki utang.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
