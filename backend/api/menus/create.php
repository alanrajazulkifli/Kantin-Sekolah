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

require_once '../../helpers/response.php';
require_once '../../config/database.php';
require_once '../../helpers/auth.php';

setHeaders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST, OPTIONS');
    jsonResponse(false, "Endpoint tambah menu hanya menerima metode POST.", null, 405);
}

$database = new Database();
$db = $database->getConnection();
$user = requireAuth($db);
requireRole($user, ['admin']);

$inputJSON = file_get_contents("php://input");
$data = json_decode($inputJSON, true);

if (trim($inputJSON) !== '' && json_last_error() !== JSON_ERROR_NONE) {
    jsonResponse(false, "Format JSON tidak valid.", null, 400);
}

$data = is_array($data) ? $data : [];
$category_id = $data['category_id'] ?? ($_POST['category_id'] ?? null);
$nama        = $data['nama'] ?? ($_POST['nama'] ?? null);
$harga       = $data['harga'] ?? ($_POST['harga'] ?? null);
$stok        = $data['stok'] ?? ($_POST['stok'] ?? 0);
$deskripsi   = $data['deskripsi'] ?? ($_POST['deskripsi'] ?? '');
$gambar_url  = $data['gambar_url'] ?? ($_POST['gambar_url'] ?? '');

$nama = is_string($nama) ? trim($nama) : '';
$categoryId = filter_var($category_id, FILTER_VALIDATE_INT);
$stock = filter_var($stok, FILTER_VALIDATE_INT);

if (
    $categoryId === false || $categoryId < 1 ||
    $nama === '' ||
    !is_numeric($harga) || $harga <= 0 ||
    $stock === false || $stock < 0
) {
    jsonResponse(
        false,
        "Data tidak valid. category_id, nama, dan harga harus diisi; stok harus berupa angka nol atau lebih.",
        null,
        400
    );
}

try {
    $query = "INSERT INTO menus (category_id, nama, harga, stok, deskripsi, gambar_url)
              VALUES (:category_id, :nama, :harga, :stok, :deskripsi, :gambar_url)";
    $stmt = $db->prepare($query);
    $stmt->execute([
        ':category_id' => $categoryId,
        ':nama' => $nama,
        ':harga' => $harga,
        ':stok' => $stock,
        ':deskripsi' => is_scalar($deskripsi) ? (string) $deskripsi : '',
        ':gambar_url' => is_scalar($gambar_url) ? (string) $gambar_url : '',
    ]);

    jsonResponse(true, "Menu berhasil ditambahkan", null, 201);
} catch (Exception $e) {
    jsonResponse(false, "Gagal menambahkan menu: " . $e->getMessage(), null, 500);
}