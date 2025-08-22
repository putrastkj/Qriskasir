// kasir/js/utang.js

document.addEventListener('DOMContentLoaded', () => {
    // Elemen-elemen yang berhubungan dengan fitur utang
    const debtPaymentBtn = document.getElementById('debt-payment-btn');
    const customerSearchInput = document.getElementById('customer-search');
    const selectedCustomerIdInput = document.getElementById('selected-customer-id');
    const searchResultsContainer = document.getElementById('search-results');
    const clearCustomerBtn = document.getElementById('clear-customer-btn');
    const paymentMethodInput = document.getElementById('payment-method-input');

    // Fungsi untuk mengaktifkan/menonaktifkan tombol "Utang"
    function checkDebtButton() {
        // Tombol "Utang" hanya aktif jika ada pelanggan yang dipilih
        if (selectedCustomerIdInput.value) {
            debtPaymentBtn.disabled = false;
        } else {
            debtPaymentBtn.disabled = true;
            // Jika metode "Utang" sedang aktif tapi pelanggan dihapus,
            // otomatis pindah ke metode pembayaran lain (misal: QRIS)
            if (paymentMethodInput.value === 'Utang') {
                document.querySelector('.payment-method-btn[data-value="QRIS"]').click();
            }
        }
    }

    // Tambahkan event listener ke elemen-elemen terkait
    if (customerSearchInput) {
        customerSearchInput.addEventListener('input', checkDebtButton);
    }

    if (searchResultsContainer) {
        // Gunakan jeda singkat untuk memastikan nilai input sudah diperbarui
        searchResultsContainer.addEventListener('click', () => setTimeout(checkDebtButton, 100));
    }

    if (clearCustomerBtn) {
        clearCustomerBtn.addEventListener('click', checkDebtButton);
    }

    // Lakukan pengecekan awal saat halaman dimuat
    checkDebtButton();
});
