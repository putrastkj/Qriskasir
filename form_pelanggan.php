<?php
// form_pelanggan.php

// Definisikan konstanta keamanan SEBELUM memanggil file lain.
define('_KASIR_', true);

require_once 'config/database.php';
require_once 'auth_check.php';

$id = 0; $name = ''; $phone = ''; $email = ''; $address = '';
$is_edit = false;

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $customer = $result->fetch_assoc();
        extract($customer);
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
    <title><?= $is_edit ? 'Edit' : 'Tambah' ?> Pelanggan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 font-sans">
    <div class="container mx-auto p-4 sm:p-6">
        <div class="max-w-2xl mx-auto">
            <div class="flex justify-between items-center mb-6">
                 <h1 class="text-3xl font-bold text-gray-800 flex items-center">
                    <i class="fas fa-user-plus mr-3"></i> 
                    <?= $is_edit ? 'Edit Pelanggan' : 'Pelanggan Baru' ?>
                </h1>
                <a href="admin_pelanggan.php" class="text-sm text-gray-600 hover:text-blue-600">
                    <i class="fas fa-arrow-left mr-2"></i>Kembali
                </a>
            </div>

            <div class="bg-white p-6 sm:p-8 rounded-xl shadow-lg">
                <form action="aksi_pelanggan.php" method="POST" class="space-y-6">
                    <input type="hidden" name="id" value="<?= $id ?>">
                    <div>
                        <label for="name" class="block text-gray-700 font-semibold mb-2">Nama Pelanggan</label>
                        <input type="text" name="name" id="name" value="<?= htmlspecialchars($name) ?>" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    </div>
                    <div>
                        <label for="phone" class="block text-gray-700 font-semibold mb-2">No. Telepon</label>
                        <input type="tel" name="phone" id="phone" value="<?= htmlspecialchars($phone ?? '') ?>" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="email" class="block text-gray-700 font-semibold mb-2">Email</label>
                        <input type="email" name="email" id="email" value="<?= htmlspecialchars($email ?? '') ?>" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="address" class="block text-gray-700 font-semibold mb-2">Alamat</label>
                        <textarea name="address" id="address" rows="3" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"><?= htmlspecialchars($address ?? '') ?></textarea>
                    </div>

                    <div class="border-t pt-6 flex justify-end">
                        <button type="submit" name="submit" class="bg-green-600 text-white py-2 px-6 rounded-lg hover:bg-green-700 font-semibold transition-colors flex items-center">
                             <i class="fas fa-save mr-2"></i><?= $is_edit ? 'Update' : 'Simpan' ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
