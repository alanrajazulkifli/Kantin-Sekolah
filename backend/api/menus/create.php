<?php
// Izinkan akses CORS agar tidak diblokir browser
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../../config/database.php';
require_once '../../helpers/response.php';

$database = new Database();
$db = $database->getConnection();

// Tangkap input JSON dari Flutter
$inputJSON = file_get_contents("php://input");
$data = json_decode($inputJSON);

// Jika kosong, coba ambil dari $_POST biasa sebagai cadangan
$category_id = $data->category_id ?? ($_POST['category_id'] ?? null);
$nama        = $data->nama ?? ($_POST['nama'] ?? null);
$harga       = $data->harga ?? ($_POST['harga'] ?? null);
$stok        = $data->stok ?? ($_POST['stok'] ?? 0);
$deskripsi   = $data->deskripsi ?? ($_POST['deskripsi'] ?? '');
$gambar_url  = $data->gambar_url ?? ($_POST['gambar_url'] ?? '');

if (!empty($category_id) && !empty($nama) && !empty($harga)) {
    try {
        $query = "INSERT INTO menus (category_id, nama, harga, stok, deskripsi, gambar_url) 
                  VALUES (:category_id, :nama, :harga, :stok, :deskripsi, :gambar_url)";
        $stmt = $db->prepare($query);
        
        $stmt->bindParam(':category_id', $category_id);
        $stmt->bindParam(':nama', $nama);
        $stmt->bindParam(':harga', $harga);
        $stmt->bindParam(':stok', $stok);
        $stmt->bindParam(':deskripsi', $deskripsi);
        $stmt->bindParam(':gambar_url', $gambar_url);
        
        if ($stmt->execute()) {
            sendResponse(true, "Menu berhasil ditambahkan", null, 201);
        } else {
            sendResponse(false, "Gagal menambahkan menu ke database", null, 400);
        }
    } catch (Exception $e) {
        sendResponse(false, "Error: " . $e->getMessage(), null, 500);
    }
} else {
    sendResponse(false, "Data input tidak lengkap (category_id, nama, dan harga wajib diisi)", null, 400);
}