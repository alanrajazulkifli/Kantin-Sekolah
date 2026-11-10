<?php
require_once '../../config/database.php';
require_once '../../helpers/response.php';

$database = new Database();
$db = $database->getConnection();

$search = isset($_GET['search']) ? trim($_GET['search']) : '';

try {
    $query = "SELECT m.*, c.nama_kategori FROM menus m 
              JOIN categories c ON m.category_id = c.id 
              WHERE m.nama LIKE :search ORDER BY m.id DESC";
    $stmt = $db->prepare($query);
    $searchTerm = "%{$search}%";
    $stmt->bindParam(':search', $searchTerm);
    $stmt->execute();
    
    $menus = $stmt->fetchAll(PDO::FETCH_ASSOC);
    sendResponse(true, "Data menu berhasil dimuat", $menus, 200);
} catch (Exception $e) {
    sendResponse(false, "Gagal mengambil data menu: " . $e->getMessage(), null, 500);
}