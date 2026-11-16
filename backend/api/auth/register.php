<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

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