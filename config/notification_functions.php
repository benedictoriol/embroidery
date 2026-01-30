<?php
// config/notification_functions.php

/**
 * Create a notification log entry.
 *
 * @param PDO $pdo
 * @param int $user_id
 * @param int|null $order_id
 * @param string $title
 * @param string $message
 * @param string $type
 * @return void
 */
function create_notification(PDO $pdo, int $user_id, ?int $order_id, string $title, string $message, string $type = 'info'): void {
    $stmt = $pdo->prepare("
        INSERT INTO notifications (user_id, order_id, type, message, read_at, created_at)
        VALUES (?, ?, ?, ?, NULL, NOW())
    ");
    $stmt->execute([$user_id, $order_id, $title, $message, $type]);
}
?>
