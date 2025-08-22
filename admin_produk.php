<?php
define('_KASIR_', true);
require_once 'auth_check.php';
require_once 'config/database.php';

// --- STATISTIK INVENTARIS ---
$inventory_summary_sql = "SELECT COUNT(id) as total_items, SUM(stock) as total_stock FROM products";
$summary_result = $conn->query($inventory_summary_sql);
$inventory_summary = $summary_result->fetch_assoc();
$total_items = $inventory_summary['total_items'] ?? 0;
$total_stock = $inventory_summary['total_stock'] ?? 0;
// -----------------------------

// Query untuk mengambil SEMUA produk, diurutkan berdasarkan nama
$data_sql = "SELECT * FROM products ORDER BY name ASC";
$stmt = $conn->prepare($data_sql);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Produk</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 font-sans">
    <div class="container mx-auto p-4 sm:p-6">
        <div class="flex flex-wrap justify-between items-center mb-6 gap-4">
            <h1 class="text-3xl font-bold text-gray-800">Manajemen Produk</h1>
            <a href="admin.php" class="bg-indigo-600 text-white py-2 px-4 rounded-lg shadow-sm hover:bg-indigo-700 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>Kembali ke Dasbor
            </a>
        </div>

        <!-- Kartu Statistik Inventaris -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div class="bg-white p-6 rounded-xl shadow-lg flex items-center space-x-4">
                <div class="bg-blue-500 text-white rounded-full w-16 h-16 flex items-center justify-center"><i class="fas fa-boxes text-3xl"></i></div>
                <div>
                    <p class="text-gray-500 text-sm">Total Jenis Produk</p>
                    <p class="text-2xl font-bold text-gray-800"><?= $total_items ?> item</p>
                </div>
            </div>
            <div class="bg-white p-6 rounded-xl shadow-lg flex items-center space-x-4">
                <div class="bg-green-500 text-white rounded-full w-16 h-16 flex items-center justify-center"><i class="fas fa-warehouse text-3xl"></i></div>
                <div>
                    <p class="text-gray-500 text-sm">Total Stok Semua Barang</p>
                    <p class="text-2xl font-bold text-gray-800"><?= $total_stock ?> pcs</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white p-4 sm:p-6 rounded-xl shadow-lg">
            <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
                <div class="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
                    <a href="form_produk.php" class="w-full sm:w-auto bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition-colors flex items-center justify-center">
                        <i class="fas fa-plus mr-2"></i> Tambah Produk
                    </a>
                    <a href="import_produk.php" class="w-full sm:w-auto bg-green-600 text-white py-2 px-4 rounded-lg hover:bg-green-700 transition-colors flex items-center justify-center">
                        <i class="fas fa-file-upload mr-2"></i> Impor
                    </a>
                    <a href="export_produk.php" class="w-full sm:w-auto bg-purple-600 text-white py-2 px-4 rounded-lg hover:bg-purple-700 transition-colors flex items-center justify-center">
                        <i class="fas fa-file-export mr-2"></i> Ekspor
                    </a>
                </div>
                <!-- Form pencarian dihapus, diganti dengan input biasa -->
                <div class="relative w-full md:w-2/5">
                    <input type="text" id="search-input" placeholder="Ketik untuk mencari produk..." class="w-full pl-10 pr-4 py-2 border rounded-lg">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                </div>
            </div>

            <!-- Daftar Produk Berbasis Kartu -->
            <div id="product-list" class="space-y-4">
                <?php if ($result->num_rows > 0) : while ($product = $result->fetch_assoc()) : ?>
                    <div class="product-item bg-gray-50 border border-gray-200 rounded-lg p-4 flex flex-col sm:flex-row items-start sm:items-center gap-4">
                        <img src="<?= htmlspecialchars($product['image'] ?? 'https://via.placeholder.com/150') ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="w-full sm:w-20 h-28 sm:h-20 object-cover rounded-md flex-shrink-0">
                        <div class="flex-grow">
                            <span class="product-category text-xs font-semibold py-1 px-2.5 rounded-full bg-blue-100 text-blue-800"><?= htmlspecialchars($product['category'] ?? 'Lainnya') ?></span>
                            <h3 class="product-name font-bold text-lg text-gray-800 mt-1"><?= htmlspecialchars($product['name']) ?></h3>
                            <div class="flex flex-wrap items-center gap-x-6 gap-y-1 text-sm mt-2">
                                <div>
                                    <span class="text-xs text-gray-500">Harga Beli</span>
                                    <p class="font-semibold text-red-600">Rp<?= number_format($product['cost_price'], 0, ',', '.') ?></p>
                                </div>
                                <div>
                                    <span class="text-xs text-gray-500">Harga Jual</span>
                                    <p class="font-semibold text-green-600">Rp<?= number_format($product['price'], 0, ',', '.') ?></p>
                                </div>
                                <div>
                                    <?php 
                                        $stock = $product['stock'];
                                        $stock_class = 'bg-green-100 text-green-800';
                                        if ($stock == 0) { $stock_class = 'bg-red-100 text-red-800'; }
                                        elseif ($stock <= 10) { $stock_class = 'bg-yellow-100 text-yellow-800'; }
                                    ?>
                                    <span class="text-xs text-gray-500">Stok</span>
                                    <p class="font-bold py-1 px-3 rounded-full <?= $stock_class ?> inline-block">
                                        <?= $stock ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="flex-shrink-0 flex sm:flex-col justify-start items-stretch gap-2 w-full sm:w-auto">
                            <a href="form_produk.php?id=<?= $product['id'] ?>" class="w-full text-center bg-yellow-400 text-yellow-900 hover:bg-yellow-500 font-semibold py-2 px-4 rounded-md text-sm transition-colors"><i class="fas fa-edit mr-2"></i>Edit</a>
                            <a href="aksi_produk.php?action=delete&id=<?= $product['id'] ?>" class="w-full text-center bg-red-500 text-white hover:bg-red-600 font-semibold py-2 px-4 rounded-md text-sm transition-colors" onclick="return confirm('Yakin ingin menghapus produk ini?')"><i class="fas fa-trash-alt mr-2"></i>Hapus</a>
                        </div>
                    </div>
                <?php endwhile; endif; ?>
                <!-- Pesan jika tidak ada hasil -->
                <div id="no-results-message" class="text-center p-10 text-gray-500 border-2 border-dashed rounded-lg hidden">
                    <i class="fas fa-search fa-3x mb-3"></i>
                    <p class="font-semibold">Produk tidak ditemukan.</p>
                </div>
            </div>
        </div>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('search-input');
    const productList = document.getElementById('product-list');
    const productItems = productList.querySelectorAll('.product-item');
    const noResultsMessage = document.getElementById('no-results-message');

    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        let visibleCount = 0;

        productItems.forEach(item => {
            const productName = item.querySelector('.product-name').textContent.toLowerCase();
            const productCategory = item.querySelector('.product-category').textContent.toLowerCase();

            if (productName.includes(searchTerm) || productCategory.includes(searchTerm)) {
                item.style.display = 'flex';
                visibleCount++;
            } else {
                item.style.display = 'none';
            }
        });

        // Tampilkan pesan "tidak ditemukan" jika tidak ada produk yang cocok
        if (visibleCount === 0) {
            noResultsMessage.style.display = 'block';
        } else {
            noResultsMessage.style.display = 'none';
        }
    });
});
</script>
</body>
</html>
