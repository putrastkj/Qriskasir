<?php
define('_KASIR_', true);
require_once 'auth_check.php';
require_once 'config/database.php';

$id = 0; $name = ''; $price = ''; $cost_price = ''; $barcode = ''; $category = ''; $stock = 0; $image = ''; $is_edit = false;

// Ambil daftar kategori untuk dropdown
$categories_result = $conn->query("SELECT name FROM categories ORDER BY name ASC");
$categories = [];
while($row = $categories_result->fetch_assoc()) {
    $categories[] = $row['name'];
}

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $product = $result->fetch_assoc();
        extract($product);
        $is_edit = true;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $is_edit ? 'Edit' : 'Tambah' ?> Produk</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
</head>
<body class="bg-gray-100 font-sans">
    <div class="container mx-auto p-4 sm:p-6">
        <div class="max-w-2xl mx-auto">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold text-gray-800 flex items-center">
                    <i class="fas fa-box-open mr-3"></i> 
                    <?= $is_edit ? 'Edit Produk' : 'Tambah Produk Baru' ?>
                </h1>
                <a href="admin_produk.php" class="text-sm text-gray-600 hover:text-blue-600">
                    <i class="fas fa-arrow-left mr-2"></i>Kembali
                </a>
            </div>
            
            <div class="bg-white p-6 sm:p-8 rounded-xl shadow-lg">
                <form action="aksi_produk.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="id" value="<?= $id ?>">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="md:col-span-2">
                            <label for="name" class="block text-gray-700 font-semibold mb-2">Nama Produk</label>
                            <div class="flex gap-2">
                                <input type="text" name="name" id="name" value="<?= htmlspecialchars($name) ?>" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                                <button type="button" id="search-image-btn" class="flex-shrink-0 bg-blue-600 text-white px-4 rounded-lg hover:bg-blue-700 transition-colors" title="Cari gambar otomatis">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Setelah mengisi nama produk, klik tombol <i class="fas fa-search"></i> untuk mencari gambar secara otomatis.</p>
                        </div>
                        <div>
                            <label for="cost_price" class="block text-gray-700 font-semibold mb-2">Harga Beli (Modal)</label>
                            <input type="number" name="cost_price" id="cost_price" value="<?= htmlspecialchars($cost_price) ?>" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required min="0" placeholder="Contoh: 10000">
                            <p class="text-xs text-gray-500 mt-1">Harga modal produk dari supplier.</p>
                        </div>
                        <div>
                            <label for="price" class="block text-gray-700 font-semibold mb-2">Harga Jual</label>
                            <input type="number" name="price" id="price" value="<?= htmlspecialchars($price) ?>" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required min="0" placeholder="Contoh: 15000">
                            <p class="text-xs text-gray-500 mt-1">Harga yang akan ditampilkan ke pelanggan.</p>
                        </div>
                        <div>
                            <label for="stock" class="block text-gray-700 font-semibold mb-2">Jumlah Stok</label>
                            <input type="number" name="stock" id="stock" value="<?= htmlspecialchars($stock) ?>" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required min="0" placeholder="Contoh: 50">
                            <p class="text-xs text-gray-500 mt-1">Jumlah stok produk yang tersedia saat ini.</p>
                        </div>
                        <div>
                            <label for="category" class="block text-gray-700 font-semibold mb-2">Kategori</label>
                            <select name="category" id="category" class="w-full px-4 py-2 border rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <?php foreach ($categories as $cat_name): ?>
                                    <option value="<?= htmlspecialchars($cat_name) ?>" <?= ($category == $cat_name) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat_name) ?>
                                    </option>
                                <?php endforeach; ?>
                                <option value="Lainnya" <?= ($category == 'Lainnya' || empty($category)) ? 'selected' : '' ?>>Lainnya</option>
                            </select>
                            <p class="text-xs text-gray-500 mt-1">Pilih kategori yang sesuai. Anda bisa mengelola daftar kategori di halaman "Manajemen Kategori".</p>
                        </div>
                        <div class="md:col-span-2">
                            <label for="barcode" class="block text-gray-700 font-semibold mb-2">Barcode (Opsional)</label>
                            <div class="flex gap-2">
                                <input type="text" name="barcode" id="barcode" value="<?= htmlspecialchars($barcode ?? '') ?>" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Ketik atau scan kode">
                                <button type="button" id="scan-barcode-btn" class="flex-shrink-0 bg-blue-600 text-white px-4 rounded-lg hover:bg-blue-700 transition-colors">
                                    <i class="fas fa-camera"></i>
                                </button>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Klik tombol <i class="fas fa-camera"></i> untuk memindai menggunakan kamera.</p>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-gray-700 font-semibold mb-2">Gambar Produk</label>
                            <div class="flex items-center gap-4">
                                <img id="image-preview" src="<?= (!empty($image) && file_exists($image)) ? htmlspecialchars($image) : 'https://placehold.co/128x128/e2e8f0/e2e8f0' ?>" alt="Preview" class="w-32 h-32 object-cover rounded-md shadow-sm bg-gray-200">
                                <div class="w-full">
                                    <p class="text-sm text-gray-600 mb-2">Unggah file baru atau gunakan gambar dari pencarian otomatis.</p>
                                    <input type="file" name="image" id="image" class="w-full text-gray-700 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" accept="image/*">
                                    <input type="hidden" name="image_url" id="image_url_input">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-8 border-t pt-6 flex justify-end">
                        <button type="submit" name="submit" class="bg-green-600 text-white py-2 px-6 rounded-lg hover:bg-green-700 font-semibold transition-colors flex items-center">
                            <i class="fas fa-save mr-2"></i><?= $is_edit ? 'Update Produk' : 'Simpan Produk' ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal untuk Scanner Barcode -->
    <div id="scanner-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden"><div class="bg-white rounded-lg shadow-xl p-4 w-full max-w-sm m-4"><h2 class="text-xl font-bold text-gray-800 mb-4 text-center">Arahkan Barcode ke Kamera</h2><div id="reader" class="w-full rounded-md overflow-hidden"></div><button id="close-scanner-btn" class="w-full mt-4 bg-red-500 text-white py-2 rounded-md hover:bg-red-600">Batal</button></div></div>
    
    <!-- Modal untuk Pencarian Gambar -->
    <div id="image-search-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden"><div class="bg-white rounded-lg shadow-xl p-4 w-full max-w-2xl m-4"><h2 id="image-search-title" class="text-xl font-bold text-gray-800 mb-4 text-center">Hasil Gambar</h2><div id="image-results" class="grid grid-cols-3 sm:grid-cols-4 gap-4 max-h-96 overflow-y-auto"></div><button id="close-image-search-btn" class="w-full mt-4 bg-gray-500 text-white py-2 rounded-md hover:bg-gray-600">Tutup</button></div></div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // --- Logika Scanner Barcode ---
        const scannerModal = document.getElementById('scanner-modal');
        const scanBtn = document.getElementById('scan-barcode-btn');
        const closeScannerBtn = document.getElementById('close-scanner-btn');
        const barcodeInput = document.getElementById('barcode');
        const html5QrCode = new Html5Qrcode("reader");
        const qrCodeSuccessCallback = (decodedText, decodedResult) => { barcodeInput.value = decodedText; stopScanner(); alert(`Barcode terdeteksi: ${decodedText}`); };
        const config = { fps: 10, qrbox: { width: 250, height: 250 } };
        function startScanner() { scannerModal.classList.remove('hidden'); html5QrCode.start({ facingMode: "environment" }, config, qrCodeSuccessCallback).catch(err => { alert("Tidak dapat memulai kamera."); stopScanner(); }); }
        function stopScanner() { html5QrCode.stop().catch(err => {}); scannerModal.classList.add('hidden'); }
        scanBtn.addEventListener('click', startScanner);
        closeScannerBtn.addEventListener('click', stopScanner);

        // --- Logika Pencarian Gambar via Google ---
        const searchImageBtn = document.getElementById('search-image-btn');
        const imageSearchModal = document.getElementById('image-search-modal');
        const closeImageSearchBtn = document.getElementById('close-image-search-btn');
        const imageResultsDiv = document.getElementById('image-results');
        const imageSearchTitle = document.getElementById('image-search-title');
        const productNameInput = document.getElementById('name');
        const imageUrlInput = document.getElementById('image_url_input');
        const imagePreview = document.getElementById('image-preview');

        searchImageBtn.addEventListener('click', async () => {
            const productName = productNameInput.value.trim();
            if (!productName) {
                alert('Silakan masukkan nama produk terlebih dahulu.');
                return;
            }
            imageSearchTitle.textContent = `Mencari gambar untuk "${productName}"...`;
            imageResultsDiv.innerHTML = '<div class="col-span-full text-center p-8"><i class="fas fa-spinner fa-spin fa-2x"></i></div>';
            imageSearchModal.classList.remove('hidden');

            try {
                const response = await fetch(`cari_gambar.php?q=${encodeURIComponent(productName)}`);
                const images = await response.json();

                imageResultsDiv.innerHTML = '';

                if (images.error) {
                    throw new Error(images.error);
                }

                if (images.length === 0) {
                    imageResultsDiv.innerHTML = '<p class="col-span-full text-center p-8">Tidak ada gambar yang ditemukan.</p>';
                    return;
                }

                images.forEach(imageUrl => {
                    const imgContainer = document.createElement('div');
                    imgContainer.className = 'cursor-pointer border-2 border-transparent hover:border-blue-500 rounded-lg overflow-hidden aspect-square';
                    imgContainer.innerHTML = `<img src="${imageUrl}" class="w-full h-full object-cover" loading="lazy" onerror="this.parentElement.style.display='none'">`;
                    
                    imgContainer.addEventListener('click', () => {
                        imageUrlInput.value = imageUrl;
                        imagePreview.src = imageUrl;
                        imageSearchModal.classList.add('hidden');
                    });
                    imageResultsDiv.appendChild(imgContainer);
                });

            } catch (error) {
                console.error('Error fetching images:', error);
                imageResultsDiv.innerHTML = `<p class="col-span-full text-center p-8 text-red-500">${error.message}</p>`;
            }
        });

        closeImageSearchBtn.addEventListener('click', () => {
            imageSearchModal.classList.add('hidden');
        });
    });
</script>
</body>
</html>
