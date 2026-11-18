
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

$email = trim($data['email'] ?? '');
$password = (string) ($data['password'] ?? '');

if ($email === '' || $password === '') {
    echo json_encode([
        'success' => false,
        'message' => 'Email dan password wajib diisi.',
        'data' => null
    ]);
    exit();
}

try {

    $database = new Database();
    $pdo = $database->getConnection();

    $stmt = $pdo->prepare(
        'SELECT id, name, email, password, role
         FROM users
         WHERE email = ?
         LIMIT 1'
    );

    $stmt->execute([$email]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($password, $user['password'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Email atau password salah.',
            'data' => null
        ]);
        exit();
    }

    $token = bin2hex(random_bytes(32));
    $updateToken = $pdo->prepare(
        'UPDATE users SET api_token = :token WHERE id = :id'
    );
    $updateToken->execute([
        ':token' => $token,
        ':id' => $user['id'],
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Login berhasil.',
        'data' => [
            'id' => (int) $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
            'token' => $token
        ]
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
