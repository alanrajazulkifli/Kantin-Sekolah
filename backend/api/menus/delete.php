<?php
require_once '../../config/database.php';
require_once '../../helpers/response.php';

$database = new Database();
$db = $database->getConnection();

$data = json_decode(file_get_contents("php://input"));
$id = isset($_GET['id']) ? $_GET['id'] : (isset($data->id) ? $data->id : null);

if (!empty($id)) {
    try {
        $query = "DELETE FROM menus WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id);
        
        if ($stmt->execute()) {
            sendResponse(true, "Menu berhasil dihapus", null, 200);
        } else {
            sendResponse(false, "Gagal menghapus menu", null, 400);
        }
    } catch (Exception $e) {
        sendResponse(false, "Error: " . $e->getMessage(), null, 500);
    }
} else {
    sendResponse(false, "ID menu tidak ditemukan", null, 400);
}