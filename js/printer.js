document.addEventListener('DOMContentLoaded', () => {
    const searchBtn = document.getElementById('search-printers-btn');
    const forgetBtn = document.getElementById('forget-printer-btn');
    const printerListDiv = document.getElementById('printer-list');
    const savedPrinterNameEl = document.getElementById('saved-printer-name');

    // Cek apakah browser mendukung Web Bluetooth
    if (!navigator.bluetooth) {
        searchBtn.textContent = 'Browser Tidak Mendukung';
        searchBtn.disabled = true;
        savedPrinterNameEl.textContent = 'Fitur ini tidak didukung di browser Anda.';
    }

    // Fungsi untuk menampilkan printer yang tersimpan
    function displaySavedPrinter() {
        const savedPrinter = localStorage.getItem('preferred_printer');
        if (savedPrinter) {
            const printer = JSON.parse(savedPrinter);
            savedPrinterNameEl.textContent = printer.name;
            forgetBtn.classList.remove('hidden');
        } else {
            savedPrinterNameEl.textContent = 'Belum ada printer yang disimpan.';
            forgetBtn.classList.add('hidden');
        }
    }

    // Event listener untuk tombol "Cari Printer"
    searchBtn.addEventListener('click', async () => {
        printerListDiv.innerHTML = '<p class="text-gray-500">Mencari perangkat...</p>';
        try {
            // Minta izin ke pengguna untuk akses Bluetooth
            const device = await navigator.bluetooth.requestDevice({
                filters: [{ services: ['000018f0-0000-1000-8000-00805f9b34fb'] }], // Service UUID untuk Serial Port Profile (umum untuk printer)
                acceptAllDevices: false,
            });
            
            printerListDiv.innerHTML = ''; // Kosongkan daftar

            const deviceDiv = document.createElement('div');
            deviceDiv.className = 'bg-blue-50 border border-blue-200 p-3 rounded-md flex justify-between items-center';
            deviceDiv.innerHTML = `
                <span class="font-semibold text-blue-800">${device.name || `ID: ${device.id}`}</span>
                <button class="save-printer-btn bg-green-500 text-white text-sm py-1 px-3 rounded-md hover:bg-green-600">Simpan</button>
            `;
            
            // Simpan data perangkat ke tombol untuk digunakan nanti
            deviceDiv.querySelector('.save-printer-btn').onclick = () => {
                const printerInfo = { id: device.id, name: device.name };
                localStorage.setItem('preferred_printer', JSON.stringify(printerInfo));
                alert(`Printer "${device.name}" berhasil disimpan!`);
                displaySavedPrinter();
                printerListDiv.innerHTML = '';
            };

            printerListDiv.appendChild(deviceDiv);

        } catch (error) {
            console.error('Error:', error);
            printerListDiv.innerHTML = `<p class="text-red-500">Gagal mencari perangkat. Pastikan Bluetooth aktif atau coba lagi. Error: ${error.message}</p>`;
        }
    });

    // Event listener untuk tombol "Lupakan Printer"
    forgetBtn.addEventListener('click', () => {
        if (confirm('Anda yakin ingin menghapus printer yang tersimpan?')) {
            localStorage.removeItem('preferred_printer');
            alert('Printer berhasil dilupakan.');
            displaySavedPrinter();
        }
    });
    
    // Tampilkan printer yang sudah tersimpan saat halaman dimuat
    displaySavedPrinter();
});