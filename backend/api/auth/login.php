<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';

setHeaders();
requireMethod('POST');

$in = getJsonInput();
$email = trim($in['email'] ?? '');
$pass  = $in['password'] ?? '';

$db = (new Database())->connect();
$stmt = $db->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
$stmt->execute([':email' => $email]);
$user = $stmt->fetch();

if (!$user || !password_verify($pass, $user['password'])) {
    jsonResponse(false, 'Email atau password salah', null, 401);
}

$token = bin2hex(random_bytes(32));
$upd = $db->prepare('UPDATE users SET api_token = :t WHERE id = :id');
$upd->execute([':t' => $token, ':id' => $user['id']]);

jsonResponse(true, 'Login berhasil', [
    'token' => $token,
    'user'  => ['id' => (int)$user['id'], 'name' => $user['name'], 'email' => $user['email']],
]);