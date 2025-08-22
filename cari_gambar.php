<?php
// cari_gambar.php
define('_KASIR_', true);
require_once 'auth_check.php';
require_once 'config/database.php';

header('Content-Type: application/json');

// Ambil kunci API dari database
$settings_result = $conn->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('google_api_key', 'google_search_engine_id')");
$api_settings = [];
while($row = $settings_result->fetch_assoc()) {
    $api_settings[$row['setting_key']] = $row['setting_value'];
}

$apiKey = $api_settings['google_api_key'] ?? '';
$searchEngineId = $api_settings['google_search_engine_id'] ?? '';
$query = $_GET['q'] ?? '';

if (empty($query) || empty($apiKey) || empty($searchEngineId)) {
    echo json_encode(['error' => 'Query pencarian kosong atau kunci API belum diatur di halaman Pengaturan.']);
    exit();
}

$url = sprintf(
    "https://www.googleapis.com/customsearch/v1?q=%s&key=%s&cx=%s&searchType=image&num=10",
    urlencode($query),
    $apiKey,
    $searchEngineId
);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$result = curl_exec($ch);
curl_close($ch);

$data = json_decode($result, true);
$images = [];

if (isset($data['error']) && $data['error']['code'] == 429) {
    echo json_encode(['error' => 'Batas pencarian harian (kuota) telah tercapai. Silakan coba lagi besok.']);
    exit();
}

if (isset($data['items'])) {
    foreach ($data['items'] as $item) {
        $images[] = $item['link'];
    }
}

echo json_encode($images);
?>
