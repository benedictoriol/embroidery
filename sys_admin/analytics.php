<?php
session_start();
require_once '../config/db.php';
require_once 'partials.php';
require_role('sys_admin');

$overview = $pdo->query("
    SELECT
        (SELECT COUNT(*) FROM users) as total_users,
        (SELECT COUNT(*) FROM shops) as total_shops,
        (SELECT COUNT(*) FROM orders) as total_orders,
        (SELECT SUM(price) FROM orders WHERE status = 'completed') as total_revenue,
        (SELECT COUNT(*) FROM orders WHERE status = 'completed') as completed_orders,
        (SELECT COUNT(*) FROM orders WHERE status = 'pending') as pending_orders
")->fetch();

$dailyOrdersStmt = $pdo->query("
    SELECT DATE(created_at) as day, COUNT(*) as total
    FROM orders
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    GROUP BY DATE(created_at)
    ORDER BY day
");
$dailyOrdersRaw = $dailyOrdersStmt->fetchAll();

$dailyRevenueStmt = $pdo->query("
    SELECT DATE(created_at) as day, COALESCE(SUM(price), 0) as total
    FROM orders
    WHERE status = 'completed'
      AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    GROUP BY DATE(created_at)
    ORDER BY day
");
$dailyRevenueRaw = $dailyRevenueStmt->fetchAll();

$rangeDays = [];
for ($i = 6; $i >= 0; $i--) {
    $rangeDays[] = date('Y-m-d', strtotime("-$i days"));
}

$dailyOrders = [];
$dailyRevenue = [];
foreach ($rangeDays as $day) {
    $dailyOrders[$day] = 0;
    $dailyRevenue[$day] = 0;
}

foreach ($dailyOrdersRaw as $row) {
    $dailyOrders[$row['day']] = (int) $row['total'];
}

foreach ($dailyRevenueRaw as $row) {
    $dailyRevenue[$row['day']] = (float) $row['total'];
}

$statusBreakdown = $pdo->query("SELECT status, COUNT(*) as total FROM orders GROUP BY status")->fetchAll();
$statusMap = [];
foreach ($statusBreakdown as $row) {
    $statusMap[$row['status']] = $row['total'];
}

$topServices = $pdo->query("
    SELECT service_type, COUNT(*) as total
    FROM orders
    GROUP BY service_type
    ORDER BY total DESC
    LIMIT 5
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - System Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .analytics-grid {
            display: grid;
            grid-template-columns: repeat(12, 1fr);
            gap: 1.5rem;
            margin: 2rem 0;
        }

        .analytics-card {
            grid-column: span 4;
        }

        .chart-card {
            grid-column: span 8;
        }

        .data-card {
            grid-column: span 4;
        }

        .chart-bars {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 0.75rem;
            align-items: flex-end;
            height: 200px;
        }

        .chart-bar {
            background: var(--primary-100);
            border-radius: var(--radius);
            position: relative;
            overflow: hidden;
        }

        .chart-bar span {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: var(--primary-500);
            border-radius: var(--radius);
        }

        .metric-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--gray-100);
        }

        .metric-row:last-child {
            border-bottom: none;
        }
    </style>
</head>
<body>
    <?php sys_admin_nav('analytics'); ?>

    <div class="container">
        <div class="dashboard-header fade-in">
            <div class="d-flex justify-between align-center">
                <div>
                    <h2>Analytics Overview</h2>
                    <p class="text-muted">Track growth, activity, and revenue trends across the platform.</p>
                </div>
                <span class="badge badge-success"><i class="fas fa-chart-line"></i> Live Metrics</span>
            </div>
        </div>

        <div class="analytics-grid">
            <div class="card analytics-card">
                <h4>Total Users</h4>
                <h2><?php echo number_format($overview['total_users'] ?? 0); ?></h2>
                <p class="text-muted">Active accounts across roles.</p>
            </div>
            <div class="card analytics-card">
                <h4>Total Shops</h4>
                <h2><?php echo number_format($overview['total_shops'] ?? 0); ?></h2>
                <p class="text-muted">Registered service providers.</p>
            </div>
            <div class="card analytics-card">
                <h4>Revenue</h4>
                <h2>₱<?php echo number_format($overview['total_revenue'] ?? 0, 2); ?></h2>
                <p class="text-muted">Completed order earnings.</p>
            </div>

            <div class="card chart-card">
                <div class="card-header">
                    <h3><i class="fas fa-chart-bar text-primary"></i> Orders (Last 7 Days)</h3>
                    <p class="text-muted">Daily completed and total orders.</p>
                </div>
                <div class="chart-bars">
                    <?php foreach ($rangeDays as $day): ?>
                        <?php $height = $dailyOrders[$day] > 0 ? min(200, 20 + $dailyOrders[$day] * 10) : 20; ?>
                        <div class="chart-bar" title="<?php echo $dailyOrders[$day]; ?> orders">
                            <span style="height: <?php echo $height; ?>px;"></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="d-flex justify-between mt-3 text-muted">
                    <?php foreach ($rangeDays as $day): ?>
                        <small><?php echo date('D', strtotime($day)); ?></small>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="card data-card">
                <div class="card-header">
                    <h3><i class="fas fa-layer-group text-info"></i> Order Status</h3>
                    <p class="text-muted">Current distribution by status.</p>
                </div>
                <div>
                    <?php foreach (['pending', 'in_progress', 'completed', 'cancelled'] as $status): ?>
                        <div class="metric-row">
                            <span><?php echo ucfirst(str_replace('_', ' ', $status)); ?></span>
                            <strong><?php echo $statusMap[$status] ?? 0; ?></strong>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="card chart-card">
                <div class="card-header">
                    <h3><i class="fas fa-coins text-success"></i> Revenue Trend</h3>
                    <p class="text-muted">Daily revenue from completed orders.</p>
                </div>
                <div class="chart-bars">
                    <?php foreach ($rangeDays as $day): ?>
                        <?php $height = $dailyRevenue[$day] > 0 ? min(200, 20 + ($dailyRevenue[$day] / 100)) : 20; ?>
                        <div class="chart-bar" title="₱<?php echo number_format($dailyRevenue[$day], 2); ?>">
                            <span style="height: <?php echo $height; ?>px; background: var(--success-500);"></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="d-flex justify-between mt-3 text-muted">
                    <?php foreach ($rangeDays as $day): ?>
                        <small><?php echo date('D', strtotime($day)); ?></small>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="card data-card">
                <div class="card-header">
                    <h3><i class="fas fa-star text-warning"></i> Top Services</h3>
                    <p class="text-muted">Most requested services.</p>
                </div>
                <?php if (empty($topServices)): ?>
                    <p class="text-muted">No services data yet.</p>
                <?php else: ?>
                    <?php foreach ($topServices as $service): ?>
                        <div class="metric-row">
                            <span><?php echo htmlspecialchars($service['service_type'] ?? 'N/A'); ?></span>
                            <strong><?php echo (int) $service['total']; ?></strong>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php sys_admin_footer(); ?>
</body>
</html>
