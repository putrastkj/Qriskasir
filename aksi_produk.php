<?php
define('_KASIR_', true);
require_once 'auth_check.php';
require_once 'config/database.php';

// Fungsi baru yang lebih andal untuk mengunduh gambar dari URL menggunakan cURL
function downloadImageFromUrl($url, $target_dir) {
    // Pastikan direktori target ada dan bisa ditulis
    if (!is_dir($target_dir)) {
        if (!mkdir($target_dir, 0777, true)) {
            return ['success' => false, 'message' => "Gagal membuat direktori '{$target_dir}'. Periksa perizinan folder."];
        }
    }
    if (!is_writable($target_dir)) {
        return ['success' => false, 'message' => "Direktori '{$target_dir}' tidak dapat ditulis. Periksa perizinan folder."];
    }

    $file_name = uniqid() . '.jpg';
    $target_file = $target_dir . $file_name;

    $ch = curl_init($url);
    $fp = fopen($target_file, 'wb');
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Ikuti redirect jika ada
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Nonaktifkan verifikasi SSL (berguna untuk server lokal)
    $result = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    fclose($fp);

    if ($result && $http_code == 200) {
        return ['success' => true, 'path' => $target_file];
    } else {
        // Hapus file kosong jika unduhan gagal
        if (file_exists($target_file)) {
            unlink($target_file);
        }
        return ['success' => false, 'message' => "Gagal mengunduh gambar dari URL (HTTP Code: {$http_code})."];
    }
}

function uploadImage($file) {
    $target_dir = "uploads/";
    if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
    $target_file = $target_dir . uniqid() . '_' . basename($file["name"]);
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array($imageFileType, $allowed_types)) return ['success' => false, 'message' => 'Hanya file JPG, JPEG, PNG, WEBP & GIF yang diizinkan.'];
    if (move_uploaded_file($file["tmp_name"], $target_file)) return ['success' => true, 'path' => $target_file];
    return ['success' => false, 'message' => 'Terjadi kesalahan saat mengunggah file.'];
}

if (isset($_POST['submit'])) {
    $id = (int)$_POST['id'];
    $name = $conn->real_escape_string($_POST['name']);
    $price = (float)$_POST['price'];
    $cost_price = (float)$_POST['cost_price'];
    $barcode = $conn->real_escape_string($_POST['barcode']);
    $category = $conn->real_escape_string($_POST['category']);
    $stock = (int)$_POST['stock'];
    $image_path = '';
    $image_url = $_POST['image_url'] ?? '';

    // Prioritaskan file yang diunggah manual
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $upload = uploadImage($_FILES['image']);
        if ($upload['success']) {
            $image_path = $upload['path'];
        } else {
            die($upload['message']);
        }
    } 
    // Jika tidak ada upload manual, cek apakah ada URL dari pencarian
    elseif (!empty($image_url)) {
        $download = downloadImageFromUrl($image_url, "uploads/");
        if ($download['success']) {
            $image_path = $download['path'];
        } else {
            die($download['message']);
        }
    }

    if ($id > 0) {
        if (empty($image_path)) {
            $sql = "UPDATE products SET name = ?, price = ?, cost_price = ?, barcode = ?, category = ?, stock = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sddssii", $name, $price, $cost_price, $barcode, $category, $stock, $id);
        } else {
            $sql = "UPDATE products SET name = ?, price = ?, cost_price = ?, barcode = ?, category = ?, stock = ?, image = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sddssisi", $name, $price, $cost_price, $barcode, $category, $stock, $image_path, $id);
        }
    } else {
        if(empty($image_path)) $image_path = 'https://placehold.co/150';
        $sql = "INSERT INTO products (name, price, cost_price, barcode, category, stock, image) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sddssis", $name, $price, $cost_price, $barcode, $category, $stock, $image_path);
    }
    
    if ($stmt->execute()) {
        header("Location: admin_produk.php");
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }
    $stmt->close();
}

if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt_select = $conn->prepare("SELECT image FROM products WHERE id = ?");
    $stmt_select->bind_param("i", $id); $stmt_select->execute(); $result = $stmt_select->get_result();
    if ($row = $result->fetch_assoc()) if (!empty($row['image']) && file_exists($row['image']) && strpos($row['image'], 'placeholder.co') === false) unlink($row['image']);
    $stmt_select->close();

    $sql = "DELETE FROM products WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        header("Location: admin_produk.php");
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }
    $stmt->close();
}
$conn->close();
?>
