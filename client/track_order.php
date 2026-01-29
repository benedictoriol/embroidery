<?php
session_start();
require_once '../config/db.php';
require_role('client');

$client_id = $_SESSION['user']['id'];
$allowed_filters = ['pending', 'accepted', 'in_progress', 'completed', 'cancelled'];
$filter = $_GET['filter'] ?? 'all';

$query = "
    SELECT o.*, s.shop_name, s.logo
    FROM orders o
    JOIN shops s ON o.shop_id = s.id
    WHERE o.client_id = ?
";
$params = [$client_id];

if(in_array($filter, $allowed_filters, true)) {
    $query .= " AND o.status = ?";
    $params[] = $filter;
}

$query .= " ORDER BY o.created_at DESC";

$orders_stmt = $pdo->prepare($query);
$orders_stmt->execute($params);
$orders = $orders_stmt->fetchAll();

$order_photos = [];
if(!empty($orders)) {
    $order_ids = array_column($orders, 'id');
    $placeholders = implode(',', array_fill(0, count($order_ids), '?'));
    $photos_stmt = $pdo->prepare("
        SELECT * FROM order_photos
        WHERE order_id IN ($placeholders)
        ORDER BY uploaded_at DESC
    ");
    $photos_stmt->execute($order_ids);
    $photos = $photos_stmt->fetchAll();

    foreach($photos as $photo) {
        $order_photos[$photo['order_id']][] = $photo;
    }
}

function status_pill($status) {
    $map = [
        'pending' => 'status-pending',
        'accepted' => 'status-accepted',
        'in_progress' => 'status-in_progress',
        'completed' => 'status-completed',
        'cancelled' => 'status-cancelled'
    ];
    $class = $map[$status] ?? 'status-pending';
    return '<span class="status-pill ' . $class . '">' . ucfirst(str_replace('_', ' ', $status)) . '</span>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Orders</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .filter-tabs {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        .filter-tabs a {
            padding: 8px 16px;
            border-radius: 20px;
            background: #f1f5f9;
            color: #475569;
            text-decoration: none;
        }
        .filter-tabs a.active {
            background: #4f46e5;
            color: white;
        }
        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
            text-transform: capitalize;
        }
        .status-pending { background: #fef9c3; color: #92400e; }
        .status-accepted { background: #e0f2fe; color: #0369a1; }
        .status-in_progress { background: #e0e7ff; color: #3730a3; }
        .status-completed { background: #dcfce7; color: #166534; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }
        .order-card {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            background: #fff;
            margin-bottom: 16px;
        }
        .order-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
            margin-top: 12px;
            color: #64748b;
            font-size: 0.9rem;
        }
        .progress-bar {
            width: 100%;
            height: 8px;
            background: #e2e8f0;
            border-radius: 6px;
            overflow: hidden;
            margin-top: 12px;
        }
        .progress-fill {
            height: 100%;
            background: #4f46e5;
        }
        .photo-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 10px;
            margin-top: 12px;
        }
        .photo-row img {
            width: 100%;
            height: 90px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container d-flex justify-between align-center">
            <a href="dashboard.php" class="navbar-brand">
                <i class="fas fa-route"></i> Track Orders
            </a>
            <ul class="navbar-nav">
                <li><a href="dashboard.php" class="nav-link">Dashboard</a></li>
                <li><a href="place_order.php" class="nav-link">Place Order</a></li>
                <li><a href="track_order.php" class="nav-link active">Track Orders</a></li>
                <li><a href="customize_design.php" class="nav-link">Customize Design</a></li>
                <li><a href="rate_provider.php" class="nav-link">Rate Provider</a></li>
                <li><a href="../auth/logout.php" class="nav-link">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="dashboard-header">
            <h2>Track Your Orders</h2>
            <p class="text-muted">Stay updated on current progress, timelines, and shop updates.</p>
        </div>

        <div class="filter-tabs">
            <a href="track_order.php" class="<?php echo $filter === 'all' ? 'active' : ''; ?>">All</a>
            <a href="track_order.php?filter=pending" class="<?php echo $filter === 'pending' ? 'active' : ''; ?>">Pending</a>
            <a href="track_order.php?filter=accepted" class="<?php echo $filter === 'accepted' ? 'active' : ''; ?>">Accepted</a>
            <a href="track_order.php?filter=in_progress" class="<?php echo $filter === 'in_progress' ? 'active' : ''; ?>">In Progress</a>
            <a href="track_order.php?filter=completed" class="<?php echo $filter === 'completed' ? 'active' : ''; ?>">Completed</a>
            <a href="track_order.php?filter=cancelled" class="<?php echo $filter === 'cancelled' ? 'active' : ''; ?>">Cancelled</a>
        </div>

        <?php if(!empty($orders)): ?>
            <?php foreach($orders as $order): ?>
                <div class="order-card">
                    <div class="d-flex justify-between align-center">
                        <div>
                            <h4 class="mb-1"><?php echo htmlspecialchars($order['service_type']); ?></h4>
                            <p class="text-muted mb-0">
                                <i class="fas fa-store"></i> <?php echo htmlspecialchars($order['shop_name']); ?>
                            </p>
                        </div>
                        <div class="text-right">
                            <?php echo status_pill($order['status']); ?>
                            <div class="text-muted mt-2">#<?php echo htmlspecialchars($order['order_number']); ?></div>
                        </div>
                    </div>

                    <div class="order-meta">
                        <div><i class="fas fa-calendar"></i> Created: <?php echo date('M d, Y', strtotime($order['created_at'])); ?></div>
                        <div><i class="fas fa-box"></i> Quantity: <?php echo htmlspecialchars($order['quantity']); ?></div>
                        <div><i class="fas fa-peso-sign"></i> ₱<?php echo number_format($order['price'] ?? 0, 2); ?></div>
                        <?php if(!empty($order['design_file'])): ?>
                            <div><i class="fas fa-paperclip"></i>
                                <a href="../assets/uploads/designs/<?php echo htmlspecialchars($order['design_file']); ?>" target="_blank">View design file</a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="mt-3">
                        <strong>Progress: <?php echo $order['progress']; ?>%</strong>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo $order['progress']; ?>%;"></div>
                        </div>
                    </div>

                    <?php if(!empty($order['design_description'])): ?>
                        <p class="text-muted mt-3"><i class="fas fa-clipboard"></i> <?php echo htmlspecialchars($order['design_description']); ?></p>
                    <?php endif; ?>

                    <?php if(!empty($order_photos[$order['id']])): ?>
                        <div class="mt-3">
                            <strong>Latest Photos</strong>
                            <div class="photo-row">
                                <?php foreach(array_slice($order_photos[$order['id']], 0, 3) as $photo): ?>
                                    <img src="../assets/uploads/<?php echo htmlspecialchars($photo['photo_url']); ?>" alt="Order photo">
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="card">
                <div class="text-center p-4">
                    <i class="fas fa-route fa-3x text-muted mb-3"></i>
                    <h4>No Orders Found</h4>
                    <p class="text-muted">Orders matching this filter will appear here.</p>
                    <a href="place_order.php" class="btn btn-primary">Place an Order</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
