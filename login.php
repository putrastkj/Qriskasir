<?php
// login.php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

define('_KASIR_', true);

if (isset($_SESSION['user_id'])) {
    header("Location: admin.php");
    exit();
}

require_once 'config/database.php';

// Ambil Nama Toko dan Gambar Latar
$settings_result = $conn->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('merchant_name', 'login_background_image')");
$settings = [];
while($row = $settings_result->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
$merchant_name = $settings['merchant_name'] ?? 'Aplikasi Kasir';
$login_bg_image = $settings['login_background_image'] ?? 'https://images.unsplash.com/photo-1570857502907-f80b788d94a6?q=80&w=1887&auto=format&fit=crop';
if (!file_exists($login_bg_image)) {
    $login_bg_image = 'https://images.unsplash.com/photo-1570857502907-f80b788d94a6?q=80&w=1887&auto=format&fit=crop';
}


$pesan_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['username']) || empty($_POST['password'])) {
        $pesan_error = "Username dan password tidak boleh kosong.";
    } else {
        $username = $_POST['username'];
        $password = $_POST['password'];

        $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                header("Location: admin.php");
                exit();
            } else {
                $pesan_error = "Username atau password salah.";
            }
        } else {
            $pesan_error = "Username atau password salah.";
        }
        $stmt->close();
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= htmlspecialchars($merchant_name) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap');
        body { font-family: 'Poppins', sans-serif; }
    </style>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="w-full max-w-4xl bg-white rounded-2xl shadow-lg overflow-hidden grid grid-cols-1 md:grid-cols-2">
            
            <div class="hidden md:block relative">
                <img src="<?= htmlspecialchars($login_bg_image) ?>" 
                     alt="Ilustrasi Toko" 
                     class="w-full h-full object-cover">
                <div class="absolute inset-0 bg-blue-600 bg-opacity-50"></div>
                <div class="absolute inset-0 flex flex-col justify-end p-8 text-white">
                    <h2 class="text-3xl font-bold"><?= htmlspecialchars($merchant_name) ?></h2>
                    <p class="mt-2">Sistem manajemen kasir yang andal dan mudah digunakan.</p>
                </div>
            </div>

            <div class="p-8 md:p-12 flex flex-col justify-center">
                <div class="text-center mb-6 md:hidden">
                    <i class="fas fa-store-alt fa-3x text-blue-500"></i>
                    <h1 class="text-2xl font-bold text-gray-800 mt-4">Selamat Datang</h1>
                </div>
                <h2 class="text-2xl font-bold text-gray-800 mb-2 hidden md:block">Login ke Akun Anda</h2>
                <p class="text-gray-600 mb-6">Silakan masukkan kredensial Anda.</p>

                <?php if (!empty($pesan_error)): ?>
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg" role="alert">
                        <p><?= htmlspecialchars($pesan_error) ?></p>
                    </div>
                <?php endif; ?>

                <form action="login.php" method="POST" class="space-y-6">
                    <div>
                        <label for="username" class="block text-sm font-semibold text-gray-700 mb-2">Username</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-user text-gray-400"></i>
                            </div>
                            <input type="text" name="username" id="username" class="w-full pl-10 pr-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        </div>
                    </div>
                    <div>
                        <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">Password</label>
                         <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-lock text-gray-400"></i>
                            </div>
                            <input type="password" name="password" id="password" class="w-full pl-10 pr-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        </div>
                    </div>
                    <button type="submit" class="w-full bg-blue-600 text-white py-3 rounded-lg hover:bg-blue-700 font-semibold transition-colors flex items-center justify-center">
                        <i class="fas fa-sign-in-alt mr-2"></i>
                        Masuk
                    </button>
                </form>
                 <div class="text-center mt-6">
                    <p class="text-sm text-gray-500">&copy; <?= date('Y') ?> <?= htmlspecialchars($merchant_name) ?></p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
