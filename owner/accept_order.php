<?php
session_start();
require_once '../config/db.php';
require_role('owner');

$owner_id = $_SESSION['user']['id'];
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

$order_stmt = $pdo->prepare("SELECT status FROM orders WHERE id = ? AND shop_id = ?");
$order_stmt->execute([$order_id, $shop['id']]);
$order = $order_stmt->fetch();

if(!$order || $order['status'] !== 'pending') {
    header("Location: shop_orders.php?filter=pending");
    exit();
}

$update_stmt = $pdo->prepare("UPDATE orders SET status = 'accepted' WHERE id = ? AND shop_id = ?");
$update_stmt->execute([$order_id, $shop['id']]);

header("Location: shop_orders.php?filter=accepted&action=accepted");
exit();
