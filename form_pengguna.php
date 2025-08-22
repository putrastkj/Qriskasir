<?php
// form_pengguna.php

// Definisikan konstanta keamanan SEBELUM memanggil file lain.
define('_KASIR_', true);

require_once 'admin_only_check.php';
require_once 'config/database.php';

$id = 0; $username = ''; $role = 'kasir';
$is_edit = false;

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $conn->prepare("SELECT username, role FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        extract($user);
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
    <title><?= $is_edit ? 'Edit' : 'Tambah' ?> Pengguna</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 font-sans">
    <div class="container mx-auto p-4 sm:p-6">
        <div class="max-w-lg mx-auto">
            <div class="flex justify-between items-center mb-6">
                 <h1 class="text-3xl font-bold text-gray-800 flex items-center">
                    <i class="fas fa-user-edit mr-3"></i> 
                    <?= $is_edit ? 'Edit Pengguna' : 'Pengguna Baru' ?>
                </h1>
                <a href="admin_pengguna.php" class="text-sm text-gray-600 hover:text-blue-600">
                    <i class="fas fa-arrow-left mr-2"></i>Kembali
                </a>
            </div>

            <div class="bg-white p-6 sm:p-8 rounded-xl shadow-lg">
                <form action="aksi_pengguna.php" method="POST" class="space-y-6">
                    <input type="hidden" name="id" value="<?= $id ?>">
                    <div>
                        <label for="username" class="block text-gray-700 font-semibold mb-2">Username</label>
                        <input type="text" name="username" id="username" value="<?= htmlspecialchars($username) ?>" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    </div>
                    <div>
                        <label for="password" class="block text-gray-700 font-semibold mb-2">Password</label>
                        <input type="password" name="password" id="password" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" <?= !$is_edit ? 'required' : '' ?>>
                        <?php if ($is_edit): ?>
                        <p class="text-xs text-gray-500 mt-1">Kosongkan jika tidak ingin mengubah password.</p>
                        <?php endif; ?>
                    </div>
                    <div>
                        <label for="role" class="block text-gray-700 font-semibold mb-2">Peran (Role)</label>
                        <select name="role" id="role" class="w-full px-4 py-2 border rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="kasir" <?= $role == 'kasir' ? 'selected' : '' ?>>Kasir</option>
                            <option value="admin" <?= $role == 'admin' ? 'selected' : '' ?>>Admin</option>
                        </select>
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
