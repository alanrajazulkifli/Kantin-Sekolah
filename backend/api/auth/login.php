<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Karena file ada di backend/api/auth/, naik 2 level ke backend/config/database.php
require_once "../../config/database.php"; 

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['email']) || !isset($data['password'])) {
    echo json_encode([
        "success" => false,
        "message" => "Email dan password wajib diisi",
        "data" => null
    ]);
    exit();
}

$email = $data['email'];
$password = $data['password'];

try {
    $database = new Database();
    $pdo = $database->getConnection();

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        // Tanpa token, langsung kembalikan data user ke aplikasi Flutter
        echo json_encode([
            "success" => true,
            "message" => "Login berhasil",
            "data" => [
                "id" => $user['id'],
                "name" => $user['name'],
                "email" => $user['email']
            ]
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Email atau password salah!",
            "data" => null
        ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        "success" => false,
        "message" => "Terjadi kesalahan server: " . $e->getMessage(),
        "data" => null
    ]);
}
?>