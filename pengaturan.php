<?php
// pengaturan.php

@ini_set('upload_max_filesize', '64M');
@ini_set('post_max_size', '64M');

define('_KASIR_', true);
require_once 'auth_check.php';
require_once 'config/database.php';

$pesan_sukses = '';
$pesan_error = '';

if (isset($_SESSION['action_status'])) {
    if ($_SESSION['action_status']['type'] === 'success') {
        $pesan_sukses = $_SESSION['action_status']['message'];
    } else {
        $pesan_error = $_SESSION['action_status']['message'];
    }
    unset($_SESSION['action_status']);
}

function parseTLV($string) {
    $result = [];
    $i = 0;
    while ($i < strlen($string)) {
        $tag = substr($string, $i, 2);
        $length = (int)substr($string, $i + 2, 2);
        $value = substr($string, $i + 4, $length);
        $result[$tag] = $value;
        $i += 4 + $length;
    }
    return $result;
}

function parseQRIS($qris_string) {
    $data = parseTLV($qris_string);
    $merchant_info = [];
    $merchant_info['merchant_name'] = $data['59'] ?? '';
    if (isset($data['51'])) {
        $sub_data = parseTLV($data['51']);
        $merchant_info['n-mid'] = $sub_data['02'] ?? '';
        $merchant_info['merchant_id'] = $sub_data['03'] ?? '';
    }
    return $merchant_info;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    
    $settings_data = $_POST;

    if (!empty($settings_data['qris_static_code'])) {
        $parsed_qris = parseQRIS($settings_data['qris_static_code']);
        $settings_data['merchant_name'] = $parsed_qris['merchant_name'] ?: $settings_data['merchant_name'];
        $settings_data['qris_n-mid'] = $parsed_qris['n-mid'];
        $settings_data['qris_merchant_id'] = $parsed_qris['merchant_id'];
    }

    $upload_dir = "uploads/ads/";
    if (!is_dir($upload_dir)) {
        if (!is_writable('uploads/')) {
             $pesan_error = "Error: Direktori 'uploads/' tidak dapat ditulis.";
        } else {
            mkdir($upload_dir, 0777, true);
        }
    }

    if (empty($pesan_error)) {
        $file_uploads = ['ad_image_1', 'ad_image_2', 'ad_image_3', 'qris_background_image', 'login_background_image'];
        foreach ($file_uploads as $file_key) {
            if (isset($_FILES[$file_key]) && $_FILES[$file_key]['error'] == UPLOAD_ERR_OK) {
                $current_upload_dir = ($file_key === 'login_background_image') ? 'uploads/' : $upload_dir;
                $file_name = uniqid() . '_' . basename($_FILES[$file_key]['name']);
                $target_file = $current_upload_dir . $file_name;
                if (move_uploaded_file($_FILES[$file_key]['tmp_name'], $target_file)) {
                    $settings_data[$file_key] = $target_file;
                } else { $pesan_error = "Gagal memindahkan file."; break; }
            }
        }
    }

    if (empty($pesan_error)) {
        $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        foreach ($settings_data as $key => $value) {
            if ($key === 'submit') continue;
            $stmt->bind_param("ss", $key, $value);
            $stmt->execute();
        }
        $stmt->close();
        $pesan_sukses = "Pengaturan berhasil disimpan!";
    }
}

$settings_result = $conn->query("SELECT * FROM settings");
$settings = [];
while($row = $settings_result->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan Toko</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .tab-btn.active {
            border-bottom-color: #3B82F6;
            color: #3B82F6;
            background-color: #EFF6FF;
        }
        .tab-panel {
            display: none;
        }
        .tab-panel.active {
            display: block;
        }
    </style>
</head>
<body class="bg-gray-100 font-sans">
    <div class="container mx-auto p-4 sm:p-6">
        <div class="max-w-3xl mx-auto">
             <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold text-gray-800"><i class="fas fa-cogs"></i> Pengaturan Toko</h1>
                 <a href="admin.php" class="text-sm text-gray-600 hover:text-blue-600"><i class="fas fa-arrow-left mr-2"></i>Kembali</a>
            </div>

            <?php if (!empty($pesan_sukses)): ?>
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-lg shadow-sm" role="alert"><p class="font-bold">Sukses!</p><p><?= $pesan_sukses ?></p></div>
            <?php endif; ?>
            <?php if (!empty($pesan_error)): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg shadow-sm" role="alert"><p class="font-bold">Gagal!</p><p><?= $pesan_error ?></p></div>
            <?php endif; ?>

            <div class="bg-white rounded-xl shadow-lg">
                <div class="border-b border-gray-200">
                    <nav class="flex -mb-px" aria-label="Tabs">
                        <button type="button" class="tab-btn active w-1/6 py-4 px-1 text-center border-b-2 font-medium text-sm" data-tab="umum">Umum</button>
                        <button type="button" class="tab-btn w-1/6 py-4 px-1 text-center border-b-2 font-medium text-sm text-gray-500 hover:text-gray-700" data-tab="login">Login</button>
                        <button type="button" class="tab-btn w-1/6 py-4 px-1 text-center border-b-2 font-medium text-sm text-gray-500 hover:text-gray-700" data-tab="display">Tampilan</button>
                        <button type="button" class="tab-btn w-1/6 py-4 px-1 text-center border-b-2 font-medium text-sm text-gray-500 hover:text-gray-700" data-tab="qris">QRIS</button>
                        <button type="button" class="tab-btn w-1/6 py-4 px-1 text-center border-b-2 font-medium text-sm text-gray-500 hover:text-gray-700" data-tab="integrasi">Integrasi</button>
                        <button type="button" class="tab-btn w-1/6 py-4 px-1 text-center border-b-2 font-medium text-sm text-gray-500 hover:text-gray-700" data-tab="utilitas">Utilitas</button>
                    </nav>
                </div>
                
                <form action="pengaturan.php" method="POST" enctype="multipart/form-data" id="main-settings-form">
                    <div class="p-6 sm:p-8">
                        <div id="tab-panel-umum" class="tab-panel active space-y-6">
                            <div><label for="merchant_name" class="block text-gray-700 font-semibold mb-2">Nama Toko</label><input type="text" name="merchant_name" id="merchant_name" value="<?= htmlspecialchars($settings['merchant_name'] ?? '') ?>" class="w-full px-4 py-2 border rounded-lg"></div>
                            <div><label for="merchant_city" class="block text-gray-700 font-semibold mb-2">Kota Toko</p><input type="text" name="merchant_city" id="merchant_city" value="<?= htmlspecialchars($settings['merchant_city'] ?? '') ?>" class="w-full px-4 py-2 border rounded-lg"></div>
                            <div>
                                <label for="low_stock_threshold" class="block text-gray-700 font-semibold mb-2">Ambang Batas Stok Menipis</label>
                                <input type="number" name="low_stock_threshold" id="low_stock_threshold" value="<?= htmlspecialchars($settings['low_stock_threshold'] ?? '10') ?>" class="w-full px-4 py-2 border rounded-lg">
                                <p class="text-xs text-gray-500 mt-1">Notifikasi akan muncul di dasbor jika stok produk kurang dari atau sama dengan angka ini.</p>
                            </div>
                        </div>

                        <div id="tab-panel-login" class="tab-panel space-y-6">
                            <div>
                                <label for="login_background_image" class="block text-gray-700 font-semibold mb-2">Gambar Latar Halaman Login</label>
                                <?php if (!empty($settings['login_background_image']) && file_exists($settings['login_background_image'])): ?>
                                    <img src="<?= htmlspecialchars($settings['login_background_image']) ?>" class="w-40 h-auto rounded-md shadow-sm mb-2">
                                <?php endif; ?>
                                <input type="file" name="login_background_image" id="login_background_image" class="w-full text-gray-700 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" accept="image/jpeg,image/png">
                                <input type="hidden" name="login_background_image" value="<?= htmlspecialchars($settings['login_background_image'] ?? '') ?>">
                                <p class="text-xs text-gray-500 mt-1">Rekomendasi ukuran gambar: 800x1200 pixels.</p>
                            </div>
                        </div>

                        <div id="tab-panel-display" class="tab-panel space-y-6">
                            <div><label for="running_text" class="block text-gray-700 font-semibold mb-2">Teks Berjalan</label><input type="text" name="running_text" id="running_text" value="<?= htmlspecialchars($settings['running_text'] ?? '') ?>" placeholder="Contoh: Selamat datang!" class="w-full px-4 py-2 border rounded-lg"></div>
                            <?php for ($i = 1; $i <= 3; $i++): $key = 'ad_image_' . $i; ?>
                            <div>
                                <label for="<?= $key ?>" class="block text-gray-700 font-semibold mb-2">File Iklan <?= $i ?></label>
                                <?php if (!empty($settings[$key]) && file_exists($settings[$key])): ?>
                                    <div class="mb-2">
                                    <?php $file_ext = strtolower(pathinfo($settings[$key], PATHINFO_EXTENSION)); if (in_array($file_ext, ['mp4', 'webm'])): ?>
                                        <video src="<?= htmlspecialchars($settings[$key]) ?>" class="w-40 h-auto rounded-md shadow-sm" controls></video>
                                    <?php else: ?><img src="<?= htmlspecialchars($settings[$key]) ?>" class="w-40 h-auto rounded-md shadow-sm"><?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                <input type="file" name="<?= $key ?>" id="<?= $key ?>" class="w-full text-gray-700 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" accept="image/*,video/mp4,video/webm">
                                <input type="hidden" name="<?= $key ?>" value="<?= htmlspecialchars($settings[$key] ?? '') ?>">
                            </div>
                            <?php endfor; ?>
                        </div>

                        <div id="tab-panel-qris" class="tab-panel space-y-6">
                            <div>
                                <label for="qris_static_code" class="block text-gray-700 font-semibold mb-2">Kode Statis QRIS</label>
                                <textarea name="qris_static_code" id="qris_static_code" rows="4" class="w-full px-4 py-2 border rounded-lg font-mono text-sm"><?= htmlspecialchars($settings['qris_static_code'] ?? '') ?></textarea>
                                <p class="text-xs text-gray-500 mt-1">Nama Toko, NMID, dan ID Toko akan diekstrak otomatis dari kode ini.</p>
                            </div>
                            <div class="bg-gray-50 p-3 rounded-lg border">
                                <p class="text-sm font-semibold text-gray-600">NMID (Otomatis): <span class="font-mono text-black"><?= htmlspecialchars($settings['qris_n-mid'] ?? 'Belum ada') ?></span></p>
                                <p class="text-sm font-semibold text-gray-600">ID Toko (Otomatis): <span class="font-mono text-black"><?= htmlspecialchars($settings['qris_merchant_id'] ?? 'Belum ada') ?></span></p>
                            </div>
                            <div>
                                <label for="qris_background_image" class="block text-gray-700 font-semibold mb-2">Gambar Latar QRIS (Opsional)</label>
                                <?php if (!empty($settings['qris_background_image']) && file_exists($settings['qris_background_image'])): ?>
                                    <img src="<?= htmlspecialchars($settings['qris_background_image']) ?>" class="w-40 h-auto rounded-md shadow-sm mb-2">
                                <?php endif; ?>
                                <input type="file" name="qris_background_image" id="qris_background_image" class="w-full text-gray-700 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" accept="image/jpeg,image/png">
                                <input type="hidden" name="qris_background_image" value="<?= htmlspecialchars($settings['qris_background_image'] ?? '') ?>">
                            </div>
                        </div>
                        
                        <div id="tab-panel-integrasi" class="tab-panel space-y-6">
                             <div>
                                <h3 class="text-lg font-semibold text-gray-800">Pencarian Gambar Google</h3>
                                <p class="text-sm text-gray-600 mt-1 mb-4">Masukkan kredensial dari Google Cloud Console untuk mengaktifkan fitur pencarian gambar otomatis.</p>
                                <div>
                                    <label for="google_api_key" class="block text-gray-700 font-semibold mb-2">Google API Key</label>
                                    <input type="text" name="google_api_key" id="google_api_key" value="<?= htmlspecialchars($settings['google_api_key'] ?? '') ?>" class="w-full px-4 py-2 border rounded-lg">
                                </div>
                                <div class="mt-4">
                                    <label for="google_search_engine_id" class="block text-gray-700 font-semibold mb-2">Search Engine ID</label>
                                    <input type="text" name="google_search_engine_id" id="google_search_engine_id" value="<?= htmlspecialchars($settings['google_search_engine_id'] ?? '') ?>" class="w-full px-4 py-2 border rounded-lg">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="border-t pt-6 px-6 sm:px-8 pb-6 flex justify-end">
                        <button type="submit" name="submit" class="bg-blue-600 text-white py-2 px-6 rounded-lg hover:bg-blue-700 font-semibold transition-colors"><i class="fas fa-save mr-2"></i> Simpan Pengaturan</button>
                    </div>
                </form>

                <div id="tab-panel-utilitas" class="tab-panel p-6 sm:p-8 space-y-8">
                    <div class="border-b pb-6">
                        <h3 class="text-lg font-semibold text-gray-800">Impor Database</h3>
                        <p class="text-sm text-gray-600 mt-1">Pulihkan data dari file backup SQL. Tindakan ini akan menimpa semua data yang ada saat ini.</p>
                        <p class="text-sm font-bold text-red-700 mt-2">PERINGATAN: Pastikan Anda memiliki backup sebelum melanjutkan.</p>
                        <div class="mt-3 flex flex-col sm:flex-row items-start gap-3">
                            <input type="file" id="import-file-input" class="w-full text-gray-700 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border file:text-sm file:font-semibold file:bg-gray-50 hover:file:bg-gray-100" accept=".sql" required>
                            <button type="button" onclick="confirmImport()" class="w-full sm:w-auto bg-yellow-500 text-white py-2 px-4 rounded-lg hover:bg-yellow-600 font-semibold transition-colors flex-shrink-0">
                                <i class="fas fa-database mr-2"></i> Impor
                            </button>
                        </div>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800">Backup & Reset</h3>
                        <p class="text-sm text-gray-600 mt-1">Unduh salinan database atau hapus data transaksi.</p>
                        <div class="mt-3 flex flex-col sm:flex-row gap-3">
                            <a href="aksi_pengaturan.php?action=backup" class="w-full sm:w-auto inline-flex items-center justify-center bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 font-semibold transition-colors">
                                <i class="fas fa-download mr-2"></i> Unduh Backup
                            </a>
                            <button type="button" onclick="confirmDeletion()" class="w-full sm:w-auto inline-flex items-center justify-center bg-red-600 text-white py-2 px-4 rounded-lg hover:bg-red-700 font-semibold transition-colors">
                                <i class="fas fa-trash-alt mr-2"></i> Hapus Data Transaksi
                            </button>
                            <!-- TOMBOL BARU UNTUK TES PRINTER -->
                            <button type="button" id="test-print-btn" class="w-full sm:w-auto inline-flex items-center justify-center bg-green-600 text-white py-2 px-4 rounded-lg hover:bg-green-700 font-semibold transition-colors">
                                <i class="fab fa-android mr-2"></i> Tes Printer RawBT
                            </button>
                        </div>
                    </div>
                </div>
                <form id="delete-form" action="aksi_pengaturan.php?action=delete_transactions" method="POST" style="display: none;"></form>
            </div>
        </div>
    </div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const tabs = document.querySelectorAll('.tab-btn');
        const panels = document.querySelectorAll('.tab-panel');
        const mainForm = document.getElementById('main-settings-form');

        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                const targetTab = tab.dataset.tab;
                
                tabs.forEach(item => item.classList.remove('active', 'text-gray-500', 'hover:text-gray-700'));
                panels.forEach(panel => {
                    panel.classList.remove('active');
                });
                
                tab.classList.add('active');
                tab.classList.remove('text-gray-500', 'hover:text-gray-700');
                
                const targetPanel = document.getElementById('tab-panel-' + targetTab);
                if(targetPanel) {
                    targetPanel.classList.add('active');
                    if (targetPanel.parentElement === mainForm.querySelector('.p-6')) {
                         mainForm.style.display = 'block';
                    } else {
                         mainForm.style.display = 'none';
                    }
                }
            });
        });
        
        function confirmDeletion() { if (confirm('APAKAH ANDA YAKIN?\n\nAnda akan menghapus semua riwayat penjualan. Tindakan ini tidak dapat dibatalkan.')) { document.getElementById('delete-form').submit(); } }
        
        function confirmImport() {
            const fileInput = document.getElementById('import-file-input');
            if (fileInput.files.length === 0) {
                alert('Silakan pilih file backup SQL terlebih dahulu.');
                return;
            }
            if (confirm('APAKAH ANDA YAKIN?\n\nAnda akan menimpa seluruh data saat ini dengan data dari file backup. Tindakan ini tidak dapat dibatalkan.')) {
                // Lanjutkan dengan logika impor
            }
        }
        
        // --- FUNGSI BARU UNTUK TES PRINTER ---
        const testPrintBtn = document.getElementById('test-print-btn');
        if (testPrintBtn) {
            testPrintBtn.addEventListener('click', () => {
                const testText = "Tes Cetak Berhasil!\n\nPrinter Anda terhubung dengan baik.\n\n--------------------------------\n";
                const encodedText = encodeURIComponent(testText);
                window.location.href = `rawbt:text=${encodedText}`;
            });
        }

        window.confirmDeletion = confirmDeletion;
        window.confirmImport = confirmImport;
    });
</script>

</body>
</html>
