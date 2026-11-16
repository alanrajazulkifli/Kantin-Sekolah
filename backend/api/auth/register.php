<?php
// Wajib ada untuk mengatasi error Failed to fetch di Flutter Web
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

// Tangani preflight request OPTIONS dari browser
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once "../../config/database.php";

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['name']) || !isset($data['email']) || !isset($data['password'])) {
    echo json_encode([
        "success" => false,
        "message" => "Data tidak lengkap (name, email, password wajib diisi)",
        "data" => null
    ]);
    exit();
}

$name = $data['name'];
$email = $data['email'];
$password = password_hash($data['password'], PASSWORD_DEFAULT);

try {
    $database = new Database();
    $pdo = $database->getConnection();

    $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $check->execute([$email]);
    if ($check->rowCount() > 0) {
        echo json_encode([
            "success" => false,
            "message" => "Email sudah terdaftar!",
            "data" => null
        ]);
        exit();
    }

    $stmt = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
    $stmt->execute([$name, $email, $password]);

    echo json_encode([
        "success" => true,
        "message" => "Registrasi berhasil, silakan login.",
        "data" => null
    ]);
} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "message" => "Kesalahan server: " . $e->getMessage(),
        "data" => null
    ]);
}
?>