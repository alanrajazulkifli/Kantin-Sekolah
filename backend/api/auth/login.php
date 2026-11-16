<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../../config/database.php';

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

if (!is_array($data) || empty($data)) {
    $data = $_POST;
}

if (!is_array($data)) {
    parse_str($rawInput, $data);
}

if ((!isset($data['email']) || trim((string) $data['email']) === '') || (!isset($data['password']) || (string) $data['password'] === '')) {
    echo json_encode([
        'success' => false,
        'message' => 'Email dan kata sandi wajib diisi.',
        'data' => null,
    ]);
    exit();
}

$email = trim((string) $data['email']);
$password = (string) $data['password'];

try {
    $database = new Database();
    $pdo = $database->getConnection();

    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $token = bin2hex(random_bytes(32));

        echo json_encode([
            'success' => true,
            'message' => 'Login berhasil',
            'data' => [
                'id' => (int) $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'token' => $token,
            ],
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Email atau kata sandi salah.',
            'data' => null,
        ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan server: ' . $e->getMessage(),
        'data' => null,
    ]);
}
?>