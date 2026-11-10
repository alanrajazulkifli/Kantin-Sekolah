<?php
require_once '../../config/database.php';
require_once '../../helpers/response.php';

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->category_id) && !empty($data->nama) && !empty($data->harga)) {
    try {
        $query = "INSERT INTO menus (category_id, nama, harga, stok, deskripsi, gambar_url) 
                  VALUES (:category_id, :nama, :harga, :stok, :deskripsi, :gambar_url)";
        $stmt = $db->prepare($query);
        
        $stmt->bindParam(':category_id', $data->category_id);
        $stmt->bindParam(':nama', $data->nama);
        $stmt->bindParam(':harga', $data->harga);
        $stok = isset($data->stok) ? $data->stok : 0;
        $stmt->bindParam(':stok', $stok);
        $deskripsi = isset($data->deskripsi) ? $data->deskripsi : '';
        $stmt->bindParam(':deskripsi', $deskripsi);
        $gambar_url = isset($data->gambar_url) ? $data->gambar_url : '';
        $stmt->bindParam(':gambar_url', $gambar_url);
        
        if ($stmt->execute()) {
            sendResponse(true, "Menu berhasil ditambahkan", null, 201);
        } else {
            sendResponse(false, "Gagal menambahkan menu", null, 400);
        }
    } catch (Exception $e) {
        sendResponse(false, "Error: " . $e->getMessage(), null, 500);
    }
} else {
    sendResponse(false, "Data input tidak lengkap (category_id, nama, harga wajib diisi)", null, 400);
}