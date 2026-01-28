<?php
session_start();
require_once '../config/db.php';
require_role('owner');

$owner_id = $_SESSION['user']['id'];
$shop_stmt = $pdo->prepare("SELECT * FROM shops WHERE owner_id = ?");
$shop_stmt->execute([$owner_id]);
$shop = $shop_stmt->fetch();

if(!$shop) {
    header("Location: create_shop.php");
    exit();
}

$shop_id = $shop['id'];
$allowed_filters = ['pending', 'in_progress', 'completed'];
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

$query = "
    SELECT o.*, u.fullname as client_name 
    FROM orders o 
    JOIN users u ON o.client_id = u.id 
    WHERE o.shop_id = ?
";
$params = [$shop_id];

if(in_array($filter, $allowed_filters, true)) {
    $query .= " AND o.status = ?";
    $params[] = $filter;
}

$query .= " ORDER BY o.created_at DESC";

$orders_stmt = $pdo->prepare($query);
$orders_stmt->execute($params);
$orders = $orders_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop Orders - <?php echo htmlspecialchars($shop['shop_name']); ?></title>
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
        .status-in_progress { background: #e0f2fe; color: #0369a1; }
        .status-completed { background: #dcfce7; color: #166534; }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container d-flex justify-between align-center">
            <a href="dashboard.php" class="navbar-brand">
                <i class="fas fa-store"></i> <?php echo htmlspecialchars($shop['shop_name']); ?>
            </a>
            <ul class="navbar-nav">
                <li><a href="dashboard.php" class="nav-link">Dashboard</a></li>
                <li><a href="shop_profile.php" class="nav-link">Shop Profile</a></li>
                <li><a href="manage_staff.php" class="nav-link">Staff</a></li>
                <li><a href="shop_orders.php" class="nav-link active">Orders</a></li>
                <li><a href="earnings.php" class="nav-link">Earnings</a></li>
                <li><a href="../auth/logout.php" class="nav-link">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="dashboard-header">
            <h2>Shop Orders</h2>
            <p class="text-muted">Review and track all orders submitted to your shop.</p>
        </div>

        <div class="filter-tabs">
            <a href="shop_orders.php" class="<?php echo $filter === 'all' ? 'active' : ''; ?>">All</a>
            <a href="shop_orders.php?filter=pending" class="<?php echo $filter === 'pending' ? 'active' : ''; ?>">Pending</a>
            <a href="shop_orders.php?filter=in_progress" class="<?php echo $filter === 'in_progress' ? 'active' : ''; ?>">In Progress</a>
            <a href="shop_orders.php?filter=completed" class="<?php echo $filter === 'completed' ? 'active' : ''; ?>">Completed</a>
        </div>

        <div class="card">
            <h3>Orders (<?php echo count($orders); ?>)</h3>
            <?php if(!empty($orders)): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Client</th>
                            <th>Service</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($orders as $order): ?>
                            <tr>
                                <td>#<?php echo htmlspecialchars($order['order_number']); ?></td>
                                <td><?php echo htmlspecialchars($order['client_name']); ?></td>
                                <td><?php echo htmlspecialchars($order['service_type']); ?></td>
                                <td>₱<?php echo number_format($order['price'], 2); ?></td>
                                <td>
                                    <span class="status-pill status-<?php echo htmlspecialchars($order['status']); ?>">
                                        <?php echo str_replace('_', ' ', ucfirst($order['status'])); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="text-center p-4">
                    <i class="fas fa-receipt fa-3x text-muted mb-3"></i>
                    <h4>No Orders Found</h4>
                    <p class="text-muted">Orders matching this filter will appear here.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
