<?php
// index.php
define('_KASIR_', true);
require_once 'auth_check.php';
require_once 'config/database.php';

// Ambil semua produk dari database
$result_products = $conn->query("SELECT * FROM products ORDER BY name ASC");
$products = [];
if ($result_products->num_rows > 0) {
    while ($row = $result_products->fetch_assoc()) {
        $products[] = $row;
    }
}

// Ambil semua kategori unik dari database
$categories_result = $conn->query("SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != '' ORDER BY category ASC");
$categories = [];
while ($row = $categories_result->fetch_assoc()) {
    $categories[] = $row['category'];
}

// Ambil kode QRIS statis dari tabel settings
$qris_result = $conn->query("SELECT setting_value FROM settings WHERE setting_key = 'qris_static_code'");
$qris_static_code = $qris_result->fetch_assoc()['setting_value'] ?? '';

$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aplikasi Kasir</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcode/1.5.0/qrcode.min.js"></script>
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
    <style>
        .toast { padding: 1rem 1.5rem; border-radius: 0.375rem; color: white; opacity: 0; transform: translateX(100%); transition: all 0.4s ease-in-out; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1); min-width: 250px; }
        .toast.show { opacity: 1; transform: translateX(0); }
        .toast.success { background-color: #28a745; }
        .toast.error { background-color: #dc3545; }
        .modal-overlay { transition: opacity 0.3s ease; }
        .tab-btn.active {
            border-bottom-color: #3b82f6;
            color: #3b82f6;
            font-weight: 600;
        }
        .payment-method-btn {
            padding: 0.75rem 0.5rem; border: 1px solid #D1D5DB; border-radius: 0.5rem;
            background-color: #FFFFFF; color: #374151; font-weight: 600;
            transition: all 0.2s ease-in-out; display: flex; align-items: center;
            justify-content: center; font-size: 0.875rem;
        }
        .payment-method-btn.active {
            background-color: #3B82F6; color: #FFFFFF; border-color: #3B82F6;
        }
        .payment-method-btn:disabled {
            background-color: #F3F4F6;
            color: #9CA3AF;
            cursor: not-allowed;
        }
    </style>
</head>
<body class="bg-gray-50 font-sans">

    <div class="p-4 bg-white shadow-md md:hidden">
        <div class="flex justify-between items-center">
             <h1 class="text-xl font-bold text-gray-800">Kasir</h1>
             <div>
                <button id="open-customer-display-btn-mobile" class="text-green-600 mr-4"><i class="fas fa-desktop"></i> Layar Pelanggan</button>
                <a href="admin.php" class="text-blue-600"><i class="fas fa-tachometer-alt"></i> Dasbor</a>
             </div>
        </div>
        <div class="mt-4 border-b border-gray-200">
            <nav class="flex -mb-px">
                <button id="tab-produk" class="tab-btn active w-1/2 py-3 text-center border-b-2"><i class="fas fa-boxes mr-2"></i>Produk</button>
                <button id="tab-keranjang" class="tab-btn w-1/2 py-3 text-center border-b-2 relative"><i class="fas fa-shopping-cart mr-2"></i>Keranjang<span id="cart-badge" class="absolute top-2 right-2 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center hidden">0</span></button>
            </nav>
        </div>
    </div>

    <div class="flex flex-col md:flex-row md:h-screen">
        <div id="panel-produk" class="w-full md:w-2/3 p-4 md:p-6 flex flex-col">
            <div class="hidden md:flex justify-between items-center mb-4 flex-shrink-0">
                <h1 class="text-3xl font-bold text-gray-800">Pilih Produk</h1>
                <div class="flex items-center space-x-3">
                    <button id="open-held-orders-btn" class="bg-orange-500 text-white py-2 px-4 rounded-lg shadow-sm hover:bg-orange-600 transition-colors"><i class="fas fa-folder-open mr-2"></i>Lanjutkan</button>
                    <button id="open-customer-display-btn-desktop" class="bg-green-600 text-white py-2 px-4 rounded-lg shadow-sm hover:bg-green-700 transition-colors"><i class="fas fa-desktop mr-2"></i>Layar Pelanggan</button>
                    <a href="admin.php" class="bg-indigo-600 text-white py-2 px-4 rounded-lg shadow-sm hover:bg-indigo-700 transition-colors"><i class="fas fa-tachometer-alt mr-2"></i>Dasbor</a>
                    <a href="logout.php" title="Logout" class="bg-red-500 text-white py-2 px-3 rounded-lg shadow-sm hover:bg-red-600 transition-colors"><i class="fas fa-sign-out-alt"></i></a>
                </div>
            </div>
            <div class="mb-4 flex space-x-4 flex-shrink-0">
                <div class="relative flex-grow"><span class="absolute inset-y-0 left-0 flex items-center pl-3"><i class="fas fa-search text-gray-400"></i></span><input type="search" id="search-product" placeholder="Cari nama produk..." class="w-full pl-10 pr-4 py-2 border rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500"></div>
                <button id="start-scan-btn" class="bg-blue-600 text-white py-2 px-4 rounded-lg shadow-sm hover:bg-blue-700 font-semibold transition-colors whitespace-nowrap"><i class="fas fa-camera"></i> Scan</button>
            </div>
            <div id="category-filters" class="mb-4 flex flex-wrap gap-2 flex-shrink-0">
                <button class="category-filter-btn bg-blue-600 text-white py-1 px-3 rounded-full text-sm shadow-sm" data-category="all">Semua</button>
                <?php foreach ($categories as $category) : ?><button class="category-filter-btn bg-white text-gray-700 py-1 px-3 rounded-full text-sm border" data-category="<?= htmlspecialchars($category) ?>"><?= htmlspecialchars($category) ?></button><?php endforeach; ?>
            </div>
            <div id="product-list-container" class="flex-grow">
                <div id="product-list" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
                    <?php foreach ($products as $product) : ?>
                        <div class="product-card bg-white rounded-lg shadow-md p-3 flex flex-col items-center cursor-pointer" data-id="<?= htmlspecialchars($product['id']) ?>" data-name="<?= htmlspecialchars($product['name']) ?>" data-price="<?= htmlspecialchars($product['price']) ?>" data-barcode="<?= htmlspecialchars($product['barcode'] ?? '') ?>" data-category="<?= htmlspecialchars($product['category'] ?? '') ?>" data-stock="<?= htmlspecialchars($product['stock']) ?>">
                            <div class="w-full h-28 mb-3"><?php if (!empty($product['image']) && file_exists($product['image'])) : ?><img src="<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="w-full h-full object-cover rounded-md"><?php else : ?><div class="w-full h-full bg-gray-200 rounded-md flex items-center justify-center"><i class="fas fa-image text-gray-400 text-3xl"></i></div><?php endif; ?></div>
                            <h3 class="font-semibold text-center text-gray-800 text-sm leading-tight h-10 flex items-center justify-center"><?= htmlspecialchars($product['name']) ?></h3>
                            <p class="text-green-600 font-bold mt-1 text-md">Rp<?= number_format($product['price'], 0, ',', '.') ?></p>
                            <div class="mt-2 text-xs font-semibold"><?php $stock = $product['stock']; if ($stock > 10) { echo "<span class='text-gray-500'>Stok: $stock</span>"; } elseif ($stock > 0 && $stock <= 10) { echo "<span class='bg-yellow-100 text-yellow-800 py-0.5 px-2 rounded-full'>Stok: $stock</span>"; } else { echo "<span class='bg-red-100 text-red-800 font-bold py-0.5 px-2 rounded-full'>STOK HABIS</span>"; } ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div id="panel-keranjang" class="w-full md:w-1/3 p-4 md:p-6 bg-white md:shadow-2xl flex flex-col hidden md:flex">
             <div class="flex-grow overflow-y-auto">
                <h2 class="text-2xl font-bold text-gray-800 mb-4 flex-shrink-0">Keranjang</h2>
                <div id="cart-items" class="flex-grow mb-4 space-y-3"><p class="text-gray-500 text-center pt-10">Keranjang masih kosong</p></div>
                <div class="flex-shrink-0">
                    <div class="bg-gray-50 p-3 rounded-lg border mb-4"><button type="button" id="open-manual-product-modal" class="w-full bg-teal-500 text-white text-sm py-2 rounded-md hover:bg-teal-600 font-semibold"><i class="fas fa-plus mr-2"></i>Tambah Barang Manual</button></div>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between items-center"><label for="global-discount-input" class="font-semibold text-gray-700">Diskon Global</label><input type="number" id="global-discount-input" placeholder="0" class="w-1/2 text-sm p-2 border rounded-md text-right"></div>
                        <div class="flex justify-between items-center"><label for="admin-fee-input" class="font-semibold text-gray-700">Biaya Admin</label><input type="number" id="admin-fee-input" placeholder="0" class="w-1/2 text-sm p-2 border rounded-md text-right"></div>
                    </div>
                    <div id="summary-section" class="border-t pt-4 mt-4 space-y-2 text-md">
                        <div class="flex justify-between"><span>Subtotal</span><span id="summary-subtotal">Rp0</span></div>
                        <div class="flex justify-between text-red-500"><span>Total Diskon</span><span id="summary-total-discount">-Rp0</span></div>
                        <div class="flex justify-between text-green-600"><span>Biaya Admin</span><span id="summary-admin-fee">+Rp0</span></div>
                        <div class="flex justify-between items-center text-xl font-bold border-t pt-2 mt-2"><span>TOTAL</span><span id="summary-grand-total">Rp0</span></div>
                    </div>
                    <div id="qris-payment-section" class="mt-4"><div id="qrcode" class="bg-gray-100 rounded-lg p-2 w-full h-auto aspect-square flex items-center justify-center"><canvas id="qris-canvas" class="w-full h-full" style="display: none;"></canvas><span id="qrcode-placeholder" class="text-gray-500 text-center">Pilih produk atau ganti metode bayar ke QRIS</span></div></div>
                    <form id="order-form" class="mt-4">
                        <div class="mb-3">
                            <label for="customer-search" class="block text-sm font-semibold text-gray-700 mb-1">Pelanggan (Wajib untuk Utang)</label>
                            <div class="relative"><input type="text" id="customer-search" placeholder="Cari pelanggan terdaftar..." class="w-full p-2 border rounded-md"><div id="search-results" class="absolute z-10 w-full bg-white border rounded-md mt-1 shadow-lg hidden"></div></div>
                            <div id="selected-customer-info" class="mt-2 p-2 bg-blue-50 border border-blue-200 rounded-md hidden"><p class="text-sm font-semibold text-blue-800 flex justify-between items-center"><span id="selected-customer-name"></span><button type="button" id="clear-customer-btn" class="text-red-500 text-xs hover:text-red-700">Ganti</button></p></div>
                        </div>
                        <input type="hidden" id="selected-customer-id" name="customer_id"><input type="hidden" id="cart-data" name="cart_data">
                        <div class="mb-3">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Metode Bayar</label>
                            <div id="payment-method-buttons" class="grid grid-cols-2 gap-2">
                                <button type="button" class="payment-method-btn active" data-value="QRIS"><i class="fas fa-qrcode mr-2"></i>QRIS</button>
                                <button type="button" class="payment-method-btn" data-value="Tunai"><i class="fas fa-money-bill-wave mr-2"></i>Tunai</button>
                                <button type="button" class="payment-method-btn" data-value="Kartu Debit/Kredit"><i class="far fa-credit-card mr-2"></i>Kartu</button>
                                <button type="button" class="payment-method-btn" data-value="Utang" id="debt-payment-btn" disabled><i class="fas fa-book mr-2"></i>Utang</button>
                            </div>
                            <input type="hidden" id="payment-method-input" name="payment_method" value="QRIS">
                        </div>
                        <div class="mt-4 grid grid-cols-3 gap-3">
                            <button type="button" id="hold-order-btn" class="w-full bg-orange-500 text-white py-3 rounded-lg hover:bg-orange-600 font-semibold transition-colors"><i class="fas fa-pause mr-1"></i>Tahan</button>
                            <button type="button" id="save-order-btn" class="w-full bg-gray-600 text-white py-3 rounded-lg hover:bg-gray-700 font-semibold transition-colors"><i class="fas fa-save mr-1"></i>Simpan</button>
                            <button type="button" id="save-print-btn" class="w-full bg-blue-600 text-white py-3 rounded-lg hover:bg-blue-700 font-semibold transition-colors"><i class="fas fa-print mr-1"></i>Cetak</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Semua Modal -->
    <div id="held-orders-modal" class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50 hidden modal-overlay"><div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-lg m-4"><h2 class="text-2xl font-bold text-gray-800 mb-6">Pilih Transaksi untuk Dilanjutkan</h2><div id="held-orders-list" class="space-y-3 max-h-96 overflow-y-auto"></div><div class="mt-8 flex justify-end"><button type="button" id="close-held-orders-modal-btn" class="bg-gray-300 text-gray-800 py-2 px-6 rounded-md hover:bg-gray-400">Tutup</button></div></div></div>
    <div id="manual-product-modal" class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50 hidden modal-overlay"><div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-sm m-4"><h2 class="text-2xl font-bold text-gray-800 mb-6">Tambah Barang Manual</h2><form id="manual-product-form"><div class="space-y-4"><div><label for="manual-product-name" class="block text-gray-700 font-semibold mb-2">Nama Produk</label><input type="text" id="manual-product-name" class="w-full px-4 py-2 border rounded-lg" required></div><div><label for="manual-product-price" class="block text-gray-700 font-semibold mb-2">Harga Satuan</label><input type="number" id="manual-product-price" class="w-full px-4 py-2 border rounded-lg" required></div><div><label for="manual-product-quantity" class="block text-gray-700 font-semibold mb-2">Jumlah</label><input type="number" id="manual-product-quantity" value="1" class="w-full px-4 py-2 border rounded-lg" required></div></div><div class="mt-8 flex justify-end space-x-3"><button type="button" id="cancel-manual-product-btn" class="bg-gray-300 text-gray-800 py-2 px-6 rounded-md hover:bg-gray-400">Batal</button><button type="submit" class="bg-green-600 text-white py-2 px-6 rounded-md hover:bg-green-700">Tambahkan</button></div></form></div></div>
    <div id="cash-modal" class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50 hidden modal-overlay"><div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-sm m-4"><h2 class="text-2xl font-bold text-gray-800 mb-4">Pembayaran Tunai</h2><div class="mb-4"><div class="flex justify-between items-baseline"><span class="text-gray-600">Total Belanja:</span><span id="modal-total-display" class="text-2xl font-bold text-gray-900">Rp0</span></div></div><div class="mb-4"><label for="modal-cash-input" class="block text-gray-700 font-semibold mb-2">Jumlah Uang Tunai (Rp)</label><input type="number" id="modal-cash-input" placeholder="0" class="w-full px-4 py-3 border rounded-lg text-lg text-right focus:outline-none focus:ring-2 focus:ring-blue-500"></div><div id="cash-shortcuts" class="grid grid-cols-3 gap-2 mb-4"><button class="cash-shortcut-btn bg-gray-200 hover:bg-gray-300 rounded-md py-2" data-amount="pas">Uang Pas</button><button class="cash-shortcut-btn bg-gray-200 hover:bg-gray-300 rounded-md py-2" data-amount="20000">20.000</button><button class="cash-shortcut-btn bg-gray-200 hover:bg-gray-300 rounded-md py-2" data-amount="50000">50.000</button><button class="cash-shortcut-btn bg-gray-200 hover:bg-gray-300 rounded-md py-2" data-amount="100000">100.000</button><button class="cash-shortcut-btn bg-gray-200 hover:bg-gray-300 rounded-md py-2" data-amount="150000">150.000</button><button class="cash-shortcut-btn bg-gray-200 hover:bg-gray-300 rounded-md py-2" data-amount="200000">200.000</button></div><div class="mb-6"><div class="flex justify-between items-baseline"><span class="text-gray-600">Kembalian:</span><span id="modal-change-display" class="text-3xl font-bold text-blue-600">Rp0</span></div></div><div class="flex justify-end space-x-3"><button type="button" id="modal-cancel-btn" class="bg-gray-300 text-gray-800 py-2 px-6 rounded-md hover:bg-gray-400">Batal</button><button type="button" id="modal-confirm-btn" class="bg-green-600 text-white py-2 px-6 rounded-md hover:bg-green-700">Konfirmasi Bayar</button></div></div></div>
    <div id="scanner-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden"><div class="bg-white rounded-lg shadow-xl p-4 w-full max-w-md m-4"><h2 class="text-xl font-bold text-gray-800 mb-4 text-center">Arahkan Barcode ke Kamera</h2><div id="reader" class="w-full rounded-md overflow-hidden bg-gray-200" style="aspect-ratio: 1/1;"></div><button id="close-scanner-btn" class="w-full mt-4 bg-red-500 text-white py-2 rounded-md hover:bg-red-600">Tutup Scanner</button></div></div>
    <div id="print-preview-modal" class="fixed inset-0 bg-gray-900 bg-opacity-75 flex items-center justify-center z-50 hidden modal-overlay"><div class="bg-gray-200 rounded-lg shadow-xl w-full max-w-sm m-4 flex flex-col"><div id="receipt-content" class="p-4 max-h-[60vh] overflow-y-auto"></div><div id="print-action-buttons" class="p-4 border-t bg-white rounded-b-lg grid grid-cols-2 gap-3"><button id="close-print-modal-btn" class="bg-gray-500 text-white py-2 px-4 rounded-md hover:bg-gray-600 col-span-2">Tutup</button></div></div></div>

    <div class="hidden"><input type="number" id="nominalInput"><input type="text" id="qrisInput" value="<?= htmlspecialchars($qris_static_code) ?>"></div>
    <div id="toast-container" class="fixed top-5 right-5 z-50 space-y-2"></div>
    <script>const allProducts = <?= json_encode($products) ?>;</script>
    <script src="js/script.js"></script>
    <script src="js/utang.js"></script> <!-- Muat file utang.js -->
</body>
</html>
