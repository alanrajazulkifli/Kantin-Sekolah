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
require_once '../../helpers/menu_image.php';

setHeaders();
requireMethod('POST');

$database = new Database();
$db = $database->getConnection();
$user = requireAuth($db);
requireRole($user, ['admin']);

$data = json_decode(file_get_contents("php://input"), true);
if (!is_array($data)) {
    $data = $_POST;
}

if (!empty($data['id']) && !empty($data['category_id']) && !empty($data['nama']) && !empty($data['harga'])) {
    try {
        $uploadedImageUrl = saveMenuImage($_FILES['gambar_file'] ?? null);
        if ($uploadedImageUrl !== null) {
            $data['gambar_url'] = $uploadedImageUrl;
        }

        $fields = [
            'category_id = :category_id',
            'nama = :nama',
            'harga = :harga',
            'stok = :stok',
            'deskripsi = :deskripsi',
        ];
        if (array_key_exists('gambar_url', $data)) {
            $fields[] = 'gambar_url = :gambar_url';
        }
        $query = 'UPDATE menus SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = $db->prepare($query);
        $parameters = [
            ':id' => $data['id'],
            ':category_id' => $data['category_id'],
            ':nama' => $data['nama'],
            ':harga' => $data['harga'],
            ':stok' => $data['stok'] ?? 0,
            ':deskripsi' => $data['deskripsi'] ?? '',
        ];
        if (array_key_exists('gambar_url', $data)) {
            $parameters[':gambar_url'] = $data['gambar_url'];
        }

        if ($stmt->execute($parameters)) {
            sendResponse(true, "Menu berhasil diperbarui", null, 200);
        } else {
            sendResponse(false, "Gagal memperbarui menu", null, 400);
        }
    } catch (InvalidArgumentException $e) {
        sendResponse(false, $e->getMessage(), null, 400);
    } catch (Exception $e) {
        sendResponse(false, "Error: " . $e->getMessage(), null, 500);
    }
} else {
    sendResponse(false, "Data input tidak lengkap", null, 400);
}