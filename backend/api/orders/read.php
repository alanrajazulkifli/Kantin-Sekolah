<?php
require_once '../../config/database.php';
require_once '../../helpers/response.php';
require_once '../../helpers/auth.php';

setHeaders();
requireMethod('GET');

$database = new Database();
$db = $database->getConnection();
$user = requireAuth($db);
requireRole($user, ['admin', 'siswa']);

$sql = 'SELECT o.id, o.user_id, o.customer_name, o.total_price, o.created_at,
               u.name AS student_name, od.menu_id, od.quantity,
               od.unit_price, od.subtotal, m.nama AS menu_name
        FROM orders o
        JOIN users u ON u.id = o.user_id
        LEFT JOIN order_details od ON od.order_id = o.id
        LEFT JOIN menus m ON m.id = od.menu_id';
$parameters = [];
if ($user['role'] === 'siswa') {
    $sql .= ' WHERE o.user_id = :user_id';
    $parameters[':user_id'] = $user['id'];
}
$sql .= ' ORDER BY o.created_at DESC, o.id DESC, od.id ASC';

try {
    $statement = $db->prepare($sql);
    $statement->execute($parameters);
    $orders = [];

    while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
        $orderId = (int) $row['id'];
        if (!isset($orders[$orderId])) {
            $orders[$orderId] = [
                'id' => $orderId,
                'user_id' => (int) $row['user_id'],
                'student_name' => $row['student_name'],
                'customer_name' => $row['customer_name'],
                'total_price' => $row['total_price'],
                'created_at' => $row['created_at'],
                'details' => [],
            ];
        }

        if ($row['menu_id'] !== null) {
            $orders[$orderId]['details'][] = [
                'menu_id' => (int) $row['menu_id'],
                'menu_name' => $row['menu_name'],
                'quantity' => (int) $row['quantity'],
                'unit_price' => $row['unit_price'],
                'subtotal' => $row['subtotal'],
            ];
        }
    }

    jsonResponse(true, 'Daftar pesanan berhasil dimuat.', array_values($orders));
} catch (PDOException $e) {
    error_log('Gagal membaca pesanan: ' . $e->getMessage());
    jsonResponse(false, 'Daftar pesanan tidak dapat dimuat.', null, 500);
}
