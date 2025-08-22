<?php
// admin.php (Dasbor Analitik Terpadu dengan Tab)
define('_KASIR_', true);
require_once 'auth_check.php';
require_once 'config/database.php';

// --- PENGATURAN FILTER ---
$filter_periode = $_GET['periode'] ?? '7hari';
$filter_kategori = $_GET['kategori'] ?? 'semua';
$filter_tanggal_mulai = $_GET['tanggal_mulai'] ?? '';
$filter_tanggal_akhir = $_GET['tanggal_akhir'] ?? '';

$where_conditions = ["o.payment_status = 'Lunas'"];
$params = [];
$types = '';

switch ($filter_periode) {
    case 'hari_ini': $where_conditions[] = "DATE(o.order_date) = CURDATE()"; break;
    case 'kemarin': $where_conditions[] = "DATE(o.order_date) = CURDATE() - INTERVAL 1 DAY"; break;
    case '7hari': $where_conditions[] = "o.order_date >= CURDATE() - INTERVAL 6 DAY"; break;
    case 'bulan_ini': $where_conditions[] = "YEAR(o.order_date) = YEAR(CURDATE()) AND MONTH(o.order_date) = MONTH(CURDATE())"; break;
    case 'custom':
        if (!empty($filter_tanggal_mulai) && !empty($filter_tanggal_akhir)) {
            $where_conditions[] = "DATE(o.order_date) BETWEEN ? AND ?";
            $params[] = $filter_tanggal_mulai;
            $params[] = $filter_tanggal_akhir;
            $types .= 'ss';
        }
        break;
}

if ($filter_kategori !== 'semua') {
    $where_conditions[] = "p.category = ?";
    $params[] = $filter_kategori;
    $types .= 's';
}

$where_clause = " WHERE " . implode(" AND ", $where_conditions);

$categories_result = $conn->query("SELECT name FROM categories ORDER BY name ASC");

// --- STATISTIK UTAMA ---
$summary_sql = "SELECT 
                    SUM(oi.quantity * oi.price) as total_omzet,
                    SUM(oi.quantity * oi.cost_price) as total_modal,
                    SUM(oi.quantity * (oi.price - oi.cost_price)) as total_keuntungan
                FROM order_items oi
                JOIN orders o ON oi.order_id = o.id
                LEFT JOIN products p ON oi.product_name = p.name
                $where_clause";
$stmt_summary = $conn->prepare($summary_sql);
if (!empty($params)) $stmt_summary->bind_param($types, ...$params);
$stmt_summary->execute();
$summary = $stmt_summary->get_result()->fetch_assoc();

$total_piutang = $conn->query("SELECT SUM(balance) as total FROM customers")->fetch_assoc()['total'] ?? 0;

// --- DATA UNTUK GRAFIK & TABEL ---
// Grafik Penjualan per Hari
$daily_sales_sql = "SELECT DATE(o.order_date) as tanggal, SUM(oi.price * oi.quantity) as omzet 
                    FROM orders o JOIN order_items oi ON o.id = oi.order_id LEFT JOIN products p ON oi.product_name = p.name " . $where_clause . " 
                    GROUP BY tanggal ORDER BY tanggal ASC";
$stmt_daily = $conn->prepare($daily_sales_sql);
if (!empty($params)) $stmt_daily->bind_param($types, ...$params);
$stmt_daily->execute();
$daily_sales_result = $stmt_daily->get_result();
$chart_harian_labels = []; $chart_harian_data = [];
while($row = $daily_sales_result->fetch_assoc()){
    $chart_harian_labels[] = date('d M', strtotime($row['tanggal']));
    $chart_harian_data[] = (float)$row['omzet'];
}

// Grafik Penjualan per Kategori
$category_sales_sql = "SELECT p.category, SUM(oi.quantity * oi.price) as omzet 
                       FROM order_items oi JOIN products p ON oi.product_name = p.name JOIN orders o ON oi.order_id = o.id " . $where_clause . "
                       GROUP BY p.category HAVING omzet > 0 ORDER BY omzet DESC";
$stmt_category = $conn->prepare($category_sales_sql);
if (!empty($params)) $stmt_category->bind_param($types, ...$params);
$stmt_category->execute();
$category_sales_result = $stmt_category->get_result();
$chart_kategori_labels = []; $chart_kategori_data = [];
while($row = $category_sales_result->fetch_assoc()){
    $chart_kategori_labels[] = $row['category'];
    $chart_kategori_data[] = (float)$row['omzet'];
}

// Grafik Penjualan per Jam
$hourly_sales_sql = "SELECT HOUR(o.order_date) as jam, SUM(oi.price * oi.quantity) as omzet 
                     FROM orders o JOIN order_items oi ON o.id = oi.order_id LEFT JOIN products p ON oi.product_name = p.name " . $where_clause . "
                     GROUP BY jam ORDER BY jam ASC";
$stmt_hourly = $conn->prepare($hourly_sales_sql);
if (!empty($params)) $stmt_hourly->bind_param($types, ...$params);
$stmt_hourly->execute();
$hourly_sales_result = $stmt_hourly->get_result();
$chart_jam_labels = array_map(fn($h) => str_pad($h, 2, '0', STR_PAD_LEFT) . ':00', range(0, 23));
$chart_jam_data = array_fill(0, 24, 0);
while($row = $hourly_sales_result->fetch_assoc()){
    $chart_jam_data[(int)$row['jam']] = (float)$row['omzet'];
}

// Grafik Metode Bayar
$payment_method_sql = "SELECT o.payment_method, COUNT(DISTINCT o.id) as jumlah 
                       FROM orders o LEFT JOIN order_items oi ON o.id = oi.order_id LEFT JOIN products p ON oi.product_name = p.name " . $where_clause . "
                       GROUP BY o.payment_method ORDER BY jumlah DESC";
$stmt_payment = $conn->prepare($payment_method_sql);
if (!empty($params)) $stmt_payment->bind_param($types, ...$params);
$stmt_payment->execute();
$payment_method_result = $stmt_payment->get_result();
$chart_payment_labels = []; $chart_payment_data = [];
while($row = $payment_method_result->fetch_assoc()){
    $chart_payment_labels[] = $row['payment_method'];
    $chart_payment_data[] = (int)$row['jumlah'];
}

// Top Produk Paling Menguntungkan
$sql_top_profit = "SELECT oi.product_name, SUM(oi.quantity) as total_terjual, SUM(oi.quantity * (oi.price - oi.cost_price)) as keuntungan_produk
                   FROM order_items oi JOIN orders o ON oi.order_id = o.id LEFT JOIN products p ON oi.product_name = p.name
                   $where_clause GROUP BY oi.product_name ORDER BY keuntungan_produk DESC LIMIT 5";
$stmt_top = $conn->prepare($sql_top_profit);
if (!empty($params)) $stmt_top->bind_param($types, ...$params);
$stmt_top->execute();
$top_products = $stmt_top->get_result();

// Notifikasi Stok
$low_stock_threshold = (int)($conn->query("SELECT setting_value FROM settings WHERE setting_key = 'low_stock_threshold'")->fetch_assoc()['setting_value'] ?? 10);
$low_stock_products = $conn->query("SELECT id, name, stock FROM products WHERE stock <= $low_stock_threshold AND stock > 0 ORDER BY stock ASC");
$out_of_stock_products = $conn->query("SELECT id, name FROM products WHERE stock = 0 ORDER BY name ASC");

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dasbor Admin - Aplikasi Kasir</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
    <style>
        .tab-btn.active { border-bottom-color: #3B82F6; color: #3B82F6; }
        .tab-panel { display: none; }
        .tab-panel.active { display: block; }
    </style>
</head>
<body class="bg-gray-100 font-sans">
    <div class="container mx-auto p-4 sm:p-6">
        <div class="flex flex-wrap justify-between items-center mb-6 gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">Dasbor Analitik</h1>
                <p class="text-gray-600">Selamat Datang, <span class="font-semibold"><?= htmlspecialchars($_SESSION['username']) ?></span>!</p>
            </div>
            <a href="logout.php" class="bg-red-500 text-white py-2 px-4 rounded-lg shadow hover:bg-red-600">
                <i class="fas fa-sign-out-alt mr-2"></i>Logout
            </a>
        </div>

        <div class="bg-white p-4 rounded-xl shadow-lg mb-6">
            <form id="filter-form" method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
                <div>
                    <label for="periode" class="block text-sm font-medium text-gray-700">Periode</label>
                    <select id="periode" name="periode" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm">
                        <option value="hari_ini" <?= $filter_periode == 'hari_ini' ? 'selected' : '' ?>>Hari Ini</option>
                        <option value="kemarin" <?= $filter_periode == 'kemarin' ? 'selected' : '' ?>>Kemarin</option>
                        <option value="7hari" <?= $filter_periode == '7hari' ? 'selected' : '' ?>>7 Hari Terakhir</option>
                        <option value="bulan_ini" <?= $filter_periode == 'bulan_ini' ? 'selected' : '' ?>>Bulan Ini</option>
                        <option value="custom" <?= $filter_periode == 'custom' ? 'selected' : '' ?>>Pilih Tanggal</option>
                    </select>
                </div>
                <div id="custom-date-range" class="<?= $filter_periode == 'custom' ? '' : 'hidden' ?> md:col-span-2 lg:col-span-1 grid grid-cols-2 gap-2">
                    <div>
                        <label for="tanggal_mulai" class="block text-sm font-medium text-gray-700">Dari</label>
                        <input type="date" name="tanggal_mulai" id="tanggal_mulai" value="<?= htmlspecialchars($filter_tanggal_mulai) ?>" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    </div>
                     <div>
                        <label for="tanggal_akhir" class="block text-sm font-medium text-gray-700">Sampai</label>
                        <input type="date" name="tanggal_akhir" id="tanggal_akhir" value="<?= htmlspecialchars($filter_tanggal_akhir) ?>" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    </div>
                </div>
                <div>
                    <label for="kategori" class="block text-sm font-medium text-gray-700">Kategori</label>
                    <select name="kategori" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm">
                        <option value="semua">Semua Kategori</option>
                        <?php while($cat = $categories_result->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($cat['name']) ?>" <?= $filter_kategori == $cat['name'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="w-full bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 flex items-center justify-center"><i class="fas fa-filter mr-2"></i>Terapkan</button>
                    <a href="admin.php" class="w-full bg-gray-400 text-white py-2 px-4 rounded-lg hover:bg-gray-500 flex items-center justify-center">Reset</a>
                </div>
            </form>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white p-6 rounded-xl shadow-lg"><h2 class="text-gray-500 text-sm">Total Omzet (Lunas)</h2><p class="text-2xl font-bold text-blue-600 mt-1">Rp<?= number_format($summary['total_omzet'] ?? 0, 0, ',', '.') ?></p></div>
            <div class="bg-white p-6 rounded-xl shadow-lg"><h2 class="text-gray-500 text-sm">Total Modal</h2><p class="text-2xl font-bold text-gray-800 mt-1">Rp<?= number_format($summary['total_modal'] ?? 0, 0, ',', '.') ?></p></div>
            <div class="bg-white p-6 rounded-xl shadow-lg"><h2 class="text-gray-500 text-sm">Estimasi Keuntungan</h2><p class="text-2xl font-bold text-green-600 mt-1">Rp<?= number_format($summary['total_keuntungan'] ?? 0, 0, ',', '.') ?></p></div>
            <div class="bg-white p-6 rounded-xl shadow-lg"><h2 class="text-gray-500 text-sm">Total Piutang</h2><p class="text-2xl font-bold text-red-600 mt-1">Rp<?= number_format($total_piutang, 0, ',', '.') ?></p></div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2">
                <div class="bg-white rounded-xl shadow-lg">
                    <div class="border-b border-gray-200">
                        <nav class="flex -mb-px" aria-label="Tabs">
                            <button type="button" class="tab-btn active w-1/2 py-4 px-1 text-center border-b-2 font-medium text-sm" data-tab="laporan-utama">Laporan Utama</button>
                            <button type="button" class="tab-btn w-1/2 py-4 px-1 text-center border-b-2 font-medium text-sm text-gray-500 hover:text-gray-700" data-tab="analisis-lanjutan">Analisis Lanjutan</button>
                        </nav>
                    </div>
                    <div class="p-6">
                        <div id="tab-panel-laporan-utama" class="tab-panel active space-y-8">
                            <div><h2 class="text-xl font-bold text-gray-700 mb-4">Grafik Tren Penjualan</h2><canvas id="salesChart"></canvas></div>
                            <div><h2 class="text-xl font-bold text-gray-700 mb-4">Analisis Jam Sibuk</h2><canvas id="hourlySalesChart"></canvas></div>
                        </div>
                        <div id="tab-panel-analisis-lanjutan" class="tab-panel space-y-8">
                            <div><h2 class="text-xl font-bold text-gray-700 mb-4">Pendapatan per Kategori</h2><canvas id="categorySalesChart"></canvas></div>
                            <div><h2 class="text-xl font-bold text-gray-700 mb-4">Popularitas Metode Pembayaran</h2><canvas id="paymentMethodChart"></canvas></div>
                            <div>
                                <h2 class="text-xl font-bold text-gray-700 mb-4">Top 5 Produk Paling Menguntungkan</h2>
                                <table class="min-w-full bg-white"><thead class="bg-gray-50"><tr><th class="p-3 text-sm font-semibold text-left text-gray-600">Produk</th><th class="p-3 text-sm font-semibold text-center text-gray-600">Terjual</th><th class="p-3 text-sm font-semibold text-right text-gray-600">Total Keuntungan</th></tr></thead><tbody class="text-gray-700 divide-y">
                                <?php if ($top_products->num_rows > 0): while($row = $top_products->fetch_assoc()): ?><tr><td class="p-3 font-semibold"><?= htmlspecialchars($row['product_name']) ?></td><td class="p-3 text-center"><?= $row['total_terjual'] ?></td><td class="p-3 text-right font-bold text-green-600">Rp<?= number_format($row['keuntungan_produk'], 0, ',', '.') ?></td></tr><?php endwhile; else: ?><tr><td colspan="3" class="text-center p-6 text-gray-500">Tidak ada data.</td></tr><?php endif; ?>
                                </tbody></table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-1 space-y-4">
                 <a href="index.php" class="flex items-center bg-blue-600 text-white p-4 rounded-lg shadow-md hover:bg-blue-700 transition-transform hover:-translate-y-1"><i class="fas fa-cash-register text-3xl"></i><span class="ml-4 font-semibold text-lg">Buka Kasir</span></a>
                
                <div id="stock-notification-panel">
                    <?php if ($low_stock_products->num_rows > 0 || $out_of_stock_products->num_rows > 0): ?>
                    <div class="bg-white p-4 rounded-xl shadow-lg">
                        <h2 class="text-lg font-bold text-gray-700 mb-2 border-b pb-2 flex items-center"><i class="fas fa-exclamation-triangle text-yellow-500 mr-3"></i>Notifikasi Stok</h2>
                        <div class="max-h-48 overflow-y-auto space-y-2 text-sm pr-2">
                            <?php while($product = $low_stock_products->fetch_assoc()): ?>
                                <button class="open-stock-modal-btn w-full text-left bg-yellow-100 text-yellow-800 p-2 rounded-md flex justify-between items-center hover:bg-yellow-200" data-id="<?= $product['id'] ?>" data-name="<?= htmlspecialchars($product['name']) ?>" data-stock="<?= $product['stock'] ?>"><span><?= htmlspecialchars($product['name']) ?></span><span class="font-bold">Sisa <?= $product['stock'] ?></span></button>
                            <?php endwhile; ?>
                            <?php while($product = $out_of_stock_products->fetch_assoc()): ?>
                                 <button class="open-stock-modal-btn w-full text-left bg-red-100 text-red-800 p-2 rounded-md flex justify-between items-center hover:bg-red-200" data-id="<?= $product['id'] ?>" data-name="<?= htmlspecialchars($product['name']) ?>" data-stock="0"><span><?= htmlspecialchars($product['name']) ?></span><span class="font-bold">Habis</span></button>
                            <?php endwhile; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <div class="bg-white p-4 rounded-xl shadow-lg space-y-3">
                    <h2 class="text-lg font-bold text-gray-700 mb-2 border-b pb-2">Menu Manajemen</h2>
                    <a href="admin_produk.php" class="flex items-center text-gray-600 hover:text-blue-600 hover:bg-gray-50 p-2 rounded-md transition-colors"><i class="fas fa-box w-6 text-center mr-3"></i>Manajemen Produk</a>
                    <a href="admin_kategori.php" class="flex items-center text-gray-600 hover:text-blue-600 hover:bg-gray-50 p-2 rounded-md transition-colors"><i class="fas fa-folder-open w-6 text-center mr-3"></i>Manajemen Kategori</a>
                    <a href="update_harga.php" class="flex items-center text-gray-600 hover:text-blue-600 hover:bg-gray-50 p-2 rounded-md transition-colors"><i class="fas fa-tags w-6 text-center mr-3"></i>Update Produk Massal</a>
                    <a href="admin_pelanggan.php" class="flex items-center text-gray-600 hover:text-blue-600 hover:bg-gray-50 p-2 rounded-md transition-colors"><i class="fas fa-book-reader w-6 text-center mr-3"></i>Manajemen Piutang</a>
                    <a href="admin_pengguna.php" class="flex items-center text-gray-600 hover:text-blue-600 hover:bg-gray-50 p-2 rounded-md transition-colors"><i class="fas fa-user-shield w-6 text-center mr-3"></i>Manajemen Pengguna</a>
                    <a href="pengaturan_printer.php" class="flex items-center text-gray-600 hover:text-blue-600 hover:bg-gray-50 p-2 rounded-md transition-colors"><i class="fas fa-print w-6 text-center mr-3"></i>Panduan Printer</a>
                    <a href="pengaturan.php" class="flex items-center text-gray-600 hover:text-blue-600 hover:bg-gray-50 p-2 rounded-md transition-colors"><i class="fas fa-cogs w-6 text-center mr-3"></i>Pengaturan Toko</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div id="add-stock-modal" class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-sm m-4">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">Tambah Stok</h2>
            <form id="add-stock-form">
                <div class="mb-4"><p class="text-gray-600">Produk: <span id="modal-product-name" class="font-bold"></span></p><p class="text-gray-600">Stok Saat Ini: <span id="modal-current-stock" class="font-bold"></span></p></div>
                <input type="hidden" id="modal-product-id" name="product_id">
                <div><label for="quantity_to_add" class="block text-gray-700 font-semibold mb-2">Jumlah yang Ditambahkan</label><input type="number" id="quantity_to_add" name="quantity_to_add" class="w-full px-4 py-2 border rounded-lg" required min="1"></div>
                <div class="mt-8 flex justify-end space-x-3"><button type="button" id="cancel-add-stock-btn" class="bg-gray-300 text-gray-800 py-2 px-6 rounded-md hover:bg-gray-400">Batal</button><button type="submit" class="bg-green-600 text-white py-2 px-6 rounded-md hover:bg-green-700">Simpan</button></div>
            </form>
        </div>
    </div>
    
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const periodeSelect = document.getElementById('periode');
        const customDateRange = document.getElementById('custom-date-range');
        periodeSelect.addEventListener('change', function() {
            if (this.value === 'custom') { customDateRange.classList.remove('hidden'); } 
            else { customDateRange.classList.add('hidden'); }
        });

        // Logika Tab
        const tabs = document.querySelectorAll('.tab-btn');
        const panels = document.querySelectorAll('.tab-panel');
        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                tabs.forEach(item => item.classList.remove('active', 'text-gray-500', 'hover:text-gray-700'));
                panels.forEach(panel => panel.classList.remove('active'));
                tab.classList.add('active');
                tab.classList.remove('text-gray-500', 'hover:text-gray-700');
                document.getElementById('tab-panel-' + tab.dataset.tab).classList.add('active');
            });
        });

        const rupiahFormatter = (value) => 'Rp' + new Intl.NumberFormat('id-ID').format(value);
        Chart.register(ChartDataLabels);

        new Chart(document.getElementById('salesChart'), {
            type: 'line',
            data: { labels: <?= json_encode($chart_harian_labels) ?>, datasets: [{ label: 'Omzet (Rp)', data: <?= json_encode($chart_harian_data) ?>, backgroundColor: 'rgba(59, 130, 246, 0.1)', borderColor: 'rgba(59, 130, 246, 1)', borderWidth: 3, tension: 0.4, fill: true }] },
            options: { scales: { y: { beginAtZero: true, ticks: { callback: rupiahFormatter } } }, plugins: { tooltip: { callbacks: { label: (context) => rupiahFormatter(context.raw) } } } }
        });

        new Chart(document.getElementById('categorySalesChart'), {
            type: 'pie',
            data: { labels: <?= json_encode($chart_kategori_labels) ?>, datasets: [{ data: <?= json_encode($chart_kategori_data) ?>, backgroundColor: ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899'] }] },
            options: { plugins: { tooltip: { callbacks: { label: (context) => `${context.label}: ${rupiahFormatter(context.raw)}` } }, datalabels: { formatter: (value, ctx) => { let sum = ctx.chart.data.datasets[0].data.reduce((a, b) => a + b, 0); let percentage = (value * 100 / sum).toFixed(1) + "%"; return percentage; }, color: '#fff' } } }
        });

        new Chart(document.getElementById('hourlySalesChart'), {
            type: 'bar',
            data: { labels: <?= json_encode($chart_jam_labels) ?>, datasets: [{ label: 'Omzet per Jam (Rp)', data: <?= json_encode($chart_jam_data) ?>, backgroundColor: 'rgba(245, 158, 11, 0.6)', borderColor: 'rgba(245, 158, 11, 1)', borderWidth: 1 }] },
            options: { scales: { y: { beginAtZero: true, ticks: { callback: rupiahFormatter } } }, plugins: { tooltip: { callbacks: { label: (context) => rupiahFormatter(context.raw) } } } }
        });

        new Chart(document.getElementById('paymentMethodChart'), {
            type: 'doughnut',
            data: { labels: <?= json_encode($chart_payment_labels) ?>, datasets: [{ label: 'Jumlah Transaksi', data: <?= json_encode($chart_payment_data) ?>, backgroundColor: ['#8B5CF6', '#10B981', '#3B82F6', '#F59E0B'] }] },
            options: { plugins: { tooltip: { callbacks: { label: (context) => `${context.label}: ${context.raw} transaksi` } }, datalabels: { formatter: (value, ctx) => { let sum = ctx.chart.data.datasets[0].data.reduce((a, b) => a + b, 0); let percentage = (value * 100 / sum).toFixed(1) + "%"; return percentage; }, color: '#fff' } } }
        });

        const stockNotificationPanel = document.getElementById('stock-notification-panel');
        const addStockModal = document.getElementById('add-stock-modal');
        const addStockForm = document.getElementById('add-stock-form');
        const cancelAddStockBtn = document.getElementById('cancel-add-stock-btn');

        stockNotificationPanel.addEventListener('click', function(e) {
            const target = e.target.closest('.open-stock-modal-btn');
            if (target) {
                document.getElementById('modal-product-id').value = target.dataset.id;
                document.getElementById('modal-product-name').textContent = target.dataset.name;
                document.getElementById('modal-current-stock').textContent = target.dataset.stock;
                document.getElementById('quantity_to_add').value = '';
                addStockModal.classList.remove('hidden');
                document.getElementById('quantity_to_add').focus();
            }
        });

        cancelAddStockBtn.addEventListener('click', () => {
            addStockModal.classList.add('hidden');
        });

        addStockForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const submitButton = this.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';

            fetch('aksi_update_stok.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const productId = formData.get('product_id');
                    const notificationItem = stockNotificationPanel.querySelector(`[data-id="${productId}"]`);
                    if (notificationItem) {
                        const stockSpan = notificationItem.querySelector('span:last-child');
                        stockSpan.textContent = `Sisa ${data.new_stock}`;
                        notificationItem.dataset.stock = data.new_stock;
                        if (data.new_stock > <?= $low_stock_threshold ?>) {
                            notificationItem.remove();
                        }
                    }
                    alert('Sukses: ' + data.message);
                    addStockModal.classList.add('hidden');
                } else {
                    alert('Gagal: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan. Silakan coba lagi.');
            })
            .finally(() => {
                submitButton.disabled = false;
                submitButton.innerHTML = 'Simpan';
            });
        });
    });
</script>

</body>
</html>
