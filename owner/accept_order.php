<?php
session_start();
require_once '../config/db.php';
require_role('owner');

$owner_id = $_SESSION['user']['id'];
$owner_role = $_SESSION['user']['role'] ?? null;
$order_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if($order_id <= 0) {
    header("Location: shop_orders.php");
    exit();
}

$shop_stmt = $pdo->prepare("SELECT id FROM shops WHERE owner_id = ?");
$shop_stmt->execute([$owner_id]);
$shop = $shop_stmt->fetch();

if(!$shop) {
    header("Location: create_shop.php");
    exit();
}

$order_stmt = $pdo->prepare("SELECT status, client_id, order_number FROM orders WHERE id = ? AND shop_id = ?");
$order_stmt->execute([$order_id, $shop['id']]);
$order = $order_stmt->fetch();

if(!$order || $order['status'] !== 'pending') {
    header("Location: shop_orders.php?filter=pending");
    exit();
}

$update_stmt = $pdo->prepare("UPDATE orders SET status = 'accepted' WHERE id = ? AND shop_id = ?");
$update_stmt->execute([$order_id, $shop['id']]);

create_notification(
    $pdo,
    (int) $order['client_id'],
    $order_id,
    'Order accepted',
    'Your order #' . $order['order_number'] . ' has been accepted and will be scheduled shortly.',
    'success'
);

log_audit(
    $pdo,
    $owner_id,
    $owner_role,
    'accept_order',
    'orders',
    $order_id,
    ['status' => $order['status'] ?? null],
    ['status' => 'accepted']
);

header("Location: shop_orders.php?filter=accepted&action=accepted");
exit();
