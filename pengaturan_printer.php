<?php
define('_KASIR_', true);
require_once 'auth_check.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan Printer Bluetooth</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 font-sans">
    <div class="container mx-auto p-4 sm:p-6">
        <div class="max-w-2xl mx-auto">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold text-gray-800 flex items-center">
                    <i class="fas fa-bluetooth-b mr-3"></i> 
                    Pengaturan Printer
                </h1>
                <a href="admin.php" class="text-sm text-gray-600 hover:text-blue-600">
                    <i class="fas fa-arrow-left mr-2"></i>Kembali
                </a>
            </div>

            <div class="bg-white p-6 sm:p-8 rounded-xl shadow-lg space-y-6">
                <div>
                    <h2 class="text-xl font-semibold text-gray-800 border-b pb-2 mb-4">
                        Printer Bluetooth Tersimpan
                    </h2>
                    <div class="bg-blue-50 border border-blue-200 p-4 rounded-lg flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Nama Printer:</p>
                            <p id="saved-printer-name" class="font-bold text-lg text-blue-800">Belum ada printer</p>
                        </div>
                        <button id="forget-printer-btn" class="bg-red-500 text-white text-sm py-1 px-3 rounded-md hover:bg-red-600 hidden">
                            <i class="fas fa-trash-alt"></i> Lupakan
                        </button>
                    </div>
                </div>

                <div class="border-t pt-6">
                     <h2 class="text-xl font-semibold text-gray-800 mb-2">
                        Hubungkan Printer Baru
                    </h2>
                    <p class="text-sm text-gray-600 mb-4">Pastikan printer thermal Anda sudah menyala dan terhubung (paired) dengan HP/tablet Anda melalui menu Bluetooth bawaan Android.</p>
                    <button id="search-printers-btn" class="w-full bg-blue-600 text-white py-3 rounded-lg hover:bg-blue-700 font-semibold transition-colors flex items-center justify-center">
                        <i class="fas fa-search mr-2"></i> Cari & Simpan Printer Bluetooth
                    </button>
                    <div id="printer-list" class="mt-4"></div>
                </div>
            </div>
        </div>
    </div>
<script src="js/printer.js"></script>
</body>
</html>
