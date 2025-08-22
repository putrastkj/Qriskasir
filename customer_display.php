<?php
// customer_display.php
define('_KASIR_', true);
require_once 'config/database.php';

$settings_result = $conn->query("SELECT * FROM settings");
$settings = [];
while($row = $settings_result->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$products_result = $conn->query("SELECT name, price FROM products WHERE stock > 0 ORDER BY name ASC");
$all_products_text = '';
if ($products_result->num_rows > 0) {
    $product_items = [];
    while($product = $products_result->fetch_assoc()) {
        $product_items[] = htmlspecialchars($product['name']) . ' - Rp' . number_format($product['price'], 0, ',', '.');
    }
    $all_products_text = implode(' ✨ ', $product_items);
}

$ads = [];
for ($i = 1; $i <= 3; $i++) {
    $key = 'ad_image_' . $i;
    if (!empty($settings[$key]) && file_exists($settings[$key])) {
        $file_path = $settings[$key];
        $file_ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
        $type = in_array($file_ext, ['mp4', 'webm']) ? 'video' : 'image';
        $ads[] = ['path' => $file_path, 'type' => $type];
    }
}

$running_text_default = $settings['running_text'] ?? 'Selamat Datang di Toko Kami!';
$qris_background_path = $settings['qris_background_image'] ?? '';
if (!empty($qris_background_path) && !file_exists($qris_background_path)) {
    $qris_background_path = '';
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tampilan Pelanggan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap');
        body { font-family: 'Poppins', sans-serif; }
        .view { display: none; }
        .view.active { display: flex; }
        .ads-container { position: relative; width: 100%; height: 100%; }
        .ad-slide { position: absolute; top: 0; left: 0; width: 100%; height: 100%; opacity: 0; transition: opacity 1.5s ease-in-out; }
        .ad-slide.active { opacity: 1; }
        .ad-slide img, .ad-slide video { width: 100%; height: 100%; object-fit: contain; }
        .marquee-container { width: 100%; overflow: hidden; white-space: nowrap; }
        .marquee-content { display: inline-block; padding-left: 100%; animation: marquee 45s linear infinite; }
        @keyframes marquee { 0% { transform: translateX(0%); } 100% { transform: translateX(-100%); } }
        
        .qris-wrapper {
            position: relative; width: 340px; aspect-ratio: 320.675 / 451.556; 
            background-size: contain; background-repeat: no-repeat; background-position: center;
            color: black; text-align: center;
        }
        #qris-dynamic-code {
            position: absolute; top: 50%; left: 50%;
            transform: translate(-50%, -50%); width: 55%; 
        }
        #qris-merchant-name {
            position: absolute; top: 18%; left: 50%;
            transform: translateX(-50%); width: 100%;
            font-weight: 700; font-size: 1.1rem;
        }
        #qris-n-mid {
            position: absolute; top: 22%; left: 50%;
            transform: translateX(-50%); width: 100%;
            font-size: 0.8rem;
        }
         #qris-merchant-id {
            position: absolute; top: 25%; left: 50%;
            transform: translateX(-50%); width: 100%;
            font-weight: 0; font-size: 0.8rem;
        }
    </style>
</head>
<body class="bg-gray-900 text-white font-sans overflow-hidden">
    <!-- Tampilan 1: IDLE -->
    <div id="idle-view" class="view h-screen flex-col">
        <div class="flex-shrink-0 bg-gray-800 text-yellow-300 p-2 text-xl marquee-container"><div class="marquee-content"><span><?= $all_products_text ?></span></div></div>
        <div class="flex-grow flex flex-col justify-center items-center p-8 relative">
            <div class="ads-container">
                <?php if (!empty($ads)): ?>
                    <?php foreach ($ads as $index => $ad): ?>
                    <div class="ad-slide <?= $index === 0 ? 'active' : '' ?>">
                        <?php if ($ad['type'] === 'video'): ?><video src="<?= htmlspecialchars($ad['path']) ?>" autoplay muted loop playsinline></video>
                        <?php else: ?><img src="<?= htmlspecialchars($ad['path']) ?>" alt="Iklan"><?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="w-full h-full flex items-center justify-center text-6xl font-bold text-gray-700 tracking-widest"><?= htmlspecialchars($settings['merchant_name'] ?? 'TOKO ANDA') ?></div>
                <?php endif; ?>
            </div>
        </div>
        <div class="flex-shrink-0 bg-blue-600 text-white p-3 text-2xl marquee-container"><div class="marquee-content" style="animation-duration: 30s;"><span><?= htmlspecialchars($running_text_default) ?></span></div></div>
    </div>
    
    <!-- Tampilan 2: TRANSAKSI -->
    <div id="transaction-view" class="view h-screen flex-col"><div class="flex-grow flex flex-col justify-between p-8"><div><h1 class="text-4xl font-bold mb-6 text-center text-green-300">Rincian Belanja Anda</h1><div id="customer-cart-items" class="space-y-3 text-xl max-h-[55vh] overflow-y-auto pr-4"></div></div><div class="border-t-2 border-gray-700 pt-6 mt-4"><div class="space-y-3 text-2xl"><div class="flex justify-between"><span class="text-gray-400">Subtotal</span><span id="customer-subtotal">Rp0</span></div><div class="flex justify-between text-red-400"><span class="text-gray-400">Diskon</span><span id="customer-discount">-Rp0</span></div><div class="flex justify-between text-yellow-400"><span class="text-gray-400">Biaya Admin</span><span id="customer-admin-fee">+Rp0</span></div></div><div class="flex justify-between items-center mt-5 text-6xl font-bold border-t-4 border-gray-500 pt-5"><span class="text-green-400">TOTAL</span><span id="customer-grand-total" class="text-green-400">Rp0</span></div></div></div></div>

    <!-- Tampilan 3: PEMBAYARAN QRIS -->
    <div id="payment-view" class="view h-screen flex-col lg:flex-row">
        <div class="w-full lg:w-1/2 flex flex-col justify-between p-8">
            <div><h1 class="text-3xl lg:text-4xl font-bold mb-6 text-center text-green-300">Rincian Belanja</h1><div id="payment-cart-items" class="space-y-2 text-lg max-h-[50vh] lg:max-h-[55vh] overflow-y-auto pr-4"></div></div>
            <div class="border-t-2 border-gray-700 pt-4 mt-4"><div class="space-y-2 text-xl"><div class="flex justify-between"><span class="text-gray-400">Subtotal</span><span id="payment-subtotal">Rp0</span></div><div class="flex justify-between text-red-400"><span class="text-gray-400">Diskon</span><span id="payment-discount">-Rp0</span></div><div class="flex justify-between text-yellow-400"><span class="text-gray-400">Biaya Admin</span><span id="payment-admin-fee">+Rp0</span></div></div><div class="flex justify-between items-center mt-4 text-4xl lg:text-5xl font-bold border-t-4 border-gray-500 pt-4"><span class="text-green-400">TOTAL</span><span id="payment-grand-total" class="text-green-400">Rp0</span></div></div>
        </div>
        <div class="w-full lg:w-1/2 flex flex-col justify-center items-center text-center bg-gray-800 p-8">
            <h1 class="text-3xl lg:text-4xl font-bold mb-8 text-yellow-300">Scan untuk Membayar</h1>
            <div id="qris-container" class="qris-wrapper" style="background-image: url('<?= htmlspecialchars($qris_background_path) ?>');">
                <div id="qris-merchant-name"><?= htmlspecialchars($settings['merchant_name'] ?? '') ?></div>
                <div id="qris-n-mid">NMID: <?= htmlspecialchars($settings['qris_n-mid'] ?? '') ?></div>
                <div id="qris-merchant-id"><?= htmlspecialchars($settings['qris_merchant_id'] ?? '') ?></div>
                <img id="qris-dynamic-code" src="" alt="QRIS Code">
            </div>
        </div>
    </div>

    <!-- Tampilan 4: FEEDBACK (Terima Kasih) -->
    <div id="thankyou-view" class="view w-full h-full flex-col justify-center items-center text-center bg-green-500 p-8"><div class="text-white"><i class="fas fa-check-circle fa-8x mb-8"></i><h1 id="thankyou-message" class="text-6xl font-bold">Terima Kasih!</h1><p class="text-3xl mt-4">Silakan datang kembali</p></div></div>

    <script>
        const views = { idle: document.getElementById('idle-view'), transaction: document.getElementById('transaction-view'), payment: document.getElementById('payment-view'), thankyou: document.getElementById('thankyou-view') };
        let thankYouTimer;
        function switchView(activeView) { clearTimeout(thankYouTimer); for (const key in views) { views[key].classList.remove('active'); } if (views[activeView]) { views[activeView].classList.add('active'); } }
        let currentSlide = 0; const slides = document.querySelectorAll('.ad-slide');
        function nextSlide() { if (slides.length <= 1) return; slides[currentSlide].classList.remove('active'); currentSlide = (currentSlide + 1) % slides.length; slides[currentSlide].classList.add('active'); }
        if (slides.length > 1) setInterval(nextSlide, 10000);

        function populateCartDetails(data, viewPrefix) {
            const itemsContainer = document.getElementById(`${viewPrefix}-cart-items`); itemsContainer.innerHTML = '';
            data.cart.forEach(item => {
                const itemEl = document.createElement('div');
                itemEl.className = 'flex justify-between items-center bg-gray-800 p-3 rounded-lg shadow-md';
                const subtotal = item.price * item.quantity;
                itemEl.innerHTML = `<div><p class="font-semibold text-white">${item.name}</p><p class="text-gray-400">${item.quantity} x Rp${item.price.toLocaleString('id-ID')}</p></div><p class="font-semibold text-white">Rp${subtotal.toLocaleString('id-ID')}</p>`;
                itemsContainer.appendChild(itemEl);
            });
            document.getElementById(`${viewPrefix}-subtotal`).textContent = `Rp${(data.subtotal || 0).toLocaleString('id-ID')}`;
            document.getElementById(`${viewPrefix}-discount`).textContent = `-Rp${(data.totalDiscount || 0).toLocaleString('id-ID')}`;
            document.getElementById(`${viewPrefix}-admin-fee`).textContent = `+Rp${(data.adminFee || 0).toLocaleString('id-ID')}`;
            document.getElementById(`${viewPrefix}-grand-total`).textContent = `Rp${(data.grandTotal || 0).toLocaleString('id-ID')}`;
        }

        function renderDisplay(data) {
            if (!data) { switchView('idle'); return; }
            const status = data.status;
            switch (status) {
                case 'completed':
                    document.getElementById('thankyou-message').textContent = data.message || "Terima Kasih!";
                    switchView('thankyou');
                    thankYouTimer = setTimeout(() => { localStorage.removeItem('kasir_cart_data'); switchView('idle'); }, 5000);
                    break;
                case 'active_cart':
                    if (data.qrisCodeUrl) {
                        populateCartDetails(data, 'payment');
                        document.getElementById('qris-dynamic-code').src = data.qrisCodeUrl;
                        switchView('payment');
                    } else {
                        populateCartDetails(data, 'customer');
                        switchView('transaction');
                    }
                    break;
                default: switchView('idle'); break;
            }
        }
        window.addEventListener('storage', event => {
            if (event.key === 'kasir_cart_data') {
                try { const data = event.newValue ? JSON.parse(event.newValue) : null; renderDisplay(data); } 
                catch (e) { console.error("Gagal parsing data:", e); renderDisplay(null); }
            }
        });
        try { renderDisplay(JSON.parse(localStorage.getItem('kasir_cart_data'))); } 
        catch (e) { renderDisplay(null); }
    </script>
</body>
</html>