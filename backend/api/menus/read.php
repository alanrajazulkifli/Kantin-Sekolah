<?php
require_once '../../config/database.php';
require_once '../../helpers/response.php';
require_once '../../helpers/auth.php';

setHeaders();
requireMethod('GET');
$database = new Database();
$db = $database->getConnection();
$user = requireAuth($db);
requireMethod('GET');

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$conditions = ['m.nama LIKE :search'];
$parameters = [':search' => "%{$search}%"];

if (isset($_GET['category_id']) && $_GET['category_id'] !== '') {
    $categoryId = filter_var($_GET['category_id'], FILTER_VALIDATE_INT);
    if ($categoryId === false || $categoryId < 1) {
        sendResponse(false, "category_id tidak valid.", null, 400);
    }

    $conditions[] = 'm.category_id = :category_id';
    $parameters[':category_id'] = $categoryId;
}

try {
    $query = "SELECT m.*, c.nama_kategori FROM menus m
              JOIN categories c ON m.category_id = c.id
              WHERE " . implode(' AND ', $conditions) . " ORDER BY m.id DESC";
    $stmt = $db->prepare($query);
    $stmt->execute($parameters);

    $menus = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($menus as &$menu) {
        $imageUrl = trim((string) ($menu['gambar_url'] ?? ''));
        if ($imageUrl !== '') {
            $menu['gambar_url'] =
                '/Kantin-Sekolah/backend/api/menus/image_proxy.php?url=' .
                rawurlencode($imageUrl);
        }
    }
    unset($menu);
    sendResponse(true, "Data menu berhasil dimuat", $menus, 200);
} catch (Exception $e) {
    sendResponse(false, "Gagal mengambil data menu: " . $e->getMessage(), null, 500);
}