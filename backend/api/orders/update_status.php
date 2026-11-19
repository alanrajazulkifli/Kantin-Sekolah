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
$orderId = filter_var($input['order_id'] ?? null, FILTER_VALIDATE_INT);
$status = (string) ($input['status'] ?? '');
$allowedStatuses = ['baru', 'diproses', 'selesai', 'dibatalkan'];

if ($orderId === false || $orderId < 1 || !in_array($status, $allowedStatuses, true)) {
    jsonResponse(false, 'ID pesanan atau status tidak valid.', null, 400);
}

try {
    $statement = $db->prepare(
        'UPDATE orders SET status = :status WHERE id = :order_id'
    );
    $statement->execute([
        ':status' => $status,
        ':order_id' => $orderId,
    ]);

    if ($statement->rowCount() === 0) {
        jsonResponse(false, 'Pesanan tidak ditemukan atau statusnya tidak berubah.', null, 404);
    }

    jsonResponse(true, 'Status pesanan berhasil diperbarui.', [
        'id' => $orderId,
        'status' => $status,
    ]);
} catch (PDOException $e) {
    error_log('Gagal memperbarui status pesanan: ' . $e->getMessage());
    jsonResponse(false, 'Status pesanan gagal diperbarui.', null, 500);
}
