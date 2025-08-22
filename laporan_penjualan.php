<?php
// laporan_penjualan.php
define('_KASIR_', true);
require_once 'auth_check.php';
require_once 'config/database.php';

// --- PENGATURAN FILTER ---
$filter_periode = $_GET['periode'] ?? '7hari';
$filter_kategori = $_GET['kategori'] ?? 'semua';
$filter_tanggal_mulai = $_GET['tanggal_mulai'] ?? '';
$filter_tanggal_akhir = $_GET['tanggal_akhir'] ?? '';

// Kondisi default: hanya tampilkan yang lunas
$where_conditions = ["o.payment_status = 'Lunas'"];
$params = [];
$types = '';

// Logika Filter Periode
switch ($filter_periode) {
    case 'hari_ini':
        $where_conditions[] = "DATE(o.order_date) = CURDATE()";
        break;
    case 'kemarin':
        $where_conditions[] = "DATE(o.order_date) = CURDATE() - INTERVAL 1 DAY";
        break;
    case '7hari':
        $where_conditions[] = "o.order_date >= CURDATE() - INTERVAL 6 DAY";
        break;
    case 'bulan_ini':
        $where_conditions[] = "YEAR(o.order_date) = YEAR(CURDATE()) AND MONTH(o.order_date) = MONTH(CURDATE())";
        break;
    case 'custom':
        if (!empty($filter_tanggal_mulai) && !empty($filter_tanggal_akhir)) {
            $where_conditions[] = "DATE(o.order_date) BETWEEN ? AND ?";
            $params[] = $filter_tanggal_mulai;
            $params[] = $filter_tanggal_akhir;
            $types .= 'ss';
        }
        break;
}

// Logika Filter Kategori
if ($filter_kategori !== 'semua') {
    $where_conditions[] = "oi.product_name IN (SELECT name FROM products WHERE category = ?)";
    $params[] = $filter_kategori;
    $types .= 's';
}

$where_clause = " WHERE " . implode(" AND ", $where_conditions);

// Ambil daftar kategori untuk filter dropdown
$categories_result = $conn->query("SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != '' ORDER BY category ASC");

// --- STATISTIK UTAMA (mengikuti filter) ---
$stats_sql = "SELECT COUNT(DISTINCT o.id) as total_pesanan, SUM(oi.price * oi.quantity) as total_omzet 
              FROM orders o JOIN order_items oi ON o.id = oi.order_id " . $where_clause;
$stmt_stats = $conn->prepare($stats_sql);
if (!empty($params)) $stmt_stats->bind_param($types, ...$params);
$stmt_stats->execute();
$stats_result = $stmt_stats->get_result()->fetch_assoc();
$total_pesanan = $stats_result['total_pesanan'] ?? 0;
$total_omzet = $stats_result['total_omzet'] ?? 0;

// --- DATA UNTUK GRAFIK (semua grafik mengikuti filter) ---

// 1. Grafik Penjualan per Hari (Line Chart)
$daily_sales_sql = "SELECT DATE(o.order_date) as tanggal, SUM(oi.price * oi.quantity) as omzet 
                    FROM orders o JOIN order_items oi ON o.id = oi.order_id " . $where_clause . " 
                    GROUP BY tanggal ORDER BY tanggal ASC";
$stmt_daily = $conn->prepare($daily_sales_sql);
if (!empty($params)) $stmt_daily->bind_param($types, ...$params);
$stmt_daily->execute();
$daily_sales_result = $stmt_daily->get_result();
$chart_harian_labels = [];
$chart_harian_data = [];
while($row = $daily_sales_result->fetch_assoc()){
    $chart_harian_labels[] = date('d M Y', strtotime($row['tanggal']));
    $chart_harian_data[] = (float)$row['omzet'];
}

// 2. Grafik Penjualan per Kategori (Pie Chart)
$category_sales_sql = "SELECT p.category, SUM(oi.quantity * oi.price) as omzet 
                       FROM order_items oi 
                       JOIN products p ON oi.product_name = p.name 
                       JOIN orders o ON oi.order_id = o.id " . $where_clause . "
                       GROUP BY p.category HAVING omzet > 0 ORDER BY omzet DESC";
$stmt_category = $conn->prepare($category_sales_sql);
if (!empty($params)) $stmt_category->bind_param($types, ...$params);
$stmt_category->execute();
$category_sales_result = $stmt_category->get_result();
$chart_kategori_labels = [];
$chart_kategori_data = [];
while($row = $category_sales_result->fetch_assoc()){
    $chart_kategori_labels[] = $row['category'];
    $chart_kategori_data[] = (float)$row['omzet'];
}

// 3. Grafik Penjualan per Jam (Bar Chart)
$hourly_sales_sql = "SELECT HOUR(o.order_date) as jam, SUM(oi.price * oi.quantity) as omzet 
                     FROM orders o JOIN order_items oi ON o.id = oi.order_id " . $where_clause . "
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

// 4. Grafik Metode Bayar (Doughnut Chart)
$payment_method_sql = "SELECT o.payment_method, COUNT(DISTINCT o.id) as jumlah 
                       FROM orders o LEFT JOIN order_items oi ON o.id = oi.order_id " . $where_clause . "
                       GROUP BY o.payment_method ORDER BY jumlah DESC";
$stmt_payment = $conn->prepare($payment_method_sql);
if (!empty($params)) $stmt_payment->bind_param($types, ...$params);
$stmt_payment->execute();
$payment_method_result = $stmt_payment->get_result();
$chart_payment_labels = [];
$chart_payment_data = [];
while($row = $payment_method_result->fetch_assoc()){
    $chart_payment_labels[] = $row['payment_method'];
    $chart_payment_data[] = (int)$row['jumlah'];
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dasbor Laporan Penjualan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
</head>
<body class="bg-gray-100 font-sans">
    <div class="container mx-auto p-4 sm:p-6">
        <div class="flex flex-wrap justify-between items-center mb-6 gap-4">
             <h1 class="text-3xl font-bold text-gray-800">Dasbor Laporan</h1>
             <a href="admin.php" class="bg-indigo-600 text-white py-2 px-4 rounded-lg shadow-sm hover:bg-indigo-700 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>Kembali ke Dasbor
            </a>
        </div>

        <!-- Filter Section -->
        <div class="bg-white p-4 rounded-xl shadow-lg mb-6">
            <form id="filter-form" method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
                <div>
                    <label for="periode" class="block text-sm font-medium text-gray-700">Periode</label>
                    <select id="periode" name="periode" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
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
                            <option value="<?= htmlspecialchars($cat['category']) ?>" <?= $filter_kategori == $cat['category'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['category']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="w-full bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 flex items-center justify-center"><i class="fas fa-filter mr-2"></i>Terapkan</button>
                    <a href="laporan_penjualan.php" class="w-full bg-gray-400 text-white py-2 px-4 rounded-lg hover:bg-gray-500 flex items-center justify-center">Reset</a>
                </div>
            </form>
        </div>
        
        <!-- Statistik Utama -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div class="bg-blue-500 text-white p-6 rounded-xl shadow-lg"><h2 class="text-lg font-semibold">Total Omzet Terfilter</h2><p class="text-3xl font-bold mt-2">Rp<?= number_format($total_omzet, 0, ',', '.') ?></p></div>
            <div class="bg-green-500 text-white p-6 rounded-xl shadow-lg"><h2 class="text-lg font-semibold">Total Transaksi Terfilter</h2><p class="text-3xl font-bold mt-2"><?= $total_pesanan ?></p></div>
        </div>

        <!-- Grafik Penjualan per Hari -->
        <div class="bg-white p-6 rounded-xl shadow-lg mb-8">
            <h2 class="text-xl font-bold text-gray-700 mb-4">Grafik Tren Penjualan Harian</h2>
            <canvas id="dailySalesChart"></canvas>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- Grafik per Kategori -->
            <div class="bg-white p-6 rounded-xl shadow-lg">
                <h2 class="text-xl font-bold text-gray-700 mb-4">Pendapatan per Kategori</h2>
                <canvas id="categorySalesChart"></canvas>
            </div>
            <!-- Grafik Metode Bayar -->
             <div class="bg-white p-6 rounded-xl shadow-lg">
                <h2 class="text-xl font-bold text-gray-700 mb-4">Popularitas Metode Pembayaran</h2>
                <canvas id="paymentMethodChart"></canvas>
            </div>
        </div>
        
        <!-- Grafik Penjualan per Jam -->
        <div class="bg-white p-6 rounded-xl shadow-lg">
            <h2 class="text-xl font-bold text-gray-700 mb-4">Analisis Jam Sibuk</h2>
            <canvas id="hourlySalesChart"></canvas>
        </div>
    </div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const periodeSelect = document.getElementById('periode');
        const customDateRange = document.getElementById('custom-date-range');
        periodeSelect.addEventListener('change', function() {
            if (this.value === 'custom') {
                customDateRange.classList.remove('hidden');
            } else {
                customDateRange.classList.add('hidden');
            }
        });

        const rupiahFormatter = (value) => 'Rp' + new Intl.NumberFormat('id-ID').format(value);
        Chart.register(ChartDataLabels);

        new Chart(document.getElementById('dailySalesChart'), { 
            type: 'line',
            data: { 
                labels: <?= json_encode($chart_harian_labels) ?>, 
                datasets: [{ 
                    label: 'Omzet (Rp)', 
                    data: <?= json_encode($chart_harian_data) ?>, 
                    borderColor: 'rgba(59, 130, 246, 1)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    borderWidth: 2, tension: 0.4, fill: true
                }] 
            }, 
            options: { scales: { y: { beginAtZero: true, ticks: { callback: rupiahFormatter } } }, plugins: { tooltip: { callbacks: { label: (context) => rupiahFormatter(context.raw) } } } }
        });

        new Chart(document.getElementById('categorySalesChart'), {
            type: 'pie',
            data: {
                labels: <?= json_encode($chart_kategori_labels) ?>,
                datasets: [{
                    label: 'Omzet',
                    data: <?= json_encode($chart_kategori_data) ?>,
                    backgroundColor: ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899'],
                }]
            },
            options: {
                plugins: {
                    tooltip: { callbacks: { label: (context) => `${context.label}: ${rupiahFormatter(context.raw)}` } },
                    datalabels: {
                        formatter: (value, ctx) => {
                            let sum = ctx.chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                            let percentage = (value * 100 / sum).toFixed(1) + "%";
                            return percentage;
                        },
                        color: '#fff',
                    }
                }
            }
        });

        new Chart(document.getElementById('hourlySalesChart'), {
            type: 'bar',
            data: {
                labels: <?= json_encode($chart_jam_labels) ?>,
                datasets: [{
                    label: 'Omzet per Jam (Rp)',
                    data: <?= json_encode($chart_jam_data) ?>,
                    backgroundColor: 'rgba(245, 158, 11, 0.6)',
                    borderColor: 'rgba(245, 158, 11, 1)',
                    borderWidth: 1
                }]
            },
            options: { scales: { y: { beginAtZero: true, ticks: { callback: rupiahFormatter } } }, plugins: { tooltip: { callbacks: { label: (context) => rupiahFormatter(context.raw) } } } }
        });

        new Chart(document.getElementById('paymentMethodChart'), {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($chart_payment_labels) ?>,
                datasets: [{
                    label: 'Jumlah Transaksi',
                    data: <?= json_encode($chart_payment_data) ?>,
                     backgroundColor: ['#8B5CF6', '#10B981', '#3B82F6', '#F59E0B', '#EF4444', '#EC4899'],
                }]
            },
             options: {
                plugins: {
                    tooltip: { callbacks: { label: (context) => `${context.label}: ${context.raw} transaksi` } },
                     datalabels: {
                        formatter: (value, ctx) => {
                            let sum = ctx.chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                            let percentage = (value * 100 / sum).toFixed(1) + "%";
                            return percentage;
                        },
                        color: '#fff',
                    }
                }
            }
        });
    });
</script>
</body>
</html>
