document.addEventListener('DOMContentLoaded', () => {
    let cart = [];
    let printAfterSave = false;

    // Elemen DOM utama
    const cartItemsContainer = document.getElementById('cart-items'),
        orderForm = document.getElementById('order-form'),
        cartDataInput = document.getElementById('cart-data'),
        globalDiscountInput = document.getElementById('global-discount-input'),
        adminFeeInput = document.getElementById('admin-fee-input'),
        qrisPaymentSection = document.getElementById('qris-payment-section');
    
    const paymentMethodButtons = document.getElementById('payment-method-buttons');
    const paymentMethodInput = document.getElementById('payment-method-input');

    const qrisCanvas = document.getElementById('qris-canvas');
    const qrcodePlaceholder = document.getElementById('qrcode-placeholder');

    // Elemen Modal
    const manualProductModal = document.getElementById('manual-product-modal'),
        openManualProductModalBtn = document.getElementById('open-manual-product-modal'),
        manualProductForm = document.getElementById('manual-product-form'),
        cancelManualProductBtn = document.getElementById('cancel-manual-product-btn');
    const cashModal = document.getElementById('cash-modal'),
        modalTotalDisplay = document.getElementById('modal-total-display'),
        modalCashInput = document.getElementById('modal-cash-input'),
        modalChangeDisplay = document.getElementById('modal-change-display'),
        modalConfirmBtn = document.getElementById('modal-confirm-btn'),
        modalCancelBtn = document.getElementById('modal-cancel-btn');
    const scannerModal = document.getElementById('scanner-modal'),
        startScanBtn = document.getElementById('start-scan-btn'),
        closeScannerBtn = document.getElementById('close-scanner-btn');
    const printPreviewModal = document.getElementById('print-preview-modal'),
        receiptContentDiv = document.getElementById('receipt-content'),
        printActionButtonsDiv = document.getElementById('print-action-buttons'),
        closePrintModalBtn = document.getElementById('close-print-modal-btn');

    // Elemen Tab Mobile
    const tabProduk = document.getElementById('tab-produk'),
        tabKeranjang = document.getElementById('tab-keranjang'),
        panelProduk = document.getElementById('panel-produk'),
        panelKeranjang = document.getElementById('panel-keranjang'),
        cartBadge = document.getElementById('cart-badge');

    // Elemen Hold Bill
    const holdOrderBtn = document.getElementById('hold-order-btn');
    const openHeldOrdersBtn = document.getElementById('open-held-orders-btn');
    const heldOrdersModal = document.getElementById('held-orders-modal');
    const closeHeldOrdersModalBtn = document.getElementById('close-held-orders-modal-btn');
    const heldOrdersListContainer = document.getElementById('held-orders-list');
    
    // Elemen Pencarian Pelanggan & Utang
    const debtPaymentBtn = document.getElementById('debt-payment-btn');
    const customerSearchInput = document.getElementById('customer-search');
    const selectedCustomerIdInput = document.getElementById('selected-customer-id');
    const searchResultsContainer = document.getElementById('search-results');
    const selectedCustomerInfo = document.getElementById('selected-customer-info');
    const selectedCustomerName = document.getElementById('selected-customer-name');
    const clearCustomerBtn = document.getElementById('clear-customer-btn');
    let searchTimeout;

    function showToast(message, type = 'success') {
        const toastContainer = document.getElementById('toast-container');
        if (!toastContainer) return;
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.textContent = message;
        toastContainer.appendChild(toast);
        setTimeout(() => { toast.classList.add('show'); }, 100);
        setTimeout(() => { toast.classList.remove('show'); toast.addEventListener('transitionend', () => toast.remove()); }, 3000);
    }

    function pad(n) { return n < 10 ? '0' + n : n.toString() } function toCRC16(c) { let a = 65535; for (let d = 0; d < c.length; d++) { a ^= c.charCodeAt(d) << 8; for (let b = 0; b < 8; b++)a = a & 32768 ? (a << 1) ^ 4129 : a << 1 } let e = (a & 65535).toString(16).toUpperCase(); return e.length === 3 ? "0" + e : e } function makeString(c, { nominal: d } = {}) { if (!c || !d) return ""; let a = c.slice(0, -4).replace("010211", "010212"), e = a.split("5802ID"), f = "54" + pad(d.toString().length) + d; f += "5802ID"; let b = e[0].trim() + f + e[1].trim(); return b += toCRC16(b), b }

    function updateCartAndDisplay() {
        if (cart.length === 0) {
            localStorage.removeItem('kasir_cart_data');
            cartItemsContainer.innerHTML = '<p class="text-gray-500 text-center pt-10">Keranjang masih kosong</p>';
            cartBadge.classList.add('hidden');
            document.getElementById('summary-subtotal').textContent = 'Rp0';
            document.getElementById('summary-total-discount').textContent = '-Rp0';
            document.getElementById('summary-admin-fee').textContent = '+Rp0';
            document.getElementById('summary-grand-total').textContent = 'Rp0';
            return;
        }

        cartItemsContainer.innerHTML = '';
        cart.forEach((item, index) => {
            const itemElement = document.createElement('div');
            itemElement.className = 'flex justify-between items-center text-sm border-b border-gray-100 py-2';
            let subtotal = item.price * item.quantity - (item.discount || 0);
            
            itemElement.innerHTML = `
                <div class="flex-grow">
                    <p class="font-semibold">${item.name}</p>
                    <p class="text-gray-600">Rp${item.price.toLocaleString('id-ID')}</p>
                    ${(item.discount || 0) > 0 ? `<p class="text-red-500 text-xs">- Disc. Rp${item.discount.toLocaleString('id-ID')}</p>` : ''}
                </div>
                <div class="flex items-center">
                    <div class="flex items-center border rounded-md">
                        <button class="quantity-change font-bold w-7 h-7 text-lg" data-index="${index}" data-action="decrease">-</button>
                        <span class="px-2">${item.quantity}</span>
                        <button class="quantity-change font-bold w-7 h-7 text-lg" data-index="${index}" data-action="increase">+</button>
                    </div>
                    <p class="font-semibold w-24 text-right">Rp${subtotal.toLocaleString('id-ID')}</p>
                    <button class="item-discount-btn text-blue-500 hover:text-blue-700 w-8 text-center" data-index="${index}"><i class="fas fa-tag"></i></button>
                    <button class="remove-item text-red-500 hover:text-red-700 w-8 text-center" data-index="${index}"><i class="fas fa-trash-alt"></i></button>
                </div>
            `;
            cartItemsContainer.appendChild(itemElement);
        });
        cartBadge.textContent = cart.reduce((sum, item) => sum + item.quantity, 0);
        cartBadge.classList.remove('hidden');

        const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        const totalItemDiscount = cart.reduce((sum, item) => sum + (item.discount || 0), 0);
        const globalDiscount = parseFloat(globalDiscountInput.value) || 0;
        const adminFee = parseFloat(adminFeeInput.value) || 0;
        const grandTotal = subtotal - totalItemDiscount - globalDiscount + adminFee;

        document.getElementById('summary-subtotal').textContent = `Rp${subtotal.toLocaleString('id-ID')}`;
        document.getElementById('summary-total-discount').textContent = `-Rp${(totalItemDiscount + globalDiscount).toLocaleString('id-ID')}`;
        document.getElementById('summary-admin-fee').textContent = `+Rp${adminFee.toLocaleString('id-ID')}`;
        document.getElementById('summary-grand-total').textContent = `Rp${grandTotal.toLocaleString('id-ID')}`;
        cartDataInput.value = JSON.stringify(cart);

        const dataForCustomerDisplay = {
            status: 'active_cart',
            cart: cart, subtotal: subtotal, totalDiscount: totalItemDiscount + globalDiscount,
            adminFee: adminFee, grandTotal: grandTotal, qrisCodeUrl: null
        };

        if (paymentMethodInput.value === 'QRIS' && grandTotal > 0) {
            const qrisInput = document.getElementById("qrisInput").value.trim();
            const qrisDinamis = makeString(qrisInput, { nominal: grandTotal });
            qrisCanvas.style.display = 'block'; qrcodePlaceholder.style.display = 'none';
            QRCode.toCanvas(qrisCanvas, qrisDinamis, { margin: 2, width: 256 }, (err) => {
                if (err) { console.error(err); return; }
                dataForCustomerDisplay.qrisCodeUrl = qrisCanvas.toDataURL();
                localStorage.setItem('kasir_cart_data', JSON.stringify(dataForCustomerDisplay));
            });
        } else {
            qrisCanvas.style.display = 'none'; qrcodePlaceholder.style.display = 'block';
            const context = qrisCanvas.getContext('2d');
            context.clearRect(0, 0, qrisCanvas.width, qrisCanvas.height);
            localStorage.setItem('kasir_cart_data', JSON.stringify(dataForCustomerDisplay));
        }
    }

    function togglePaymentSections() {
        qrisPaymentSection.style.display = (paymentMethodInput.value === 'QRIS') ? 'block' : 'none';
        updateCartAndDisplay();
    }

    async function showPrintPreview(orderId) {
        receiptContentDiv.innerHTML = '<p class="text-center p-4">Memuat struk...</p>';
        printActionButtonsDiv.innerHTML = '<button id="close-print-modal-btn" class="bg-gray-500 text-white py-2 px-4 rounded-md hover:bg-gray-600 col-span-2">Tutup</button>';
        document.getElementById('close-print-modal-btn').onclick = () => printPreviewModal.classList.add('hidden');
        printPreviewModal.classList.remove('hidden');

        try {
            const response = await fetch(`cetak_struk.php?id=${orderId}`);
            if (!response.ok) throw new Error('Network response was not ok.');
            const html = await response.text();
            
            receiptContentDiv.innerHTML = html;
            
            const actionButtons = receiptContentDiv.querySelector('#modal-print-buttons');
            const plainTextDiv = receiptContentDiv.querySelector('#plain-text-receipt');
            
            if (actionButtons) {
                printActionButtonsDiv.innerHTML = '';
                printActionButtonsDiv.appendChild(actionButtons);
                printActionButtonsDiv.innerHTML += '<button id="close-print-modal-btn" class="bg-gray-500 text-white py-2 px-4 rounded-md hover:bg-gray-600 col-span-2">Tutup</button>';
            }

            document.getElementById('close-print-modal-btn').onclick = () => printPreviewModal.classList.add('hidden');
            
            printActionButtonsDiv.querySelector('#print-regular-btn')?.addEventListener('click', () => {
                window.print();
            });

            printActionButtonsDiv.querySelector('#print-rawbt-btn')?.addEventListener('click', () => {
                const plainText = JSON.parse(plainTextDiv.dataset.text);
                const encodedText = encodeURIComponent(plainText.replace(/\n/g, '[L]\n'));
                window.location.href = `rawbt:text=${encodedText}`;
            });

            printActionButtonsDiv.querySelector('#print-direct-btn')?.addEventListener('click', async () => {
                const savedPrinter = localStorage.getItem('preferred_printer');
                if (!savedPrinter) { alert('Belum ada printer yang disimpan.'); return; }
                if (!navigator.bluetooth) { alert('Browser Anda tidak mendukung Web Bluetooth.'); return; }
                try {
                    const encoder = new TextEncoder();
                    const data = encoder.encode(JSON.parse(plainTextDiv.dataset.text));
                    const printerInfo = JSON.parse(savedPrinter);
                    const device = await navigator.bluetooth.requestDevice({
                        filters: [{ services: ['000018f0-0000-1000-8000-00805f9b34fb'], name: printerInfo.name }]
                    });
                    const server = await device.gatt.connect();
                    const service = await server.getPrimaryService('000018f0-0000-1000-8000-00805f9b34fb');
                    const characteristic = await service.getCharacteristic('00002af1-0000-1000-8000-00805f9b34fb');
                    await characteristic.writeValue(data);
                    showToast('Data berhasil dikirim ke printer!', 'success');
                } catch(error) {
                    alert('Gagal mencetak: ' + error.message);
                }
            });

        } catch (error) {
            console.error('Fetch error:', error);
            receiptContentDiv.innerHTML = '<p class="text-center text-red-500 p-4">Gagal memuat struk. Coba lagi.</p>';
        }
    }
    closePrintModalBtn.addEventListener('click', () => printPreviewModal.classList.add('hidden'));

    function processOrder(cashAmount = 0, changeAmount = 0) {
        if (cart.length === 0) { showToast('Keranjang kosong!', 'error'); return; }
        const finalGrandTotal = document.getElementById('summary-grand-total').textContent;
        const formData = new FormData(orderForm);
        formData.append('global_discount', globalDiscountInput.value || 0);
        formData.append('admin_fee', adminFeeInput.value || 0);
        formData.append('payment_method', paymentMethodInput.value);
        formData.append('cash_amount', cashAmount);
        formData.append('change_amount', changeAmount);
        
        fetch('proses_pesanan.php', { method: 'POST', body: formData }).then(response => response.json()).then(data => {
            if (data.success && data.order_id) {
                showToast('Pesanan berhasil disimpan!');
                localStorage.setItem('kasir_cart_data', JSON.stringify({ status: 'completed', message: 'Pembayaran Berhasil!', grandTotal: finalGrandTotal }));
                
                if (printAfterSave) {
                    showPrintPreview(data.order_id);
                }
                
                cart = [];
                printAfterSave = false;
                orderForm.reset();
                globalDiscountInput.value = '';
                adminFeeInput.value = '';
                document.getElementById('selected-customer-info').classList.add('hidden');
                document.getElementById('customer-search').classList.remove('hidden');
                updateCartAndDisplay();

            } else { showToast(data.message || 'Gagal menyimpan pesanan.', 'error'); }
        }).catch(error => { console.error('Error:', error); showToast('Terjadi kesalahan.', 'error'); });
    }

    function openCashModal() {
        const grandTotal = parseFloat(document.getElementById('summary-grand-total').textContent.replace(/[^0-9,-]+/g, "").replace(",", ".")) || 0;
        if (grandTotal <= 0) { showToast('Tidak ada total belanja.', 'error'); return; }
        modalTotalDisplay.textContent = `Rp${grandTotal.toLocaleString('id-ID')}`;
        modalCashInput.value = ''; modalChangeDisplay.textContent = 'Rp0';
        cashModal.classList.remove('hidden');
        setTimeout(() => modalCashInput.focus(), 100);
    }
    function closeCashModal() { cashModal.classList.add('hidden'); }
    function calculateModalChange() {
        const grandTotal = parseFloat(modalTotalDisplay.textContent.replace(/[^0-9,-]+/g, "").replace(",", ".")) || 0;
        const cashAmount = parseFloat(modalCashInput.value) || 0;
        const change = cashAmount - grandTotal;
        modalChangeDisplay.textContent = `Rp${(change > 0 ? change : 0).toLocaleString('id-ID')}`;
    }
    modalCashInput.addEventListener('input', calculateModalChange);
    modalCancelBtn.addEventListener('click', closeCashModal);
    modalConfirmBtn.addEventListener('click', () => {
        const grandTotal = parseFloat(modalTotalDisplay.textContent.replace(/[^0-9,-]+/g, "").replace(",", ".")) || 0;
        const cashAmount = parseFloat(modalCashInput.value) || 0;
        if (cashAmount < grandTotal) { showToast('Uang tunai kurang dari total belanja!', 'error'); return; }
        const changeAmount = cashAmount - grandTotal;
        processOrder(cashAmount, changeAmount);
        closeCashModal();
    });

    document.getElementById('cash-shortcuts').addEventListener('click', (e) => {
        if (e.target.classList.contains('cash-shortcut-btn')) {
            const amount = e.target.dataset.amount;
            if (amount === 'pas') {
                const total = parseFloat(modalTotalDisplay.textContent.replace(/[^0-9]/g, "")) || 0;
                modalCashInput.value = total;
            } else {
                modalCashInput.value = amount;
            }
            calculateModalChange();
        }
    });

    openManualProductModalBtn.addEventListener('click', () => { manualProductModal.classList.remove('hidden'); manualProductForm.reset(); document.getElementById('manual-product-name').focus(); });
    cancelManualProductBtn.addEventListener('click', () => { manualProductModal.classList.add('hidden'); });
    manualProductForm.addEventListener('submit', (e) => {
        e.preventDefault();
        const name = document.getElementById('manual-product-name').value.trim(), price = parseFloat(document.getElementById('manual-product-price').value), quantity = parseInt(document.getElementById('manual-product-quantity').value);
        if (name === '' || isNaN(price) || price <= 0 || isNaN(quantity) || quantity <= 0) { showToast('Input produk manual tidak valid.', 'error'); return; }
        cart.push({ id: 'manual_' + Date.now(), name: name, price: price, quantity: quantity, discount: 0 });
        updateCartAndDisplay();
        manualProductModal.classList.add('hidden');
    });

    function switchTab(targetTab) {
        if (targetTab === 'produk') { tabProduk.classList.add('active'); tabKeranjang.classList.remove('active'); panelProduk.classList.remove('hidden'); panelKeranjang.classList.add('hidden'); }
        else { tabProduk.classList.remove('active'); tabKeranjang.classList.add('active'); panelProduk.classList.add('hidden'); panelKeranjang.classList.remove('hidden'); }
    }
    if (tabProduk) { tabProduk.addEventListener('click', () => switchTab('produk')); tabKeranjang.addEventListener('click', () => switchTab('keranjang')); }

    function openCustomerDisplay() { window.open('customer_display.php', 'customer_display_window', 'width=1024,height=768'); }
    const btnDesktop = document.getElementById('open-customer-display-btn-desktop'), btnMobile = document.getElementById('open-customer-display-btn-mobile');
    if (btnDesktop) btnDesktop.addEventListener('click', openCustomerDisplay); if (btnMobile) btnMobile.addEventListener('click', openCustomerDisplay);

    document.getElementById('save-order-btn').addEventListener('click', () => { printAfterSave = false; (paymentMethodInput.value === 'Tunai') ? openCashModal() : processOrder(); });
    document.getElementById('save-print-btn').addEventListener('click', () => { printAfterSave = true; (paymentMethodInput.value === 'Tunai') ? openCashModal() : processOrder(); });
    globalDiscountInput.addEventListener('input', updateCartAndDisplay); adminFeeInput.addEventListener('input', updateCartAndDisplay);

    paymentMethodButtons.addEventListener('click', (e) => {
        const clickedButton = e.target.closest('.payment-method-btn');
        if (!clickedButton) return;
        document.querySelectorAll('.payment-method-btn').forEach(btn => btn.classList.remove('active'));
        clickedButton.classList.add('active');
        const selectedValue = clickedButton.dataset.value;
        paymentMethodInput.value = selectedValue;
        togglePaymentSections();
    });

    document.querySelectorAll('.product-card').forEach(card => {
        card.addEventListener('click', () => {
            const productId = card.dataset.id, availableStock = parseInt(card.dataset.stock), existingItem = cart.find(item => item.id === productId), currentQtyInCart = existingItem ? existingItem.quantity : 0;
            if (currentQtyInCart < availableStock) { if (existingItem) { existingItem.quantity++; } else { cart.push({ id: productId, name: card.dataset.name, price: parseFloat(card.dataset.price), quantity: 1, discount: 0 }); } updateCartAndDisplay(); }
            else { showToast('Stok tidak mencukupi!', 'error'); }
        });
    });

    const searchInput = document.getElementById('search-product'), categoryFilters = document.getElementById('category-filters');
    let currentCategory = 'all', currentSearchQuery = '';
    function filterProducts() { document.querySelectorAll('.product-card').forEach(card => { const productName = card.dataset.name.toLowerCase(), productCategory = card.dataset.category.toLowerCase(), categoryMatch = (currentCategory === 'all' || productCategory === currentCategory), searchMatch = productName.includes(currentSearchQuery); card.style.display = categoryMatch && searchMatch ? 'flex' : 'none'; }); }
    searchInput.addEventListener('input', (e) => { currentSearchQuery = e.target.value.toLowerCase(); filterProducts(); });
    categoryFilters.addEventListener('click', (e) => { const clickedButton = e.target.closest('.category-filter-btn'); if (clickedButton) { const selectedCategory = clickedButton.dataset.category.toLowerCase(); currentCategory = selectedCategory; document.querySelectorAll('.category-filter-btn').forEach(btn => { btn.classList.remove('bg-blue-600', 'text-white'); btn.classList.add('bg-white', 'text-gray-700'); }); clickedButton.classList.add('bg-blue-600', 'text-white'); clickedButton.classList.remove('bg-white', 'text-gray-700'); filterProducts(); } });
    filterProducts();

    function findAndAddToCart(barcode) {
        const product = allProducts.find(p => p.barcode === barcode);
        if (product) {
            const availableStock = parseInt(product.stock), existingItem = cart.find(item => item.id === product.id), currentQtyInCart = existingItem ? existingItem.quantity : 0;
            if (currentQtyInCart < availableStock) {
                if (existingItem) { existingItem.quantity++; } else { cart.push({ id: product.id, name: product.name, price: parseFloat(product.price), quantity: 1, discount: 0 }); }
                updateCartAndDisplay();
                const card = document.querySelector(`[data-id='${product.id}']`);
                if (card) { card.classList.add('ring-4', 'ring-green-400'); setTimeout(() => { card.classList.remove('ring-4', 'ring-green-400'); }, 500); }
                showToast(`"${product.name}" ditambahkan.`, 'success');
            } else { showToast(`Stok "${product.name}" habis!`, 'error'); }
        } else { showToast(`Barcode ${barcode} tidak ditemukan.`, 'error'); }
    }

    let barcodeBuffer = [], lastKeyTime = Date.now();
    document.addEventListener('keydown', e => {
        if (document.activeElement.tagName === 'INPUT' || document.activeElement.tagName === 'TEXTAREA') return;
        const currentTime = Date.now(); if (currentTime - lastKeyTime > 100) { barcodeBuffer = []; }
        if (e.key === 'Enter') { if (barcodeBuffer.length > 5) { findAndAddToCart(barcodeBuffer.join('')); } barcodeBuffer = []; }
        else if (e.key.length === 1) { barcodeBuffer.push(e.key); } lastKeyTime = currentTime;
    });

    const html5QrCode = new Html5Qrcode("reader");
    const qrCodeSuccessCallback = (decodedText, decodedResult) => { findAndAddToCart(decodedText); stopScanner(); };
    const config = { fps: 10, qrbox: { width: 250, height: 250 } };
    function startScanner() { scannerModal.classList.remove('hidden'); html5QrCode.start({ facingMode: "environment" }, config, qrCodeSuccessCallback).catch(err => { console.error("Gagal memulai scanner.", err); showToast("Kamera tidak ditemukan.", 'error'); stopScanner(); }); }
    function stopScanner() { html5QrCode.stop().catch(err => {}); scannerModal.classList.add('hidden'); }
    startScanBtn.addEventListener('click', startScanner); closeScannerBtn.addEventListener('click', stopScanner);

    function checkDebtButton() {
        if (selectedCustomerIdInput.value) {
            debtPaymentBtn.disabled = false;
        } else {
            debtPaymentBtn.disabled = true;
            if (paymentMethodInput.value === 'Utang') {
                document.querySelector('.payment-method-btn[data-value="QRIS"]').click();
            }
        }
    }

    customerSearchInput.addEventListener('input', () => {
        const term = customerSearchInput.value;
        searchResultsContainer.innerHTML = '';
        searchResultsContainer.classList.add('hidden');
        clearTimeout(searchTimeout);
        if (term.length < 2) {
            selectedCustomerIdInput.value = '';
            checkDebtButton();
            return;
        }
        searchTimeout = setTimeout(() => {
            fetch(`cari_pelanggan.php?term=${term}`)
                .then(response => response.json())
                .then(data => {
                    searchResultsContainer.innerHTML = ''; 
                    if (data.length > 0) {
                        searchResultsContainer.classList.remove('hidden');
                        data.forEach(customer => {
                            const resultItem = document.createElement('div');
                            resultItem.className = 'p-2 hover:bg-gray-100 cursor-pointer';
                            resultItem.textContent = `${customer.name} (${customer.phone || 'No HP -'})`;
                            resultItem.dataset.id = customer.id;
                            resultItem.dataset.name = customer.name;
                            resultItem.dataset.phone = customer.phone || '';
                            searchResultsContainer.appendChild(resultItem);
                        });
                    }
                })
                .catch(error => {
                    console.error('Error fetching customers:', error);
                    showToast('Gagal mencari pelanggan.', 'error');
                });
        }, 300);
    });

    searchResultsContainer.addEventListener('click', (e) => {
        const target = e.target.closest('div');
        if (target && target.dataset.id) {
            selectedCustomerIdInput.value = target.dataset.id;
            selectedCustomerName.textContent = target.dataset.name;
            selectedCustomerInfo.classList.remove('hidden');
            customerSearchInput.value = '';
            customerSearchInput.classList.add('hidden');
            searchResultsContainer.innerHTML = '';
            searchResultsContainer.classList.add('hidden');
            checkDebtButton();
        }
    });

    clearCustomerBtn.addEventListener('click', () => {
        selectedCustomerIdInput.value = '';
        customerSearchInput.value = '';
        selectedCustomerInfo.classList.add('hidden');
        customerSearchInput.classList.remove('hidden');
        customerSearchInput.focus();
        checkDebtButton();
    });
    
    cartItemsContainer.addEventListener('click', (e) => {
        const target = e.target.closest('.quantity-change, .remove-item, .item-discount-btn');
        if (!target) return;

        const index = parseInt(target.dataset.index);
        
        if (target.classList.contains('quantity-change')) {
            const action = target.dataset.action;
            if (action === 'increase') {
                const productId = cart[index].id;
                const product = allProducts.find(p => p.id == productId);
                const availableStock = product ? parseInt(product.stock) : Infinity;
                if (cart[index].quantity < availableStock) {
                    cart[index].quantity++;
                } else {
                    showToast('Stok tidak mencukupi!', 'error');
                }
            } else if (action === 'decrease') {
                cart[index].quantity--;
                if (cart[index].quantity === 0) {
                    cart.splice(index, 1);
                }
            }
        } else if (target.classList.contains('remove-item')) {
            cart.splice(index, 1);
        } else if (target.classList.contains('item-discount-btn')) {
            const discountValue = prompt(`Diskon untuk "${cart[index].name}":`, cart[index].discount || 0);
            if (discountValue !== null) {
                const discountAmount = parseFloat(discountValue);
                const itemSubtotal = cart[index].price * cart[index].quantity;
                if (!isNaN(discountAmount) && discountAmount >= 0 && discountAmount <= itemSubtotal) {
                    cart[index].discount = discountAmount;
                } else {
                    showToast('Jumlah diskon tidak valid.', 'error');
                }
            }
        }
        updateCartAndDisplay();
    });

    holdOrderBtn.addEventListener('click', () => {
        if (cart.length === 0) { showToast('Keranjang kosong, tidak ada yang bisa ditahan.', 'error'); return; }
        const holdName = prompt("Beri nama untuk transaksi ini:", `Pelanggan ${new Date().toLocaleTimeString()}`);
        if (holdName) {
            fetch('aksi_tahan_transaksi.php?action=hold', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ name: holdName, cart: cart }) })
            .then(res => res.json()).then(data => {
                if (data.success) { showToast(data.message, 'success'); resetCartAndForm(); } 
                else { showToast(data.message, 'error'); }
            });
        }
    });
    openHeldOrdersBtn.addEventListener('click', () => {
        fetch('aksi_tahan_transaksi.php?action=list').then(res => res.json()).then(data => {
            if (data.success && data.data.length > 0) {
                heldOrdersListContainer.innerHTML = '';
                data.data.forEach(order => {
                    const orderDiv = document.createElement('div');
                    orderDiv.className = 'flex justify-between items-center bg-gray-100 p-3 rounded-md';
                    orderDiv.innerHTML = `<div><p class="font-semibold">${order.hold_name}</p><p class="text-xs text-gray-500">${new Date(order.created_at).toLocaleString('id-ID')}</p></div><button class="resume-order-btn bg-green-500 text-white py-1 px-3 rounded-md text-sm hover:bg-green-600" data-id="${order.id}">Lanjutkan</button>`;
                    heldOrdersListContainer.appendChild(orderDiv);
                });
                heldOrdersModal.classList.remove('hidden');
            } else { showToast('Tidak ada transaksi yang sedang ditahan.', 'success'); }
        });
    });
    
    heldOrdersListContainer.addEventListener('click', (e) => {
        if (e.target.classList.contains('resume-order-btn')) {
            if (cart.length > 0 && !confirm("Keranjang saat ini berisi item. Apakah Anda yakin ingin menggantinya?")) { return; }
            const orderId = e.target.dataset.id;
            fetch('aksi_tahan_transaksi.php?action=resume', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id: orderId }) })
            .then(res => res.json()).then(data => {
                if (data.success) {
                    cart = data.cart_data; updateCartAndDisplay(); heldOrdersModal.classList.add('hidden');
                    showToast('Transaksi berhasil dilanjutkan.', 'success');
                } else { showToast(data.message, 'error'); }
            });
        }
    });
    closeHeldOrdersModalBtn.addEventListener('click', () => { heldOrdersModal.classList.add('hidden'); });
    
    updateCartAndDisplay();
    checkDebtButton();
});
