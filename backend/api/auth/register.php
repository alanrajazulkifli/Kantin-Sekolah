
<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Metode tidak diizinkan.',
        'data' => null
    ]);
    exit();
}

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

if (!is_array($data) || empty($data)) {
    $data = $_POST;
}

$name = trim($data['name'] ?? '');
$email = trim($data['email'] ?? '');
$password = (string) ($data['password'] ?? '');

if ($name === '' || $email === '' || $password === '') {
    echo json_encode([
        'success' => false,
        'message' => 'Nama, email, dan password wajib diisi.',
        'data' => null
    ]);
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'success' => false,
        'message' => 'Format email tidak valid.',
        'data' => null
    ]);
    exit();
}

if (strlen($password) < 8) {
    echo json_encode([
        'success' => false,
        'message' => 'Password minimal 8 karakter.',
        'data' => null
    ]);
    exit();
}

try {

    $database = new Database();
    $pdo = $database->getConnection();

    $check = $pdo->prepare(
        'SELECT id FROM users WHERE email = ? LIMIT 1'
    );

    $check->execute([$email]);

    if ($check->fetch()) {
        echo json_encode([
            'success' => false,
            'message' => 'Email sudah terdaftar.',
            'data' => null
        ]);
        exit();
    }

    $hashedPassword = password_hash(
        $password,
        PASSWORD_DEFAULT
    );

    // Registrasi hanya diperbolehkan untuk siswa.
    $stmt = $pdo->prepare(
        'INSERT INTO users (name, email, password, role)
         VALUES (?, ?, ?, ?)'
    );

    $stmt->execute([
        $name,
        $email,
        $hashedPassword,
        'siswa'
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Registrasi berhasil. Silakan login.',
        'data' => null
    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan pada server.',
        'data' => null
    ]);
}

?>
