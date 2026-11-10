<?php
require_once '../../config/database.php';
require_once '../../helpers/response.php';

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->id) && !empty($data->category_id) && !empty($data->nama) && !empty($data->harga)) {
    try {
        $query = "UPDATE menus SET category_id = :category_id, nama = :nama, harga = :harga, 
                  stok = :stok, deskripsi = :deskripsi WHERE id = :id";
        $stmt = $db->prepare($query);
        
        $stmt->bindParam(':id', $data->id);
        $stmt->bindParam(':category_id', $data->category_id);
        $stmt->bindParam(':nama', $data->nama);
        $stmt->bindParam(':harga', $data->harga);
        $stmt->bindParam(':stok', $data->stok);
        $stmt->bindParam(':deskripsi', $data->deskripsi);
        
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