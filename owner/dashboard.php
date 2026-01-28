<?php
session_start();
include('../config/db.php');

if(!isset($_SESSION['user']) || $_SESSION['user']['role'] != 'owner') {
    header("Location: ../auth/login.php");
    exit();
}

$owner_id = $_SESSION['user']['id'];

// Get shop details
$shop = $pdo->prepare("SELECT * FROM shops WHERE owner_id = ?");
$shop->execute([$owner_id]);
$shop_data = $shop->fetch();

if(!$shop_data) {
    header("Location: shop_profile.php?setup=required");
    exit();
}

$shop_id = $shop_data['id'];

// Shop statistics
$stats = $pdo->prepare("
    SELECT 
        (SELECT COUNT(*) FROM orders WHERE shop_id = ?) as total_orders,
        (SELECT COUNT(*) FROM orders WHERE shop_id = ? AND status = 'pending') as pending_orders,
        (SELECT COUNT(*) FROM orders WHERE shop_id = ? AND status = 'in_progress') as in_progress_orders,
        (SELECT SUM(price) FROM orders WHERE shop_id = ? AND status = 'completed') as total_earnings,
        (SELECT COUNT(*) FROM shop_employees WHERE shop_id = ? AND status = 'active') as total_staff
");
$stats->execute([$shop_id, $shop_id, $shop_id, $shop_id, $shop_id]);
$shop_stats = $stats->fetch();

// Recent orders
$recent_orders = $pdo->prepare("
    SELECT o.*, u.fullname as client_name 
    FROM orders o 
    JOIN users u ON o.client_id = u.id 
    WHERE o.shop_id = ? 
    ORDER BY o.created_at DESC 
    LIMIT 5
");
$recent_orders->execute([$shop_id]);
$orders = $recent_orders->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Owner Dashboard - <?php echo htmlspecialchars($shop_data['shop_name']); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .shop-header {
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .shop-rating {
            background: rgba(255,255,255,0.2);
            padding: 5px 15px;
            border-radius: 20px;
            display: inline-block;
        }
        .quick-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .stat-item {
            background: white;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container d-flex justify-between align-center">
            <a href="dashboard.php" class="navbar-brand">
                <i class="fas fa-store"></i> <?php echo htmlspecialchars($shop_data['shop_name']); ?>
            </a>
            <ul class="navbar-nav">
                <li><a href="dashboard.php" class="nav-link active">Dashboard</a></li>
                <li><a href="shop_profile.php" class="nav-link">Shop Profile</a></li>
                <li><a href="manage_staff.php" class="nav-link">Staff</a></li>
                <li><a href="shop_orders.php" class="nav-link">Orders</a></li>
                <li><a href="earnings.php" class="nav-link">Earnings</a></li>
                <li><a href="../auth/logout.php" class="nav-link">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <!-- Shop Header -->
        <div class="shop-header">
            <div class="d-flex justify-between align-center">
                <div>
                    <h2><?php echo htmlspecialchars($shop_data['shop_name']); ?></h2>
                    <p class="mb-0"><?php echo htmlspecialchars($shop_data['shop_description']); ?></p>
                </div>
                <div class="text-right">
                    <div class="shop-rating">
                        <i class="fas fa-star"></i> <?php echo number_format($shop_data['rating'], 1); ?>
                        <small>(<?php echo $shop_data['total_orders']; ?> orders)</small>
                    </div>
                    <p class="mb-0 mt-2"><?php echo htmlspecialchars($shop_data['address']); ?></p>
                </div>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="quick-stats">
            <div class="stat-item">
                <div class="stat-number text-primary"><?php echo $shop_stats['total_orders']; ?></div>
                <div class="stat-label">Total Orders</div>
            </div>
            <div class="stat-item">
                <div class="stat-number text-warning"><?php echo $shop_stats['pending_orders']; ?></div>
                <div class="stat-label">Pending</div>
            </div>
            <div class="stat-item">
                <div class="stat-number text-info"><?php echo $shop_stats['in_progress_orders']; ?></div>
                <div class="stat-label">In Progress</div>
            </div>
            <div class="stat-item">
                <div class="stat-number text-success">₱<?php echo number_format($shop_stats['total_earnings'], 2); ?></div>
                <div class="stat-label">Total Earnings</div>
            </div>
            <div class="stat-item">
                <div class="stat-number text-danger"><?php echo $shop_stats['total_staff']; ?></div>
                <div class="stat-label">Staff Members</div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card">
            <h3>Quick Actions</h3>
            <div class="d-flex flex-wrap" style="gap: 10px;">
                <a href="shop_orders.php?filter=pending" class="btn btn-primary">
                    <i class="fas fa-clipboard-check"></i> Review Orders (<?php echo $shop_stats['pending_orders']; ?>)
                </a>
                <a href="manage_staff.php" class="btn btn-outline-primary">
                    <i class="fas fa-users"></i> Manage Staff
                </a>
                <a href="shop_profile.php" class="btn btn-outline-success">
                    <i class="fas fa-edit"></i> Edit Shop Profile
                </a>
                <a href="earnings.php" class="btn btn-outline-warning">
                    <i class="fas fa-chart-line"></i> View Performance
                </a>
            </div>
        </div>

        <!-- Recent Orders -->
        <div class="card">
            <h3>Recent Orders</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Client</th>
                        <th>Service</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($orders as $order): ?>
                    <tr>
                        <td>#<?php echo $order['order_number']; ?></td>
                        <td><?php echo htmlspecialchars($order['client_name']); ?></td>
                        <td><?php echo htmlspecialchars($order['service_type']); ?></td>
                        <td>₱<?php echo number_format($order['price'], 2); ?></td>
                        <td>
                            <?php
                            $status_badge = [
                                'pending' => 'warning',
                                'accepted' => 'primary',
                                'rejected' => 'danger',
                                'in_progress' => 'info',
                                'completed' => 'success',
                                'cancelled' => 'secondary'
                            ];
                            ?>
                            <span class="badge badge-<?php echo $status_badge[$order['status']]; ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?>
                            </span>
                        </td>
                        <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                        <td>
                            <?php if($order['status'] == 'pending'): ?>
                                <div class="d-flex" style="gap: 5px;">
                                    <a href="accept_order.php?id=<?php echo $order['id']; ?>" 
                                       class="btn btn-sm btn-success">Accept</a>
                                    <a href="reject_order.php?id=<?php echo $order['id']; ?>" 
                                       class="btn btn-sm btn-danger">Reject</a>
                                </div>
                            <?php else: ?>
                                <a href="view_order.php?id=<?php echo $order['id']; ?>" 
                                   class="btn btn-sm btn-outline-primary">View</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="text-center">
                <a href="shop_orders.php" class="btn btn-outline-primary">View All Orders</a>
            </div>
        </div>

        <!-- Shop Performance -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-number">
                    <?php 
                    $weekly = $pdo->prepare("
                        SELECT COUNT(*) as count 
                        FROM orders 
                        WHERE shop_id = ? 
                        AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                    ");
                    $weekly->execute([$shop_id]);
                    echo $weekly->fetch()['count'];
                    ?>
                </div>
                <div class="stat-label">Orders This Week</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-number">
                    <?php 
                    $avg = $pdo->prepare("
                        SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, completed_at)) as avg_hours 
                        FROM orders 
                        WHERE shop_id = ? AND status = 'completed'
                    ");
                    $avg->execute([$shop_id]);
                    echo round($avg->fetch()['avg_hours'], 1);
                    ?>
                </div>
                <div class="stat-label">Avg Completion (hours)</div>
            </div>
            <div class="stat-card"><?php
session_start();
require_once '../config/db.php';
require_role('owner');

$owner_id = $_SESSION['user']['id'];

// Get shop details
$shop_stmt = $pdo->prepare("SELECT * FROM shops WHERE owner_id = ?");
$shop_stmt->execute([$owner_id]);
$shop = $shop_stmt->fetch();

// If no shop exists, redirect to create shop
if(!$shop) {
    header("Location: create_shop.php");
    exit();
}

$shop_id = $shop['id'];

// Get shop statistics
$stats_stmt = $pdo->prepare("
    SELECT 
        (SELECT COUNT(*) FROM orders WHERE shop_id = ?) as total_orders,
        (SELECT COUNT(*) FROM orders WHERE shop_id = ? AND status = 'pending') as pending_orders,
        (SELECT COUNT(*) FROM orders WHERE shop_id = ? AND status = 'in_progress') as active_orders,
        (SELECT COUNT(*) FROM orders WHERE shop_id = ? AND status = 'completed') as completed_orders,
        (SELECT SUM(price) FROM orders WHERE shop_id = ? AND status = 'completed') as total_earnings,
        (SELECT COUNT(*) FROM shop_employees WHERE shop_id = ? AND status = 'active') as total_staff,
        (SELECT AVG(rating) FROM orders WHERE shop_id = ? AND rating IS NOT NULL) as avg_rating
");
$stats_stmt->execute([$shop_id, $shop_id, $shop_id, $shop_id, $shop_id, $shop_id, $shop_id]);
$stats = $stats_stmt->fetch();

// Recent orders
$orders_stmt = $pdo->prepare("
    SELECT o.*, u.fullname as client_name 
    FROM orders o 
    JOIN users u ON o.client_id = u.id 
    WHERE o.shop_id = ? 
    ORDER BY o.created_at DESC 
    LIMIT 5
");
$orders_stmt->execute([$shop_id]);
$recent_orders = $orders_stmt->fetchAll();

// Recent employees
$employees_stmt = $pdo->prepare("
    SELECT se.*, u.fullname, u.email 
    FROM shop_employees se 
    JOIN users u ON se.user_id = u.id 
    WHERE se.shop_id = ? 
    ORDER BY se.created_at DESC 
    LIMIT 3
");
$employees_stmt->execute([$shop_id]);
$recent_employees = $employees_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Owner Dashboard - <?php echo htmlspecialchars($shop['shop_name']); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .shop-header {
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 25px;
        }
        .shop-rating {
            background: rgba(255,255,255,0.2);
            padding: 8px 20px;
            border-radius: 25px;
            display: inline-block;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin: 25px 0;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            text-align: center;
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .order-status {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 5px;
        }
        .pending { background: #ffc107; }
        .accepted { background: #17a2b8; }
        .in_progress { background: #007bff; }
        .completed { background: #28a745; }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container d-flex justify-between align-center">
            <a href="dashboard.php" class="navbar-brand">
                <i class="fas fa-store"></i> <?php echo htmlspecialchars($shop['shop_name']); ?>
            </a>
            <ul class="navbar-nav">
                <li><a href="dashboard.php" class="nav-link active">Dashboard</a></li>
                <li><a href="shop_profile.php" class="nav-link">Shop Profile</a></li>
                <li><a href="manage_staff.php" class="nav-link">Staff</a></li>
                <li><a href="shop_orders.php" class="nav-link">Orders</a></li>
                <li><a href="earnings.php" class="nav-link">Earnings</a></li>
                <li class="dropdown">
                    <a href="#" class="nav-link dropdown-toggle">
                        <i class="fas fa-user"></i> <?php echo $_SESSION['user']['fullname']; ?>
                    </a>
                    <div class="dropdown-menu">
                        <a href="profile.php" class="dropdown-item"><i class="fas fa-user-cog"></i> Profile</a>
                        <a href="../auth/logout.php" class="dropdown-item"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <!-- Shop Header -->
        <div class="shop-header">
            <div class="d-flex justify-between align-center">
                <div>
                    <h2><?php echo htmlspecialchars($shop['shop_name']); ?></h2>
                    <p class="mb-0"><?php echo htmlspecialchars($shop['shop_description']); ?></p>
                    <div class="mt-2">
                        <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($shop['address']); ?>
                    </div>
                </div>
                <div class="text-right">
                    <div class="shop-rating mb-3">
                        <i class="fas fa-star"></i> 
                        <strong><?php echo number_format($stats['avg_rating'] ?? 0, 1); ?></strong>
                        <small>(<?php echo $stats['completed_orders']; ?> completed orders)</small>
                    </div>
                    <div>
                        <a href="shop_profile.php" class="btn btn-light btn-sm">
                            <i class="fas fa-edit"></i> Edit Profile
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon text-primary">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="stat-number"><?php echo $stats['total_orders']; ?></div>
                <div class="stat-label">Total Orders</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon text-warning">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-number"><?php echo $stats['pending_orders']; ?></div>
                <div class="stat-label">Pending Orders</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon text-info">
                    <i class="fas fa-spinner"></i>
                </div>
                <div class="stat-number"><?php echo $stats['active_orders']; ?></div>
                <div class="stat-label">Active Orders</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon text-success">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="stat-number">₱<?php echo number_format($stats['total_earnings'], 2); ?></div>
                <div class="stat-label">Total Earnings</div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card mb-4">
            <h3>Quick Actions</h3>
            <div class="d-flex flex-wrap" style="gap: 10px;">
                <a href="shop_orders.php?filter=pending" class="btn btn-primary">
                    <i class="fas fa-clipboard-check"></i> Review Orders (<?php echo $stats['pending_orders']; ?>)
                </a>
                <a href="manage_staff.php" class="btn btn-outline-primary">
                    <i class="fas fa-users"></i> Manage Staff (<?php echo $stats['total_staff']; ?>)
                </a>
                <a href="shop_profile.php" class="btn btn-outline-success">
                    <i class="fas fa-edit"></i> Edit Shop Profile
                </a>
                <a href="earnings.php" class="btn btn-outline-warning">
                    <i class="fas fa-chart-line"></i> View Earnings
                </a>
                <a href="add_staff.php" class="btn btn-outline-info">
                    <i class="fas fa-user-plus"></i> Add New Staff
                </a>
            </div>
        </div>

        <div class="row" style="display: flex; gap: 20px;">
            <!-- Recent Orders -->
            <div style="flex: 2;">
                <div class="card">
                    <h3>Recent Orders</h3>
                    <?php if(!empty($recent_orders)): ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Order #</th>
                                    <th>Client</th>
                                    <th>Service</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($recent_orders as $order): ?>
                                <tr>
                                    <td>#<?php echo $order['order_number']; ?></td>
                                    <td><?php echo htmlspecialchars($order['client_name']); ?></td>
                                    <td><?php echo htmlspecialchars($order['service_type']); ?></td>
                                    <td>₱<?php echo number_format($order['price'], 2); ?></td>
                                    <td>
                                        <span class="order-status <?php echo $order['status']; ?>"></span>
                                        <?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?>
                                    </td>
                                    <td><?php echo date('M d', strtotime($order['created_at'])); ?></td>
                                    <td>
                                        <?php if($order['status'] == 'pending'): ?>
                                            <div class="d-flex" style="gap: 5px;">
                                                <a href="accept_order.php?id=<?php echo $order['id']; ?>" 
                                                   class="btn btn-sm btn-success">Accept</a>
                                                <a href="reject_order.php?id=<?php echo $order['id']; ?>" 
                                                   class="btn btn-sm btn-danger">Reject</a>
                                            </div>
                                        <?php else: ?>
                                            <a href="view_order.php?id=<?php echo $order['id']; ?>" 
                                               class="btn btn-sm btn-outline-primary">View</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <div class="text-center">
                            <a href="shop_orders.php" class="btn btn-outline-primary">View All Orders</a>
                        </div>
                    <?php else: ?>
                        <div class="text-center p-4">
                            <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                            <h4>No Orders Yet</h4>
                            <p class="text-muted">You haven't received any orders yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Staff & Shop Info -->
            <div style="flex: 1;">
                <!-- Staff Section -->
                <div class="card mb-4">
                    <h3>Recent Staff</h3>
                    <?php if(!empty($recent_employees)): ?>
                        <ul class="list-unstyled">
                            <?php foreach($recent_employees as $emp): ?>
                            <li class="d-flex justify-between align-center mb-3 p-2 border-bottom">
                                <div>
                                    <strong><?php echo htmlspecialchars($emp['fullname']); ?></strong><br>
                                    <small class="text-muted"><?php echo $emp['position']; ?></small>
                                </div>
                                <div>
                                    <span class="badge badge-success">Active</span>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        <div class="text-center">
                            <a href="manage_staff.php" class="btn btn-sm btn-outline-primary">Manage All Staff</a>
                        </div>
                    <?php else: ?>
                        <div class="text-center p-3">
                            <i class="fas fa-users fa-2x text-muted mb-2"></i>
                            <p class="text-muted">No staff members yet</p>
                            <a href="add_staff.php" class="btn btn-sm btn-primary">Add Staff</a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Shop Performance -->
                <div class="card">
                    <h3>Shop Performance</h3>
                    <div class="mb-3">
                        <div class="d-flex justify-between">
                            <span>Completion Rate:</span>
                            <strong>
                                <?php 
                                $completion_rate = $stats['total_orders'] > 0 
                                    ? ($stats['completed_orders'] / $stats['total_orders'] * 100) 
                                    : 0;
                                echo round($completion_rate, 1);
                                ?>%
                            </strong>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar bg-success" style="width: <?php echo $completion_rate; ?>%"></div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="d-flex justify-between">
                            <span>Average Rating:</span>
                            <strong><?php echo number_format($stats['avg_rating'] ?? 0, 1); ?>/5</strong>
                        </div>
                        <div class="text-warning">
                            <?php for($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star<?php echo $i <= ($stats['avg_rating'] ?? 0) ? '' : '-o'; ?>"></i>
                            <?php endfor; ?>
                        </div>
                    </div>
                    
                    <div>
                        <div class="d-flex justify-between">
                            <span>Active Staff:</span>
                            <strong><?php echo $stats['total_staff']; ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>&copy; 2024 <?php echo htmlspecialchars($shop['shop_name']); ?> - Owner Dashboard</p>
            <small class="text-muted">Shop ID: <?php echo $shop['id']; ?> | Status: <?php echo ucfirst($shop['status']); ?></small>
        </div>
    </footer>
</body>
</html>
                <div class="stat-icon">
                    <i class="fas fa-star-half-alt"></i>
                </div>
                <div class="stat-number">
                    <?php 
                    $reviews = $pdo->prepare("
                        SELECT AVG(rating) as avg_rating 
                        FROM orders 
                        WHERE shop_id = ? AND rating IS NOT NULL
                    ");
                    $reviews->execute([$shop_id]);
                    echo number_format($reviews->fetch()['avg_rating'], 1);
                    ?>
                </div>
                <div class="stat-label">Average Rating</div>
            </div>
        </div>
    </div>
</body>
</html>