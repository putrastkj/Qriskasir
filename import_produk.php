<?php
// import_produk.php
define('_KASIR_', true);
require_once 'auth_check.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Impor Produk dari CSV</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 font-sans">
    <div class="container mx-auto p-4 sm:p-6">
        <div class="max-w-2xl mx-auto">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold text-gray-800 flex items-center">
                    <i class="fas fa-file-upload mr-3"></i> 
                    Impor Produk
                </h1>
                <a href="admin_produk.php" class="text-sm text-gray-600 hover:text-blue-600">
                    <i class="fas fa-arrow-left mr-2"></i>Kembali
                </a>
            </div>

            <?php if (isset($_SESSION['import_status'])): ?>
                <div class="bg-<?= $_SESSION['import_status']['type'] === 'success' ? 'green' : 'red' ?>-100 border-l-4 border-<?= $_SESSION['import_status']['type'] === 'success' ? 'green' : 'red' ?>-500 text-<?= $_SESSION['import_status']['type'] === 'success' ? 'green' : 'red' ?>-700 p-4 mb-6 rounded-lg shadow-sm" role="alert">
                    <p class="font-bold"><?= $_SESSION['import_status']['type'] === 'success' ? 'Berhasil!' : 'Gagal!' ?></p>
                    <p><?= htmlspecialchars($_SESSION['import_status']['message']) ?></p>
                </div>
                <?php unset($_SESSION['import_status']); ?>
            <?php endif; ?>

            <div class="bg-white p-6 sm:p-8 rounded-xl shadow-lg">
                <form action="aksi_import_produk.php" method="POST" enctype="multipart/form-data">
                    <div class="space-y-6">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-800">Langkah 1: Unduh Template</h2>
                            <p class="text-sm text-gray-600 mt-1">Gunakan template CSV ini untuk memastikan data Anda sesuai format. Pastikan kolom `harga_beli` dan `harga_jual` diisi dengan benar.</p>
                            <a href="template_produk.csv" download class="mt-3 inline-flex items-center bg-gray-600 text-white py-2 px-4 rounded-lg hover:bg-gray-700 font-semibold transition-colors">
                                <i class="fas fa-download mr-2"></i> Unduh Template CSV
                            </a>
                        </div>
                        <div class="border-t pt-6">
                            <h2 class="text-lg font-semibold text-gray-800">Langkah 2: Unggah File</h2>
                            <label for="file_csv" class="block text-gray-700 font-semibold mb-2 mt-4">Pilih File CSV</label>
                            <input type="file" name="file_csv" id="file_csv" class="w-full text-gray-700 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" accept=".csv" required>
                        </div>
                    </div>

                    <div class="mt-8 border-t pt-6 flex justify-end">
                        <button type="submit" name="submit" class="bg-green-600 text-white py-2 px-6 rounded-lg hover:bg-green-700 font-semibold transition-colors flex items-center">
                            <i class="fas fa-check mr-2"></i> Mulai Proses Impor
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
