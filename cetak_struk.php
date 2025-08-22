<?php
// cetak_struk.php (Versi Final dengan Perbaikan Cetak)
define('_KASIR_', true);
require_once 'config/database.php';

if (!isset($_GET['id']) || empty($_GET['id'])) die("Error: ID Pesanan tidak valid.");
$order_id = (int)$_GET['id'];

$settings_result = $conn->query("SELECT * FROM settings");
$settings = [];
while($row = $settings_result->fetch_assoc()) $settings[$row['setting_key']] = $row['setting_value'];

$stmt_order = $conn->prepare("SELECT * FROM orders WHERE id = ?");
$stmt_order->bind_param("i", $order_id);
$stmt_order->execute();
$order_result = $stmt_order->get_result();
if ($order_result->num_rows === 0) die("Pesanan tidak ditemukan.");
$order = $order_result->fetch_assoc();

$stmt_items = $conn->prepare("SELECT * FROM order_items WHERE order_id = ?");
$stmt_items->bind_param("i", $order_id);
$stmt_items->execute();
$items_result = $stmt_items->get_result();
$items_for_text = [];
while($item = $items_result->fetch_assoc()){
    $items_for_text[] = $item;
}

function buatGaris($char = '-') { return str_repeat($char, 32) . "\n"; }
function formatBaris($kiri, $kanan) {
    $lebar = 32;
    $panjang_kanan = strlen($kanan);
    $panjang_kiri = strlen($kiri);
    $spasi = $lebar - $panjang_kiri - $panjang_kanan;
    return $kiri . str_repeat(' ', $spasi > 0 ? $spasi : 0) . $kanan . "\n";
}

// ---- Teks untuk RawBT (tanpa tag HTML) ----
$plain_text_rawbt = "[C]" . ($settings['merchant_name'] ?? 'Toko Anda') . "\n";
$plain_text_rawbt .= "[C]" . ($settings['merchant_city'] ?? 'Kota Anda') . "\n";
$plain_text_rawbt .= "[C]" . date('d/m/Y H:i:s') . "\n";
$plain_text_rawbt .= buatGaris();
$plain_text_rawbt .= "Pelanggan: " . $order['customer_name'] . "\n";
$plain_text_rawbt .= "ID Pesanan: #" . $order['id'] . "\n";
$plain_text_rawbt .= buatGaris();
$subtotalStruk = 0;
foreach($items_for_text as $item) {
    $item_subtotal = $item['price'] * $item['quantity'];
    $subtotalStruk += $item_subtotal;
    $plain_text_rawbt .= $item['product_name'] . "\n";
    $plain_text_rawbt .= formatBaris("  " . $item['quantity'] . " x " . number_format($item['price']), number_format($item_subtotal));
}
$plain_text_rawbt .= buatGaris('=');
$plain_text_rawbt .= formatBaris("Subtotal", number_format($subtotalStruk));
$totalDiskon = array_reduce($items_for_text, fn($sum, $item) => $sum + $item['discount'], 0) + $order['global_discount'];
if ($totalDiskon > 0) $plain_text_rawbt .= formatBaris("Total Diskon", "-" . number_format($totalDiskon));
if ($order['admin_fee'] > 0) $plain_text_rawbt .= formatBaris("Biaya Admin", number_format($order['admin_fee']));
$plain_text_rawbt .= "[L]\n";
$plain_text_rawbt .= "[L]" . formatBaris("TOTAL", "Rp" . number_format($order['total_amount']));
$plain_text_rawbt .= buatGaris('=');
if ($order['payment_method'] === 'Tunai') {
    $plain_text_rawbt .= formatBaris("Tunai", number_format($order['cash_amount']));
    $plain_text_rawbt .= formatBaris("Kembali", number_format($order['change_amount']));
    $plain_text_rawbt .= buatGaris();
}
$plain_text_rawbt .= "[C]Terima Kasih!\n";
// ---------------------------------------------

echo "<div id='plain-text-receipt' data-text='" . htmlspecialchars(json_encode($plain_text_rawbt)) . "'></div>";
?>

<div id="modal-print-buttons" class="grid grid-cols-2 gap-3">
    <button id="print-direct-btn" class="bg-purple-600 text-white py-2 px-4 rounded-md hover:bg-purple-700"><i class="fas fa-bolt mr-2"></i>Langsung</button>
    <button id="print-rawbt-btn" class="bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700"><i class="fab fa-android mr-2"></i>RawBT</button>
    <button id="print-regular-btn" class="bg-gray-700 text-white py-2 px-4 rounded-md hover:bg-gray-800 col-span-2"><i class="fas fa-print mr-2"></i>Cetak Biasa (PDF/Printer)</button>
</div>

<div class="receipt-paper w-[302px] bg-white text-black p-4 font-mono text-xs leading-normal shadow-lg">
    <div class="header text-center">
        <h1 class="font-bold text-base uppercase"><?= htmlspecialchars($settings['merchant_name'] ?? 'Toko Anda') ?></h1>
        <p><?= htmlspecialchars($settings['merchant_city'] ?? 'Kota Anda') ?></p>
        <p><?= date('d/m/Y H:i:s') ?></p>
    </div>
    <div class="separator border-t border-dashed border-black my-2"></div>
    <div>Pelanggan: <?= htmlspecialchars($order['customer_name']) ?></div>
    <div>ID: #<?= $order_id ?> | <?= htmlspecialchars($order['payment_method']) ?></div>
    <div class="separator border-t border-dashed border-black my-2"></div>
    <table class="w-full">
        <tbody>
            <?php foreach($items_for_text as $item): $item_subtotal = $item['price'] * $item['quantity']; ?>
            <tr><td colspan="3"><?= htmlspecialchars($item['product_name']) ?></td></tr>
            <tr><td><?= $item['quantity'] ?>x @<?= number_format($item['price']) ?></td><td></td><td class="text-right"><?= number_format($item_subtotal) ?></td></tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <div class="separator border-t-2 border-solid border-black my-2"></div>
    <div class="total-section">
        <div class="flex justify-between"><span>Subtotal</span><span>Rp<?= number_format($subtotalStruk) ?></span></div>
        <?php if ($totalDiskon > 0): ?><div><span>Total Diskon</span><span>-Rp<?= number_format($totalDiskon) ?></span></div><?php endif; ?>
        <?php if ($order['admin_fee'] > 0): ?><div><span>Biaya Admin</span><span>Rp<?= number_format($order['admin_fee']) ?></span></div><?php endif; ?>
        <div class="grand-total font-bold text-lg mt-1 pt-1 border-t border-black flex justify-between">
            <span>TOTAL</span><span>Rp<?= number_format($order['total_amount']) ?></span>
        </div>
        <?php if ($order['payment_method'] === 'Tunai'): ?>
        <div class="payment-details mt-1 pt-1 border-t border-dashed border-black">
            <div class="flex justify-between"><span>Tunai</span><span>Rp<?= number_format($order['cash_amount']) ?></span></div>
            <div class="flex justify-between"><span>Kembali</span><span>Rp<?= number_format($order['change_amount']) ?></span></div>
        </div>
        <?php endif; ?>
    </div>
    <div class="separator border-t border-dashed border-black my-2"></div>
    <div class="footer text-center"><p>Terima Kasih!</p></div>
</div>

<style>
    @media print {
        body * { visibility: hidden; }
        .receipt-paper, .receipt-paper * { visibility: visible; }
        .receipt-paper { position: absolute; left: 0; top: 0; width: 100%; margin: 0; padding: 0; }
    }
</style>
