<?php
define('_KASIR_', true);
require_once 'auth_check.php';
require_once 'config/database.php';

$categories = $conn->query("SELECT * FROM categories ORDER BY name ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Kategori</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 font-sans">
    <div class="container mx-auto p-4 sm:p-6">
        <div class="flex flex-wrap justify-between items-center mb-6 gap-4">
            <h1 class="text-3xl font-bold text-gray-800">Manajemen Kategori</h1>
            <a href="admin.php" class="bg-indigo-600 text-white py-2 px-4 rounded-lg shadow-sm hover:bg-indigo-700 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>Kembali ke Dasbor
            </a>
        </div>

        <?php if (isset($_SESSION['action_status'])): ?>
            <div class="bg-<?= $_SESSION['action_status']['type'] === 'success' ? 'green' : 'red' ?>-100 border-l-4 border-<?= $_SESSION['action_status']['type'] === 'success' ? 'green' : 'red' ?>-500 text-<?= $_SESSION['action_status']['type'] === 'success' ? 'green' : 'red' ?>-700 p-4 mb-6 rounded-lg shadow-sm" role="alert">
                <p><?= htmlspecialchars($_SESSION['action_status']['message']) ?></p>
            </div>
            <?php unset($_SESSION['action_status']); ?>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Form Tambah/Edit -->
            <div class="lg:col-span-1">
                <div class="bg-white p-6 rounded-xl shadow-lg">
                    <h2 class="text-xl font-bold text-gray-800 mb-4" id="form-title">Tambah Kategori Baru</h2>
                    <form action="aksi_kategori.php" method="POST">
                        <input type="hidden" name="id" id="category-id">
                        <input type="hidden" name="action" id="form-action" value="add">
                        <div>
                            <label for="category-name" class="block text-gray-700 font-semibold mb-2">Nama Kategori</label>
                            <input type="text" name="name" id="category-name" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        </div>
                        <div class="mt-6 flex gap-2">
                            <button type="submit" id="submit-btn" class="w-full bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 font-semibold transition-colors">
                                <i class="fas fa-save mr-2"></i>Simpan
                            </button>
                            <button type="button" id="cancel-btn" class="w-full bg-gray-300 text-gray-800 py-2 px-4 rounded-lg hover:bg-gray-400 font-semibold transition-colors hidden">
                                Batal
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Daftar Kategori -->
            <div class="lg:col-span-2">
                <div class="bg-white p-6 rounded-xl shadow-lg">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">Daftar Kategori</h2>
                    <div class="space-y-3">
                        <?php if ($categories->num_rows > 0): while($category = $categories->fetch_assoc()): ?>
                        <div class="bg-gray-50 border p-3 rounded-lg flex justify-between items-center">
                            <span class="font-semibold"><?= htmlspecialchars($category['name']) ?></span>
                            <div class="flex gap-2">
                                <button class="edit-btn text-yellow-500 hover:text-yellow-700" data-id="<?= $category['id'] ?>" data-name="<?= htmlspecialchars($category['name']) ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <a href="aksi_kategori.php?action=delete&id=<?= $category['id'] ?>" class="text-red-500 hover:text-red-700" onclick="return confirm('Yakin ingin menghapus kategori ini? Semua produk dalam kategori ini akan diubah menjadi \'Lainnya\'.')">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                            </div>
                        </div>
                        <?php endwhile; else: ?>
                        <p class="text-gray-500 text-center">Belum ada kategori.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const formTitle = document.getElementById('form-title');
        const categoryIdInput = document.getElementById('category-id');
        const categoryNameInput = document.getElementById('category-name');
        const formActionInput = document.getElementById('form-action');
        const submitBtn = document.getElementById('submit-btn');
        const cancelBtn = document.getElementById('cancel-btn');
        const editButtons = document.querySelectorAll('.edit-btn');

        function resetForm() {
            formTitle.textContent = 'Tambah Kategori Baru';
            categoryIdInput.value = '';
            categoryNameInput.value = '';
            formActionInput.value = 'add';
            submitBtn.innerHTML = '<i class="fas fa-save mr-2"></i>Simpan';
            cancelBtn.classList.add('hidden');
        }

        editButtons.forEach(button => {
            button.addEventListener('click', () => {
                const id = button.dataset.id;
                const name = button.dataset.name;
                
                formTitle.textContent = 'Edit Kategori';
                categoryIdInput.value = id;
                categoryNameInput.value = name;
                formActionInput.value = 'update';
                submitBtn.innerHTML = '<i class="fas fa-save mr-2"></i>Update';
                cancelBtn.classList.remove('hidden');
                categoryNameInput.focus();
            });
        });

        cancelBtn.addEventListener('click', resetForm);
    });
</script>
</body>
</html>
