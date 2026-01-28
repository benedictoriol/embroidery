<?php
session_start();
require_once '../config/db.php';
require_role('client');

$client_id = $_SESSION['user']['id'];
$error = '';
$success = '';

if(isset($_POST['submit_rating'])) {
    $order_id = $_POST['order_id'] ?? '';
    $rating = (int)($_POST['rating'] ?? 0);

    if($rating < 1 || $rating > 5) {
        $error = 'Please select a rating between 1 and 5.';
    } else {
        $order_stmt = $pdo->prepare("
            SELECT id, shop_id
            FROM orders
            WHERE id = ? AND client_id = ? AND status = 'completed'
        ");
        $order_stmt->execute([$order_id, $client_id]);
        $order = $order_stmt->fetch();

        if(!$order) {
            $error = 'Unable to find a completed order to rate.';
        } else {
            $update_stmt = $pdo->prepare("UPDATE orders SET rating = ?, updated_at = NOW() WHERE id = ? AND client_id = ?");
            $update_stmt->execute([$rating, $order_id, $client_id]);

            $rating_stmt = $pdo->prepare("SELECT AVG(rating) as avg_rating FROM orders WHERE shop_id = ? AND rating IS NOT NULL AND rating > 0");
            $rating_stmt->execute([$order['shop_id']]);
            $avg_rating = $rating_stmt->fetchColumn();

            $shop_update = $pdo->prepare("UPDATE shops SET rating = ? WHERE id = ?");
            $shop_update->execute([$avg_rating ?: 0, $order['shop_id']]);

            $success = 'Thank you! Your rating has been submitted.';
        }
    }
}

$pending_stmt = $pdo->prepare("
    SELECT o.*, s.shop_name
    FROM orders o
    JOIN shops s ON o.shop_id = s.id
    WHERE o.client_id = ? AND o.status = 'completed' AND (o.rating IS NULL OR o.rating = 0)
    ORDER BY o.completed_at DESC, o.created_at DESC
");
$pending_stmt->execute([$client_id]);
$pending_orders = $pending_stmt->fetchAll();

$rated_stmt = $pdo->prepare("
    SELECT o.*, s.shop_name
    FROM orders o
    JOIN shops s ON o.shop_id = s.id
    WHERE o.client_id = ? AND o.status = 'completed' AND o.rating IS NOT NULL AND o.rating > 0
    ORDER BY o.completed_at DESC, o.created_at DESC
    LIMIT 5
");
$rated_stmt->execute([$client_id]);
$rated_orders = $rated_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rate Provider</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .rating-card {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            background: #fff;
            margin-bottom: 16px;
        }
        .rating-stars {
            display: flex;
            gap: 8px;
            margin: 10px 0 15px;
            flex-direction: row-reverse;
            justify-content: flex-end;
        }
        .rating-stars input {
            display: none;
        }
        .rating-stars label {
            cursor: pointer;
            font-size: 1.5rem;
            color: #cbd5f5;
            transition: color 0.2s ease;
        }
        .rating-stars input:checked ~ label {
            color: #f59e0b;
        }
        .rating-stars label:hover,
        .rating-stars label:hover ~ label {
            color: #fbbf24;
        }
        .rated-list {
            display: grid;
            gap: 12px;
        }
        .rated-card {
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 15px;
            background: #fff;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container d-flex justify-between align-center">
            <a href="dashboard.php" class="navbar-brand">
                <i class="fas fa-star"></i> Rate Providers
            </a>
            <ul class="navbar-nav">
                <li><a href="dashboard.php" class="nav-link">Dashboard</a></li>
                <li><a href="place_order.php" class="nav-link">Place Order</a></li>
                <li><a href="track_order.php" class="nav-link">Track Orders</a></li>
                <li><a href="customize_design.php" class="nav-link">Customize Design</a></li>
                <li><a href="rate_provider.php" class="nav-link active">Rate Provider</a></li>
                <li><a href="../auth/logout.php" class="nav-link">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="dashboard-header">
            <h2>Rate Your Providers</h2>
            <p class="text-muted">Share feedback for completed orders to help shops improve their service.</p>
        </div>

        <?php if($error): ?>
            <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
        <?php endif; ?>
        <?php if($success): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
        <?php endif; ?>

        <div class="card">
            <h3>Pending Ratings</h3>
            <?php if(!empty($pending_orders)): ?>
                <?php foreach($pending_orders as $order): ?>
                    <div class="rating-card">
                        <div class="d-flex justify-between align-center">
                            <div>
                                <h4 class="mb-1"><?php echo htmlspecialchars($order['service_type']); ?></h4>
                                <p class="text-muted mb-0"><i class="fas fa-store"></i> <?php echo htmlspecialchars($order['shop_name']); ?></p>
                            </div>
                            <div class="text-right">
                                <div class="text-muted">#<?php echo htmlspecialchars($order['order_number']); ?></div>
                                <?php if(!empty($order['completed_at'])): ?>
                                    <div class="text-muted">Completed: <?php echo date('M d, Y', strtotime($order['completed_at'])); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <form method="POST" class="mt-3">
                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                            <div class="rating-stars">
                                <?php for($i = 5; $i >= 1; $i--): ?>
                                    <input type="radio" id="rating-<?php echo $order['id']; ?>-<?php echo $i; ?>" name="rating" value="<?php echo $i; ?>" required>
                                    <label for="rating-<?php echo $order['id']; ?>-<?php echo $i; ?>">
                                        <i class="fas fa-star"></i>
                                    </label>
                                <?php endfor; ?>
                            </div>
                            <button type="submit" name="submit_rating" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i> Submit Rating
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center p-4">
                    <i class="fas fa-star fa-3x text-muted mb-3"></i>
                    <h4>No Pending Ratings</h4>
                    <p class="text-muted">You have rated all completed orders. Thank you!</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="card mt-4">
            <h3>Recently Rated</h3>
            <?php if(!empty($rated_orders)): ?>
                <div class="rated-list">
                    <?php foreach($rated_orders as $order): ?>
                        <div class="rated-card">
                            <div class="d-flex justify-between align-center">
                                <div>
                                    <strong><?php echo htmlspecialchars($order['service_type']); ?></strong>
                                    <div class="text-muted small"><i class="fas fa-store"></i> <?php echo htmlspecialchars($order['shop_name']); ?></div>
                                </div>
                                <div>
                                    <?php for($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star<?php echo $i <= $order['rating'] ? '' : '-o'; ?> text-warning"></i>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <div class="text-muted small mt-2">#<?php echo htmlspecialchars($order['order_number']); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center p-4">
                    <i class="fas fa-clipboard-check fa-3x text-muted mb-3"></i>
                    <h4>No Ratings Yet</h4>
                    <p class="text-muted">Complete an order to leave your first rating.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
