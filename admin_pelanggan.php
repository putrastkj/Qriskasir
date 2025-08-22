<?php
define('_KASIR_', true);
require_once 'config/database.php';
require_once 'auth_check.php';

$limit = 15;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

$total_result = $conn->query("SELECT COUNT(id) as total FROM customers");
$total_customers = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_customers / $limit);

$stmt = $conn->prepare("SELECT * FROM customers ORDER BY id DESC LIMIT ? OFFSET ?");
$stmt->bind_param("ii", $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();

// Ambil nama toko dari pengaturan untuk pesan WhatsApp
$merchant_name = $conn->query("SELECT setting_value FROM settings WHERE setting_key = 'merchant_name'")->fetch_assoc()['setting_value'] ?? 'Toko Anda';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Pelanggan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 font-sans">
    <div class="container mx-auto p-4 sm:p-6">
        <div class="flex flex-wrap justify-between items-center mb-6 gap-4">
            <h1 class="text-3xl font-bold text-gray-800">Manajemen Pelanggan</h1>
            <a href="admin.php" class="bg-indigo-600 text-white py-2 px-4 rounded-lg shadow-sm hover:bg-indigo-700 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>Kembali ke Dasbor
            </a>
        </div>

        <div id="notification-area"></div>

        <div class="bg-white p-4 sm:p-6 rounded-xl shadow-lg">
            <div class="flex justify-between items-center mb-6">
                <a href="form_pelanggan.php" class="bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition-colors flex items-center justify-center">
                    <i class="fas fa-plus mr-2"></i> Tambah Pelanggan
                </a>
            </div>

            <div class="space-y-4">
                <?php if ($result->num_rows > 0) : while ($customer = $result->fetch_assoc()) : ?>
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 flex flex-col sm:flex-row items-start sm:items-center gap-4">
                        <div class="flex-grow">
                            <h3 class="font-bold text-lg text-gray-800"><?= htmlspecialchars($customer['name']) ?></h3>
                            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-gray-500 mt-1">
                                <span><i class="fas fa-phone-alt mr-2"></i><?= htmlspecialchars($customer['phone'] ?? '-') ?></span>
                                <?php if($customer['balance'] > 0): ?>
                                <span class="font-bold text-red-600" id="balance-<?= $customer['id'] ?>"><i class="fas fa-exclamation-circle mr-1"></i>Utang: Rp<?= number_format($customer['balance'], 0, ',', '.') ?></span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="flex-shrink-0 flex items-stretch gap-2 w-full sm:w-auto">
                            <?php if($customer['balance'] > 0): ?>
                            <button class="open-payment-modal-btn w-full text-center bg-green-500 text-white hover:bg-green-600 font-semibold py-2 px-4 rounded-md text-sm transition-colors"
                                    data-id="<?= $customer['id'] ?>" data-name="<?= htmlspecialchars($customer['name']) ?>" data-balance="<?= $customer['balance'] ?>">
                                <i class="fas fa-money-bill-wave mr-2"></i>Bayar
                            </button>
                            <?php if(!empty($customer['phone'])): ?>
                            <button class="send-wa-reminder-btn w-full text-center bg-teal-500 text-white hover:bg-teal-600 font-semibold py-2 px-4 rounded-md text-sm transition-colors"
                                    data-id="<?= $customer['id'] ?>" data-name="<?= htmlspecialchars($customer['name']) ?>" data-phone="<?= htmlspecialchars($customer['phone']) ?>" data-balance="<?= $customer['balance'] ?>">
                                <i class="fab fa-whatsapp mr-2"></i>Tagih
                            </button>
                            <?php endif; ?>
                            <?php endif; ?>
                            <a href="form_pelanggan.php?id=<?= $customer['id'] ?>" class="w-full text-center bg-yellow-400 text-yellow-900 hover:bg-yellow-500 font-semibold py-2 px-4 rounded-md text-sm transition-colors"><i class="fas fa-edit mr-2"></i>Edit</a>
                            <a href="aksi_pelanggan.php?action=delete&id=<?= $customer['id'] ?>" class="w-full text-center bg-red-500 text-white hover:bg-red-600 font-semibold py-2 px-4 rounded-md text-sm transition-colors" onclick="return confirm('Yakin ingin menghapus pelanggan ini?')"><i class="fas fa-trash-alt mr-2"></i>Hapus</a>
                        </div>
                    </div>
                <?php endwhile; else : ?>
                    <div class="text-center p-10 text-gray-500 border-2 border-dashed rounded-lg">
                        <i class="fas fa-users fa-3x mb-3"></i>
                        <p class="font-semibold">Belum ada data pelanggan.</p>
                    </div>
                <?php endif; ?>
            </div>
        
            <div class="mt-6 flex justify-center">
                <nav class="flex items-center space-x-1">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?= $i ?>" class="py-2 px-4 <?= ($i == $page) ? 'bg-blue-600 text-white' : 'bg-white' ?> border rounded-md hover:bg-gray-100 text-gray-700"><?= $i ?></a>
                    <?php endfor; ?>
                </nav>
            </div>
        </div>
    </div>

    <!-- Modal Pembayaran Utang -->
    <div id="payment-modal" class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-3xl m-4 flex flex-col">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">Bayar Utang</h2>
            <div class="mb-4">
                <p class="text-gray-600">Pelanggan: <span id="modal-customer-name" class="font-bold"></span></p>
                <p class="text-gray-600">Total Utang: <span id="modal-current-balance" class="font-bold text-red-600"></span></p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 border-t pt-4 mb-4">
                <div>
                    <h3 class="font-semibold text-gray-700 mb-2">Riwayat Transaksi Belum Lunas:</h3>
                    <div id="modal-unpaid-orders" class="space-y-2 max-h-48 overflow-y-auto text-sm"></div>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-700 mb-2">Riwayat Pembayaran:</h3>
                    <div id="modal-payment-history" class="space-y-2 max-h-48 overflow-y-auto text-sm"></div>
                </div>
            </div>

            <form id="payment-form">
                <input type="hidden" id="modal-customer-id" name="customer_id">
                <div>
                    <label for="amount" class="block text-gray-700 font-semibold mb-2">Jumlah Pembayaran (Cicilan)</label>
                    <input type="number" name="amount" id="amount" class="w-full px-4 py-2 border rounded-lg" required min="1">
                </div>
                <div class="mt-8 flex justify-end space-x-3">
                    <button type="button" id="cancel-payment-btn" class="bg-gray-300 text-gray-800 py-2 px-6 rounded-md hover:bg-gray-400">Batal</button>
                    <button type="submit" class="bg-green-600 text-white py-2 px-6 rounded-md hover:bg-green-700">Konfirmasi</button>
                </div>
            </form>
        </div>
    </div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const paymentModal = document.getElementById('payment-modal');
    const paymentForm = document.getElementById('payment-form');
    const cancelPaymentBtn = document.getElementById('cancel-payment-btn');
    const notificationArea = document.getElementById('notification-area');
    const unpaidOrdersDiv = document.getElementById('modal-unpaid-orders');
    const paymentHistoryDiv = document.getElementById('modal-payment-history');
    const merchantName = "<?= htmlspecialchars($merchant_name) ?>";

    document.body.addEventListener('click', async (e) => {
        const paymentButton = e.target.closest('.open-payment-modal-btn');
        const waButton = e.target.closest('.send-wa-reminder-btn');
        const lunasButton = e.target.closest('.send-lunas-wa-btn');

        if (paymentButton) {
            const customerId = paymentButton.dataset.id;
            document.getElementById('modal-customer-id').value = customerId;
            document.getElementById('modal-customer-name').textContent = paymentButton.dataset.name;
            const balance = parseFloat(paymentButton.dataset.balance);
            document.getElementById('modal-current-balance').textContent = `Rp${balance.toLocaleString('id-ID')}`;
            const amountInput = document.getElementById('amount');
            amountInput.value = '';
            amountInput.max = balance;
            
            unpaidOrdersDiv.innerHTML = '<p>Memuat...</p>';
            paymentHistoryDiv.innerHTML = '<p>Memuat...</p>';
            paymentModal.classList.remove('hidden');
            amountInput.focus();

            // Ambil dan tampilkan detail transaksi utang
            fetch(`aksi_bayar_utang.php?action=get_unpaid&customer_id=${customerId}`)
                .then(res => res.json())
                .then(data => {
                    unpaidOrdersDiv.innerHTML = '';
                    if (data.success && data.orders.length > 0) {
                        data.orders.forEach(order => {
                            const orderDiv = document.createElement('div');
                            orderDiv.className = 'bg-gray-100 p-3 rounded-md';
                            
                            let itemsHtml = '';
                            order.items.forEach(item => {
                                itemsHtml += `<li class="text-xs text-gray-600 ml-4">${item.product_name} (x${item.quantity})</li>`;
                            });

                            orderDiv.innerHTML = `
                                <div class="flex justify-between font-semibold">
                                    <span>Pesanan #${order.id} (${order.order_date_formatted})</span>
                                    <span>Rp${parseFloat(order.total_amount).toLocaleString('id-ID')}</span>
                                </div>
                                <ul class="list-disc list-inside">${itemsHtml}</ul>
                            `;
                            unpaidOrdersDiv.appendChild(orderDiv);
                        });
                    } else {
                        unpaidOrdersDiv.innerHTML = '<p>Tidak ada riwayat utang.</p>';
                    }
                });

            // Ambil dan tampilkan riwayat pembayaran
            fetch(`aksi_bayar_utang.php?action=get_payment_history&customer_id=${customerId}`)
                .then(res => res.json())
                .then(data => {
                    paymentHistoryDiv.innerHTML = '';
                    if (data.success && data.payments.length > 0) {
                        data.payments.forEach(payment => {
                            const paymentDiv = document.createElement('div');
                            paymentDiv.className = 'bg-green-50 p-2 rounded-md flex justify-between';
                            paymentDiv.innerHTML = `<span>${payment.payment_date_formatted}</span> <span class="font-semibold text-green-700">+Rp${parseFloat(payment.amount_paid).toLocaleString('id-ID')}</span>`;
                            paymentHistoryDiv.appendChild(paymentDiv);
                        });
                    } else {
                        paymentHistoryDiv.innerHTML = '<p>Belum ada pembayaran.</p>';
                    }
                });
        }

        if (waButton) {
            waButton.disabled = true;
            waButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            
            const customerId = waButton.dataset.id;
            const customerName = waButton.dataset.name;
            let phone = waButton.dataset.phone;
            const balance = parseFloat(waButton.dataset.balance).toLocaleString('id-ID');

            try {
                const response = await fetch(`aksi_bayar_utang.php?action=get_unpaid&customer_id=${customerId}`);
                const data = await response.json();
                
                let message = `Halo Bapak/Ibu ${customerName},\n\nKami dari *${merchantName}* ingin menginformasikan bahwa Anda memiliki tagihan yang belum lunas sebesar *Rp${balance}*.\n\nBerikut adalah rincian transaksi Anda:\n`;
                if (data.success && data.orders.length > 0) {
                    data.orders.forEach(order => {
                        message += `\n--- *Pesanan #${order.id}* ---\n`;
                        message += `Tanggal: ${order.order_date_formatted} jam ${order.order_time}\n`;
                        order.items.forEach(item => {
                            message += `- ${item.product_name} (x${item.quantity})\n`;
                        });
                        message += `*Total: Rp${parseFloat(order.total_amount).toLocaleString('id-ID')}*\n`;
                    });
                }
                message += "\nMohon untuk segera melakukan pembayaran. Terima kasih atas perhatiannya.";
                
                if (phone.startsWith('0')) {
                    phone = '62' + phone.substring(1);
                }
                const waLink = `https://wa.me/${phone}?text=${encodeURIComponent(message)}`;
                window.open(waLink, '_blank');

            } catch (error) {
                showNotification('Gagal mengambil detail utang.', 'error');
            } finally {
                waButton.disabled = false;
                waButton.innerHTML = '<i class="fab fa-whatsapp mr-2"></i>Tagih';
            }
        }

        if (lunasButton) {
            const customerName = lunasButton.dataset.name;
            let phone = lunasButton.dataset.phone;
            const message = `Halo Bapak/Ibu ${customerName},\n\nKami dari *${merchantName}* ingin memberitahukan bahwa pembayaran Anda telah kami terima dan seluruh tagihan Anda telah LUNAS.\n\nTerima kasih atas kerja samanya!`;

            if (phone.startsWith('0')) {
                phone = '62' + phone.substring(1);
            }
            const waLink = `https://wa.me/${phone}?text=${encodeURIComponent(message)}`;
            window.open(waLink, '_blank');
        }
    });

    cancelPaymentBtn.addEventListener('click', () => {
        paymentModal.classList.add('hidden');
    });

    paymentForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const submitButton = this.querySelector('button[type="submit"]');
        submitButton.disabled = true;
        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';

        fetch('aksi_bayar_utang.php?action=pay', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            showNotification(data.message, data.success ? 'success' : 'error');
            if (data.success) {
                const customerRow = document.querySelector(`.open-payment-modal-btn[data-id="${data.customer_id}"]`)?.closest('.bg-gray-50');
                const balanceSpan = document.getElementById(`balance-${data.customer_id}`);
                const paymentButton = customerRow?.querySelector(`.open-payment-modal-btn`);
                const waButton = customerRow?.querySelector(`.send-wa-reminder-btn`);
                
                if (data.is_fully_paid) {
                    balanceSpan?.remove();
                    paymentButton?.remove();
                    waButton?.remove();
                    if(data.customer_phone){
                        const buttonContainer = customerRow.querySelector('.flex-shrink-0');
                        const lunasButton = document.createElement('button');
                        lunasButton.className = 'send-lunas-wa-btn w-full text-center bg-blue-500 text-white hover:bg-blue-600 font-semibold py-2 px-4 rounded-md text-sm transition-colors';
                        lunasButton.dataset.phone = data.customer_phone;
                        lunasButton.dataset.name = document.getElementById('modal-customer-name').textContent;
                        lunasButton.innerHTML = '<i class="fab fa-whatsapp mr-2"></i>Notifikasi Lunas';
                        buttonContainer.prepend(lunasButton);
                    }
                } else {
                    if (balanceSpan) {
                        balanceSpan.innerHTML = `<i class="fas fa-exclamation-circle mr-1"></i>Utang: Rp${data.new_balance.toLocaleString('id-ID')}`;
                    }
                    if (paymentButton) {
                        paymentButton.dataset.balance = data.new_balance;
                    }
                }
                paymentModal.classList.add('hidden');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Terjadi kesalahan. Silakan coba lagi.', 'error');
        })
        .finally(() => {
            submitButton.disabled = false;
            submitButton.innerHTML = 'Konfirmasi';
        });
    });

    function showNotification(message, type) {
        const bgColor = type === 'success' ? 'bg-green-100 border-green-500 text-green-700' : 'bg-red-100 border-red-500 text-red-700';
        const notification = document.createElement('div');
        notification.className = `border-l-4 p-4 mb-6 rounded-lg shadow-sm ${bgColor}`;
        notification.innerHTML = `<p>${message}</p>`;
        notificationArea.appendChild(notification);
        setTimeout(() => notification.remove(), 5000);
    }
});
</script>
</body>
</html>
