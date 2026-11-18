<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit();
}

require_once '../../config/database.php';
require_once '../../helpers/response.php';
require_once '../../helpers/auth.php';

setHeaders();
requireMethod('POST');

$database = new Database();
$db = $database->getConnection();
$user = requireAuth($db);
requireRole($user, ['admin']);

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->id) && !empty($data->category_id) && !empty($data->nama) && !empty($data->harga)) {
    try {
        $query = "UPDATE menus SET category_id = :category_id, nama = :nama, harga = :harga, 
                  stok = :stok, deskripsi = :deskripsi, gambar_url = :gambar_url WHERE id = :id";
        $stmt = $db->prepare($query);
        
        $gambar_url = isset($data->gambar_url) ? $data->gambar_url : '';
        $stmt->bindParam(':id', $data->id);
        $stmt->bindParam(':category_id', $data->category_id);
        $stmt->bindParam(':nama', $data->nama);
        $stmt->bindParam(':harga', $data->harga);
        $stmt->bindParam(':stok', $data->stok);
        $stmt->bindParam(':deskripsi', $data->deskripsi);
        $stmt->bindParam(':gambar_url', $gambar_url);
        
        if ($stmt->execute()) {
            sendResponse(true, "Menu berhasil diperbarui", null, 200);
        } else {
            sendResponse(false, "Gagal memperbarui menu", null, 400);
        }
    } catch (Exception $e) {
        sendResponse(false, "Error: " . $e->getMessage(), null, 500);
    }
} else {
    sendResponse(false, "Data input tidak lengkap", null, 400);
}