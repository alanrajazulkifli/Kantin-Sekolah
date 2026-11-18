
<?php

/**
 * Mengambil token Bearer dari header Authorization.
 */
function getBearerToken(): ?string
{
    $headers = function_exists('getallheaders')
        ? getallheaders()
        : [];

    $auth = $headers['Authorization']
        ?? $headers['authorization']
        ?? $_SERVER['HTTP_AUTHORIZATION']
        ?? '';

    if (preg_match('/Bearer\s+(\S+)/i', $auth, $matches)) {
        return $matches[1];
    }

    return null;
}

/**
 * Memeriksa token pengguna dari database.
 */
function requireAuth(PDO $db): array
{
    $token = getBearerToken();

    if (!$token) {
        jsonResponse(
            false,
            'Token tidak ditemukan.',
            null,
            401
        );
    }

    $stmt = $db->prepare(
        'SELECT id, name, email, role
         FROM users
         WHERE api_token = :token
         LIMIT 1'
    );

    $stmt->execute([
        ':token' => $token
    ]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        jsonResponse(
            false,
            'Token tidak valid.',
            null,
            401
        );
    }

    return $user;
}

/**
 * Membatasi akses berdasarkan role pengguna.
 */
function requireRole(array $user, array $roles): void
{
    if (!in_array($user['role'], $roles, true)) {
        jsonResponse(
            false,
            'Kamu tidak memiliki akses ke fitur ini.',
            null,
            403
        );
    }
}

?>
