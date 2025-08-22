<?php
// update_harga.php
define('_KASIR_', true);
require_once 'auth_check.php';
require_once 'config/database.php';

// Query diubah untuk mengambil SEMUA produk, karena pencarian akan dilakukan di sisi klien (JavaScript)
$products_result = $conn->prepare("SELECT id, name, price, cost_price, category, stock FROM products ORDER BY name ASC");
$products_result->execute();
$products = $products_result->get_result();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Harga & Stok Massal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 font-sans">
    <div class="container mx-auto p-4 sm:p-6">
        <div class="flex flex-wrap justify-between items-center mb-6 gap-4">
            <h1 class="text-3xl font-bold text-gray-800">Update Produk Massal</h1>
            <a href="admin.php" class="bg-indigo-600 text-white py-2 px-4 rounded-lg shadow-sm hover:bg-indigo-700 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>Kembali ke Dasbor
            </a>
        </div>

        <?php if (isset($_SESSION['update_status'])): ?>
            <div class="bg-<?= $_SESSION['update_status']['type'] === 'success' ? 'green' : 'red' ?>-100 border-l-4 border-<?= $_SESSION['update_status']['type'] === 'success' ? 'green' : 'red' ?>-500 text-<?= $_SESSION['update_status']['type'] === 'success' ? 'green' : 'red' ?>-700 p-4 mb-6 rounded-lg shadow-sm" role="alert">
                <p><?= htmlspecialchars($_SESSION['update_status']['message']) ?></p>
            </div>
            <?php unset($_SESSION['update_status']); ?>
        <?php endif; ?>

        <div class="bg-white p-4 sm:p-6 rounded-xl shadow-lg">
            <div class="relative w-full md:w-2/5 mb-6">
                <input type="text" id="search-input" placeholder="Ketik untuk mencari produk..." class="w-full pl-10 pr-4 py-2 border rounded-lg">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
            </div>

            <form action="aksi_update_harga.php" method="POST">
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="p-3 text-sm font-semibold text-left text-gray-600">Nama Produk</th>
                                <th class="p-3 text-sm font-semibold text-left text-gray-600">Harga Beli Baru</th>
                                <th class="p-3 text-sm font-semibold text-left text-gray-600">Harga Jual Baru</th>
                                <th class="p-3 text-sm font-semibold text-left text-gray-600">Stok Baru</th>
                            </tr>
                        </thead>
                        <tbody id="product-table-body" class="text-gray-700 divide-y">
                            <?php if ($products->num_rows > 0): while ($product = $products->fetch_assoc()): ?>
                                <tr class="product-row hover:bg-gray-50">
                                    <td class="p-3 font-semibold">
                                        <span class="product-name"><?= htmlspecialchars($product['name']) ?></span><br>
                                        <span class="product-category text-xs text-gray-500 font-normal"><?= htmlspecialchars($product['category']) ?></span>
                                        <div class="text-xs text-gray-500 font-normal mt-1">
                                            Beli: Rp<?= number_format($product['cost_price'], 0, ',', '.') ?> | 
                                            Jual: Rp<?= number_format($product['price'], 0, ',', '.') ?> | 
                                            Stok: <?= $product['stock'] ?>
                                        </div>
                                    </td>
                                    <td class="p-3">
                                        <input type="number" name="harga_beli[<?= $product['id'] ?>]" class="w-full max-w-xs px-3 py-1 border rounded-md" placeholder="Beli" min="0">
                                    </td>
                                    <td class="p-3">
                                        <input type="number" name="harga_jual[<?= $product['id'] ?>]" class="w-full max-w-xs px-3 py-1 border rounded-md" placeholder="Jual" min="0">
                                    </td>
                                    <td class="p-3">
                                        <input type="number" name="stok[<?= $product['id'] ?>]" class="w-full max-w-xs px-3 py-1 border rounded-md" placeholder="Stok" min="0">
                                    </td>
                                </tr>
                            <?php endwhile; else: ?>
                                <tr><td colspan="4" class="text-center p-6 text-gray-500">Belum ada produk.</td></tr>
                            <?php endif; ?>
                            <tr id="no-results-row" class="hidden"><td colspan="4" class="text-center p-6 text-gray-500">Produk tidak ditemukan.</td></tr>
                        </tbody>
                    </table>
                </div>
                
                <?php if ($products->num_rows > 0): ?>
                <div class="mt-6 border-t pt-6 flex justify-end">
                    <button type="submit" class="bg-green-600 text-white py-2 px-6 rounded-lg hover:bg-green-700 font-semibold transition-colors flex items-center">
                        <i class="fas fa-save mr-2"></i> Simpan Semua Perubahan
                    </button>
                </div>
                <?php endif; ?>
            </form>
        </div>
    </div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('search-input');
    const productTableBody = document.getElementById('product-table-body');
    const productRows = productTableBody.querySelectorAll('.product-row');
    const noResultsRow = document.getElementById('no-results-row');

    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        let visibleCount = 0;

        productRows.forEach(row => {
            const productName = row.querySelector('.product-name').textContent.toLowerCase();
            const productCategory = row.querySelector('.product-category').textContent.toLowerCase();

            if (productName.includes(searchTerm) || productCategory.includes(searchTerm)) {
                row.style.display = ''; // Tampilkan baris
                visibleCount++;
            } else {
                row.style.display = 'none'; // Sembunyikan baris
            }
        });

        // Tampilkan pesan "tidak ditemukan" jika tidak ada produk yang cocok
        if (visibleCount === 0) {
            noResultsRow.classList.remove('hidden');
        } else {
            noResultsRow.classList.add('hidden');
        }
    });
});
</script>
</body>
</html>
