<?php
require_once '../../config/database.php';
require_once '../../helpers/response.php';

$database = new Database();
$db = $database->getConnection();

try {
    $query = "SELECT * FROM categories ORDER BY id ASC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    sendResponse(true, "Data kategori berhasil dimuat", $categories, 200);
} catch (Exception $e) {
    sendResponse(false, "Gagal mengambil kategori: " . $e->getMessage(), null, 500);
}