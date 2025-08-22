<!DOCTYPE html>
<html>
<head>
    <title>Scan Barcode & Ambil Teks</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            background-color: #f0f0f0;
            margin: 0;
            padding: 20px;
        }
        h1 {
            color: #333;
        }
        #interactive.viewport {
            width: 100%;
            height: auto;
            max-width: 640px;
            margin: 20px auto;
            position: relative;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
        }
        #interactive.viewport video {
            width: 100%;
            height: auto;
        }
        p {
            color: #555;
        }
        #result-status {
            font-weight: bold;
            color: #007BFF;
            margin-top: 10px;
        }
        #extracted-text {
            border: 1px solid #ccc;
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
            background-color: white;
            min-height: 100px;
            text-align: left;
        }
    </style>
</head>
<body>

    <h1>Scan Barcode & Ambil Teks</h1>

    <p>Arahkan kamera ke barcode produk (EAN-13).</p>

    <div id="interactive" class="viewport"></div>

    <p>Status: <span id="result-status">Siap untuk scan</span></p>

    <div id="extracted-text">
        Teks yang diekstrak akan muncul di sini.
    </div>

    <script src="https://cdn.jsdelivr.net/npm/@ericblade/quagga2/dist/quagga.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            var lastResult = null;
            var resultStatus = document.getElementById('result-status');
            var extractedTextDiv = document.getElementById('extracted-text');

            Quagga.init({
                inputStream: {
                    name: "Live",
                    type: "LiveStream",
                    target: document.querySelector('#interactive'),
                    constraints: {
                        width: 640,
                        height: 480,
                        facingMode: "environment"
                    }
                },
                decoder: {
                    readers: ["ean_reader"]
                }
            }, function(err) {
                if (err) {
                    console.error("Error saat inisialisasi Quagga:", err);
                    resultStatus.textContent = "Gagal mengakses kamera: " + err.message;
                    return;
                }
                console.log("Inisialisasi Quagga berhasil. Memulai scanning.");
                Quagga.start();
            });

            Quagga.onDetected(function(data) {
                if (data.codeResult && data.codeResult.code !== lastResult) {
                    lastResult = data.codeResult.code;
                    resultStatus.textContent = "Barcode berhasil dibaca: " + lastResult + ". Mengambil data...";

                    // Hentikan scanning setelah barcode ditemukan
                    Quagga.stop();

                    // GANTI: Panggil server perantara untuk mengambil teks
                    var urlToFetch = 'https://bkdiho.ap.loclx.io/harga//?cari=' + lastResult;
                    // Catatan: Baris di bawah ini adalah PENTING. Anda harus mengganti URL ini dengan URL server perantara Anda
                    var proxyUrl = 'https://URL-SERVER-ANDA/proxy?url=' + encodeURIComponent(urlToFetch);

                    fetch(proxyUrl)
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Network response was not ok');
                            }
                            return response.text();
                        })
                        .then(htmlText => {
                            // TAMPILKAN teks yang berhasil diambil
                            extractedTextDiv.textContent = "Teks dari halaman: " + htmlText;
                            resultStatus.textContent = "Data berhasil diambil!";
                        })
                        .catch(error => {
                            console.error('Ada masalah dengan operasi fetch:', error);
                            extractedTextDiv.textContent = "Gagal mengambil data: " + error.message;
                            resultStatus.textContent = "Gagal mengambil data.";
                        });
                }
            });

            window.addEventListener('error', function(e) {
                if (e.message.includes("Permission denied")) {
                    resultStatus.textContent = "Izin akses kamera ditolak. Silakan berikan izin.";
                }
            });
        });
    </script>

</body>
</html>