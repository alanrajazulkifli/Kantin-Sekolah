<?php
require_once '../../config/database.php';
require_once '../../helpers/response.php';
require_once '../../helpers/auth.php';

setHeaders();
requireMethod('POST');

$database = new Database();
$db = $database->getConnection();
$user = requireAuth($db);
requireRole($user, ['siswa']);

$input = getJsonInput();
$customerName = trim((string) ($input['customer_name'] ?? ''));
$items = $input['items'] ?? null;

if (
    $customerName === '' ||
    strlen($customerName) > 100 ||
    !is_array($items) ||
    count($items) === 0 ||
    count($items) > 50
) {
    jsonResponse(false, 'Nama pemesan dan daftar menu wajib diisi.', null, 400);
}

$quantities = [];
foreach ($items as $item) {
    if (!is_array($item)) {
        jsonResponse(false, 'Format detail pesanan tidak valid.', null, 400);
    }

    $menuId = filter_var($item['menu_id'] ?? null, FILTER_VALIDATE_INT);
    $quantity = filter_var($item['quantity'] ?? null, FILTER_VALIDATE_INT);
    if (
        $menuId === false || $menuId < 1 ||
        $quantity === false || $quantity < 1 || $quantity > 99 ||
        isset($quantities[$menuId])
    ) {
        jsonResponse(false, 'Menu atau jumlah pesanan tidak valid.', null, 400);
    }
    $quantities[$menuId] = $quantity;
}

try {
    $db->beginTransaction();

    $placeholders = implode(',', array_fill(0, count($quantities), '?'));
    $menuQuery = $db->prepare(
        "SELECT id, nama, harga, stok FROM menus WHERE id IN ($placeholders) FOR UPDATE"
    );
    $menuQuery->execute(array_keys($quantities));
    $menus = [];
    foreach ($menuQuery->fetchAll(PDO::FETCH_ASSOC) as $menu) {
        $menus[(int) $menu['id']] = $menu;
    }

    if (count($menus) !== count($quantities)) {
        $db->rollBack();
        jsonResponse(false, 'Salah satu menu tidak ditemukan.', null, 404);
    }

    $totalCents = 0;
    foreach ($quantities as $menuId => $quantity) {
        $stock = (int) ($menus[$menuId]['stok'] ?? 0);
        if ($quantity > $stock) {
            $db->rollBack();
            jsonResponse(
                false,
                'Stok menu "' . $menus[$menuId]['nama'] . '" tidak mencukupi.',
                null,
                409
            );
        }

        $unitCents = (int) round((float) $menus[$menuId]['harga'] * 100);
        $totalCents += $unitCents * $quantity;
    }

    $orderStatement = $db->prepare(
        'INSERT INTO orders (user_id, customer_name, total_price)
         VALUES (:user_id, :customer_name, :total_price)'
    );
    $orderStatement->execute([
        ':user_id' => $user['id'],
        ':customer_name' => $customerName,
        ':total_price' => number_format($totalCents / 100, 2, '.', ''),
    ]);
    $orderId = (int) $db->lastInsertId();

    $detailStatement = $db->prepare(
        'INSERT INTO order_details (order_id, menu_id, quantity, unit_price, subtotal)
         VALUES (:order_id, :menu_id, :quantity, :unit_price, :subtotal)'
    );
    $stockStatement = $db->prepare(
        'UPDATE menus SET stok = stok - :deduct_quantity
         WHERE id = :menu_id AND stok >= :available_quantity'
    );

    foreach ($quantities as $menuId => $quantity) {
        $unitCents = (int) round((float) $menus[$menuId]['harga'] * 100);
        $detailStatement->execute([
            ':order_id' => $orderId,
            ':menu_id' => $menuId,
            ':quantity' => $quantity,
            ':unit_price' => number_format($unitCents / 100, 2, '.', ''),
            ':subtotal' => number_format(($unitCents * $quantity) / 100, 2, '.', ''),
        ]);
        $stockStatement->execute([
            ':deduct_quantity' => $quantity,
            ':menu_id' => $menuId,
            ':available_quantity' => $quantity,
        ]);
    }

    $db->commit();
    jsonResponse(true, 'Pesanan berhasil dibuat.', [
        'id' => $orderId,
        'total_price' => number_format($totalCents / 100, 2, '.', ''),
    ], 201);
} catch (PDOException $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log('Gagal membuat pesanan: ' . $e->getMessage());
    jsonResponse(false, 'Pesanan gagal disimpan karena kesalahan server.', null, 500);
}
