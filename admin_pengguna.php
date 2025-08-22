<?php
// admin_pengguna.php

// Definisikan konstanta keamanan SEBELUM memanggil file lain.
define('_KASIR_', true);

require_once 'admin_only_check.php';
require_once 'config/database.php';

$result = $conn->query("SELECT id, username, role, created_at FROM users ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Pengguna</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 font-sans">
    <div class="container mx-auto p-4 sm:p-6">
        <div class="flex flex-wrap justify-between items-center mb-6 gap-4">
            <h1 class="text-3xl font-bold text-gray-800">Manajemen Pengguna</h1>
            <a href="admin.php" class="bg-indigo-600 text-white py-2 px-4 rounded-lg shadow-sm hover:bg-indigo-700 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>Kembali ke Dasbor
            </a>
        </div>

        <div class="bg-white p-4 sm:p-6 rounded-xl shadow-lg">
            <div class="flex justify-between items-center mb-6">
                <a href="form_pengguna.php" class="bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition-colors flex items-center justify-center">
                    <i class="fas fa-plus mr-2"></i> Tambah Pengguna
                </a>
            </div>

            <div class="space-y-4">
                <?php if ($result->num_rows > 0) : while ($user = $result->fetch_assoc()) : ?>
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 flex flex-col sm:flex-row items-start sm:items-center gap-4">
                        <div class="flex-grow">
                            <h3 class="font-bold text-lg text-gray-800"><?= htmlspecialchars($user['username']) ?></h3>
                            <div class="mt-1">
                                <span class="capitalize text-xs font-semibold py-1 px-2.5 rounded-full <?= $user['role'] == 'admin' ? 'bg-red-100 text-red-800' : 'bg-gray-200 text-gray-800' ?>">
                                    <i class="fas <?= $user['role'] == 'admin' ? 'fa-user-shield' : 'fa-user-tag' ?> mr-1"></i>
                                    <?= htmlspecialchars($user['role']) ?>
                                </span>
                            </div>
                        </div>
                        <div class="flex-shrink-0 flex items-stretch gap-2 w-full sm:w-auto">
                            <a href="form_pengguna.php?id=<?= $user['id'] ?>" class="w-full text-center bg-yellow-400 text-yellow-900 hover:bg-yellow-500 font-semibold py-2 px-4 rounded-md text-sm transition-colors"><i class="fas fa-edit mr-2"></i>Edit</a>
                            <?php if ($_SESSION['user_id'] != $user['id']): ?>
                            <a href="aksi_pengguna.php?action=delete&id=<?= $user['id'] ?>" class="w-full text-center bg-red-500 text-white hover:bg-red-600 font-semibold py-2 px-4 rounded-md text-sm transition-colors" onclick="return confirm('Yakin ingin menghapus pengguna ini?')"><i class="fas fa-trash-alt mr-2"></i>Hapus</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; else: ?>
                    <div class="text-center p-10 text-gray-500 border-2 border-dashed rounded-lg">
                         <i class="fas fa-users-slash fa-3x mb-3"></i>
                        <p class="font-semibold">Belum ada data pengguna.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
