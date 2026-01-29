<?php
session_start();
require_once '../config/db.php';
require_role('owner');

$owner_id = $_SESSION['user']['id'];

// Get shop details
$shop_stmt = $pdo->prepare("SELECT * FROM shops WHERE owner_id = ?");
$shop_stmt->execute([$owner_id]);
$shop = $shop_stmt->fetch();

if(!$shop) {
    header("Location: create_shop.php");
    exit();
}

$shop_id = $shop['id'];

// Add new employee
if(isset($_POST['add_employee'])) {
    $email = sanitize($_POST['email']);
    $position = sanitize($_POST['position']);
    $permissions = [
        'view_jobs' => !empty($_POST['perm_view_jobs']),
        'update_status' => !empty($_POST['perm_update_status']),
        'upload_photos' => !empty($_POST['perm_upload_photos'])
    ];
    $permissions_json = json_encode($permissions);    
    try {
        // Check if user exists
        $user_stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $user_stmt->execute([$email]);
        $user = $user_stmt->fetch();
        
        if($user) {
            // Check if already an employee
            $check_stmt = $pdo->prepare("SELECT id FROM shop_employees WHERE user_id = ?");
            $check_stmt->execute([$user['id']]);
            
            if($check_stmt->rowCount() == 0) {
                // Add as employee
                $add_stmt = $pdo->prepare("
                    INSERT INTO shop_employees (shop_id, user_id, position, permissions, hired_date) 
                    VALUES (?, ?, ?, ?, CURDATE())
                ");
                $add_stmt->execute([$shop_id, $user['id'], $position, $permissions_json]);
                
                // Update user role to employee
                $update_stmt = $pdo->prepare("UPDATE users SET role = 'employee' WHERE id = ?");
                $update_stmt->execute([$user['id']]);
                
                $success = "Employee added successfully!";
            } else {
                $error = "User is already an employee!";
            }
        } else {
            $error = "User with this email not found!";
        }
    } catch(PDOException $e) {
        $error = "Failed to add employee: " . $e->getMessage();
    }
}

// Remove employee
if(isset($_GET['remove'])) {
    $emp_id = $_GET['remove'];
    $remove_stmt = $pdo->prepare("DELETE FROM shop_employees WHERE id = ? AND shop_id = ?");
    $remove_stmt->execute([$emp_id, $shop_id]);
    $success = "Employee removed successfully!";
}

// Get all employees
$employees_stmt = $pdo->prepare("
    SELECT se.*, u.fullname, u.email, u.phone, u.created_at as joined_date
    FROM shop_employees se 
    JOIN users u ON se.user_id = u.id 
    WHERE se.shop_id = ? 
    ORDER BY se.created_at DESC
");
$employees_stmt->execute([$shop_id]);
$employees = $employees_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Staff - <?php echo htmlspecialchars($shop['shop_name']); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <nav class="navbar">
        <div class="container d-flex justify-between align-center">
            <a href="dashboard.php" class="navbar-brand">
                <i class="fas fa-store"></i> <?php echo htmlspecialchars($shop['shop_name']); ?>
            </a>
            <ul class="navbar-nav">
                <li><a href="dashboard.php" class="nav-link">Dashboard</a></li>
                <li><a href="manage_staff.php" class="nav-link active">Staff</a></li>
                <li><a href="shop_orders.php" class="nav-link">Orders</a></li>
                <li><a href="../auth/logout.php" class="nav-link">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="dashboard-header">
            <h2>Manage Staff</h2>
            <p class="text-muted">Add and manage employees for your shop</p>
            <button class="btn btn-primary" onclick="document.getElementById('addEmployeeModal').style.display='block'">
                <i class="fas fa-user-plus"></i> Add New Employee
            </button>
        </div>

        <?php if(isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if(isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <!-- Employees Table -->
        <div class="card">
            <h3>Current Employees (<?php echo count($employees); ?>)</h3>
            <?php if(!empty($employees)): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Position</th>
                            <th>Contact</th>
                            <th>Joined</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($employees as $emp): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($emp['fullname']); ?></strong><br>
                                <small class="text-muted">ID: <?php echo $emp['id']; ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($emp['position']); ?></td>
                            <td>
                                <?php echo htmlspecialchars($emp['email']); ?><br>
                                <small><?php echo $emp['phone']; ?></small>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($emp['joined_date'])); ?></td>
                            <td>
                                <span class="badge badge-success">Active</span>
                            </td>
                            <td>
                                <a href="edit_employee.php?id=<?php echo $emp['id']; ?>" 
                                   class="btn btn-sm btn-outline-primary">Edit</a>
                                <a href="manage_staff.php?remove=<?php echo $emp['id']; ?>" 
                                   class="btn btn-sm btn-outline-danger"
                                   onclick="return confirm('Are you sure you want to remove this employee?')">Remove</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="text-center p-4">
                    <i class="fas fa-users fa-3x text-muted mb-3"></i>
                    <h4>No Employees Yet</h4>
                    <p class="text-muted">Add your first employee to get started.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Employee Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-number"><?php echo count($employees); ?></div>
                <div class="stat-label">Total Employees</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-user-check"></i>
                </div>
                <div class="stat-number">
                    <?php 
                    $active = $pdo->prepare("SELECT COUNT(*) as count FROM shop_employees WHERE shop_id = ? AND status = 'active'");
                    $active->execute([$shop_id]);
                    echo $active->fetch()['count'];
                    ?>
                </div>
                <div class="stat-label">Active</div>
            </div>
        </div>
    </div>

    <!-- Add Employee Modal -->
    <div id="addEmployeeModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5);">
        <div class="modal-content" style="background: white; margin: 10% auto; padding: 30px; width: 500px; border-radius: 10px;">
            <div class="modal-header d-flex justify-between align-center mb-3">
                <h3>Add New Employee</h3>
                <button onclick="document.getElementById('addEmployeeModal').style.display='none'" 
                        style="background: none; border: none; font-size: 1.5rem;">&times;</button>
            </div>
            <form method="POST">
                <div class="form-group">
                    <label>Employee Email *</label>
                    <input type="email" name="email" class="form-control" required 
                           placeholder="Enter employee's registered email">
                    <small class="text-muted">Employee must be registered on the platform</small>
                </div>
                
                <div class="form-group">
                    <label>Position *</label>
                    <select name="position" class="form-control" required>
                        <option value="">Select position</option>
                        <option value="Designer">Designer</option>
                        <option value="Embroidery Technician">Embroidery Technician</option>
                        <option value="Quality Control">Quality Control</option>
                        <option value="Production Manager">Production Manager</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Permissions</label>
                    <p class="text-muted mb-2">Uncheck all job permissions to give schedule-only access.</p>
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="perm_view_jobs" name="perm_view_jobs" checked>
                        <label class="form-check-label" for="perm_view_jobs">View assigned jobs</label>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="perm_update_status" name="perm_update_status" checked>
                        <label class="form-check-label" for="perm_update_status">Update job status</label>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="perm_upload_photos" name="perm_upload_photos" checked>
                        <label class="form-check-label" for="perm_upload_photos">Upload output photos</label>
                    </div>
                </div>
                
                <div class="modal-footer mt-4">
                    <button type="button" class="btn btn-secondary" 
                            onclick="document.getElementById('addEmployeeModal').style.display='none'">Cancel</button>
                    <button type="submit" name="add_employee" class="btn btn-primary">Add Employee</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Close modal when clicking outside
        window.onclick = function(event) {
            var modal = document.getElementById('addEmployeeModal');
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>
</body>
</html>