<?php
require_once '../../config/database.php';
require_once '../../helpers/response.php';
require_once '../../helpers/auth.php';

setHeaders();
requireMethod('POST');

$database = new Database();
$db = $database->getConnection();
$user = requireAuth($db);
requireRole($user, ['admin']);

$input = getJsonInput();
$orderId = (int) ($input['order_id'] ?? 0);

if ($orderId < 1) {
    jsonResponse(false, 'ID pesanan tidak valid.', null, 400);
}

try {
    $statement = $db->prepare(
        "DELETE FROM orders WHERE id = :order_id AND status = 'selesai'"
    );
    $statement->execute([':order_id' => $orderId]);

    if ($statement->rowCount() === 0) {
        jsonResponse(
            false,
            'Pesanan tidak ditemukan atau belum berstatus selesai.',
            null,
            404
        );
    }

    jsonResponse(true, 'Pesanan selesai berhasil dihapus.', [
        'id' => $orderId,
    ]);
} catch (PDOException $e) {
    error_log('Gagal menghapus pesanan: ' . $e->getMessage());
    jsonResponse(false, 'Pesanan gagal dihapus.', null, 500);
}
