<?php
session_start();
require_once '../config/db.php';
require_role('employee');

$employee_id = $_SESSION['user']['id'];

$emp_stmt = $pdo->prepare("
    SELECT se.*, s.shop_name, s.logo 
    FROM shop_employees se 
    JOIN shops s ON se.shop_id = s.id 
    WHERE se.user_id = ? AND se.status = 'active'
");
$emp_stmt->execute([$employee_id]);
$employee = $emp_stmt->fetch();

if(!$employee) {
    die("You are not assigned to any shop. Please contact your shop owner.");
}

$permissions = [
    'view_jobs' => true,
    'update_status' => true,
    'upload_photos' => true
];
if (!empty($employee['permissions'])) {
    $decoded_permissions = json_decode($employee['permissions'], true);
    if (is_array($decoded_permissions)) {
        $permissions = array_merge($permissions, $decoded_permissions);
    }
}

if (empty($permissions['view_jobs'])) {
    header("Location: schedule.php");
    exit();
}


$jobs_stmt = $pdo->prepare("
    SELECT o.*, u.fullname as client_name, s.shop_name
    FROM orders o 
    JOIN users u ON o.client_id = u.id 
    JOIN shops s ON o.shop_id = s.id
    WHERE o.assigned_to = ?
    ORDER BY o.scheduled_date ASC, o.created_at DESC
");
$jobs_stmt->execute([$employee_id]);
$assigned_jobs = $jobs_stmt->fetchAll();

function job_status_badge($status) {
    $map = [
        'accepted' => 'badge-primary',
        'in_progress' => 'badge-warning',
        'completed' => 'badge-success',
        'pending' => 'badge-secondary',
        'cancelled' => 'badge-danger'
    ];
    $class = $map[$status] ?? 'badge-secondary';
    return '<span class="badge ' . $class . '">' . ucfirst(str_replace('_', ' ', $status)) . '</span>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assigned Jobs - <?php echo htmlspecialchars($employee['shop_name']); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .job-card {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
        }
        .job-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 10px;
            margin-top: 15px;
        }
        .job-meta div {
            font-size: 0.9rem;
            color: #6c757d;
        }
        .progress-bar-container {
            width: 120px;
            background: #e9ecef;
            border-radius: 5px;
            overflow: hidden;
            height: 8px;
            display: inline-block;
            vertical-align: middle;
            margin-right: 8px;
        }
        .progress-fill {
            height: 100%;
            background: #4361ee;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container d-flex justify-between align-center">
            <a href="dashboard.php" class="navbar-brand">
                <i class="fas fa-user-tie"></i> Employee Dashboard
            </a>
            <ul class="navbar-nav">
                <li><a href="dashboard.php" class="nav-link">Dashboard</a></li>
                <?php if(!empty($permissions['view_jobs'])): ?>
                    <li><a href="assigned_jobs.php" class="nav-link active">My Jobs</a></li>
                <?php endif; ?>
                <?php if(!empty($permissions['update_status'])): ?>
                    <li><a href="update_status.php" class="nav-link">Update Status</a></li>
                <?php endif; ?>
                <?php if(!empty($permissions['upload_photos'])): ?>
                    <li><a href="upload_photos.php" class="nav-link">Upload Photos</a></li>
                <?php endif; ?>
                <li><a href="schedule.php" class="nav-link">Schedule</a></li>
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
        <div class="dashboard-header">
            <h2>Assigned Jobs</h2>
            <p class="text-muted">Track all jobs assigned to you and their current progress.</p>
        </div>

        <?php if(!empty($assigned_jobs)): ?>
            <?php foreach($assigned_jobs as $job): ?>
                <div class="job-card">
                    <div class="d-flex justify-between align-center">
                        <div>
                            <h4 class="mb-1"><?php echo htmlspecialchars($job['service_type']); ?></h4>
                            <p class="mb-1 text-muted">
                                <i class="fas fa-user"></i> <?php echo htmlspecialchars($job['client_name']); ?>
                                <span class="ml-2">Order #<?php echo htmlspecialchars($job['order_number']); ?></span>
                            </p>
                        </div>
                        <div class="text-right">
                            <?php echo job_status_badge($job['status']); ?>
                            <div class="mt-2">
                                <a href="update_status.php?order_id=<?php echo $job['id']; ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-edit"></i> Update
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3">
                        <div class="d-flex align-center">
                            <div class="progress-bar-container">
                                <div class="progress-fill" style="width: <?php echo $job['progress']; ?>%;"></div>
                            </div>
                            <strong><?php echo $job['progress']; ?>%</strong>
                        </div>
                    </div>

                    <div class="job-meta">
                        <div><i class="fas fa-store"></i> <?php echo htmlspecialchars($job['shop_name']); ?></div>
                        <?php if(!empty($job['scheduled_date'])): ?>
                            <div><i class="fas fa-calendar"></i> Due: <?php echo date('M d, Y', strtotime($job['scheduled_date'])); ?></div>
                        <?php endif; ?>
                        <?php if(!empty($job['design_description'])): ?>
                            <div><i class="fas fa-clipboard"></i> <?php echo htmlspecialchars($job['design_description']); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="card">
                <div class="text-center p-4">
                    <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                    <h4>No Assigned Jobs</h4>
                    <p class="text-muted">You don't have any assigned jobs at the moment.</p>
                    <a href="dashboard.php" class="btn btn-primary">Back to Dashboard</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
