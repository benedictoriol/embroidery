<?php
session_start();
require_once 'config/db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Embroidery Platform - Professional Embroidery Services</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 100px 0;
            text-align: center;
        }
        .feature-card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
            height: 100%;
            transition: transform 0.3s;
        }
        .feature-card:hover {
            transform: translateY(-10px);
        }
        .role-section {
            background: #f8f9ff;
            padding: 80px 0;
        }
        .role-card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            text-align: center;
            height: 100%;
            border: 2px solid #e0e0e0;
            transition: all 0.3s;
        }
        .role-card:hover {
            border-color: #4361ee;
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.1);
        }
        .stats-section {
            background: linear-gradient(135deg, #00b09b 0%, #96c93d 100%);
            color: white;
            padding: 60px 0;
        }
        .cta-section {
            background: #f8f9fa;
            padding: 80px 0;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container d-flex justify-between align-center">
            <a href="index.php" class="navbar-brand">
                <i class="fas fa-threads"></i> Embroidery Platform
            </a>
            <ul class="navbar-nav">
                <li><a href="index.php" class="nav-link active">Home</a></li>
                <li><a href="#features">Features</a></li>
                <li><a href="#roles">Roles</a></li>
                <li><a href="#about">About</a></li>
                <?php if(isset($_SESSION['user'])): ?>
                    <?php 
                    $dashboard_url = '';
                    switch($_SESSION['user']['role']) {
                        case 'sys_admin': $dashboard_url = 'sys_admin/dashboard.php'; break;
                        case 'owner': $dashboard_url = 'owner/dashboard.php'; break;
                        case 'employee': $dashboard_url = 'employee/dashboard.php'; break;
                        case 'client': $dashboard_url = 'client/dashboard.php'; break;
                    }
                    ?>
                    <li><a href="<?php echo $dashboard_url; ?>" class="nav-link">Dashboard</a></li>
                    <li><a href="auth/logout.php" class="nav-link">Logout</a></li>
                <?php else: ?>
                    <li><a href="auth/login.php" class="nav-link">Login</a></li>
                    <li><a href="auth/register.php?type=client" class="btn btn-primary">Get Started</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <h1 style="font-size: 3.5rem; margin-bottom: 20px;">Professional Embroidery Services Platform</h1>
            <p style="font-size: 1.2rem; max-width: 700px; margin: 0 auto 40px;">
                Connect with the best embroidery service providers. From custom designs to bulk orders, 
                we provide a complete solution for all your embroidery needs.
            </p>
            <div style="display: flex; gap: 15px; justify-content: center;">
                <?php if(!isset($_SESSION['user'])): ?>
                    <a href="auth/register.php?type=client" class="btn btn-light btn-lg">
                        <i class="fas fa-shopping-cart"></i> Order Services
                    </a>
                    <a href="auth/register.php?type=owner" class="btn btn-outline-light btn-lg">
                        <i class="fas fa-store"></i> Register as Provider
                    </a>
                <?php else: ?>
                    <a href="<?php echo $dashboard_url; ?>" class="btn btn-light btn-lg">
                        <i class="fas fa-tachometer-alt"></i> Go to Dashboard
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" style="padding: 80px 0;">
        <div class="container">
            <h2 class="text-center mb-5">Why Choose Our Platform?</h2>
            <div class="row" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px;">
                <div class="feature-card">
                    <div class="feature-icon" style="font-size: 3rem; color: #4361ee; margin-bottom: 20px;">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <h3>Quick Order Processing</h3>
                    <p>Get your embroidery orders processed quickly with our efficient workflow system.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon" style="font-size: 3rem; color: #4361ee; margin-bottom: 20px;">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3>Secure Platform</h3>
                    <p>Your data and transactions are protected with enterprise-grade security.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon" style="font-size: 3rem; color: #4361ee; margin-bottom: 20px;">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3>Real-time Tracking</h3>
                    <p>Track your orders in real-time from design to delivery.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Roles Section -->
    <section id="roles" class="role-section">
        <div class="container">
            <h2 class="text-center mb-5">Platform Roles</h2>
            <div class="row" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 25px;">
                <div class="role-card">
                    <div class="role-icon" style="font-size: 3rem; color: #4361ee; margin-bottom: 20px;">
                        <i class="fas fa-user"></i>
                    </div>
                    <h3>Client</h3>
                    <p>Place orders, customize designs, track progress, and rate providers.</p>
                    <a href="auth/register.php?type=client" class="btn btn-outline-primary mt-3">Register as Client</a>
                </div>
                
                <div class="role-card">
                    <div class="role-icon" style="font-size: 3rem; color: #4361ee; margin-bottom: 20px;">
                        <i class="fas fa-store"></i>
                    </div>
                    <h3>Shop Owner</h3>
                    <p>Manage your shop, staff, orders, and view earnings & performance.</p>
                    <a href="auth/register.php?type=owner" class="btn btn-outline-primary mt-3">Register as Owner</a>
                </div>
                
                <div class="role-card">
                    <div class="role-icon" style="font-size: 3rem; color: #4361ee; margin-bottom: 20px;">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <h3>Employee</h3>
                    <p>View assigned jobs, update status, upload photos, and view schedule.</p>
                    <a href="auth/login.php" class="btn btn-outline-primary mt-3">Employee Login</a>
                </div>
                
                <div class="role-card">
                    <div class="role-icon" style="font-size: 3rem; color: #4361ee; margin-bottom: 20px;">
                        <i class="fas fa-cogs"></i>
                    </div>
                    <h3>System Admin</h3>
                    <p>Full system control, DSS configuration, approvals, and analytics.</p>
                    <a href="auth/login.php" class="btn btn-outline-primary mt-3">Admin Login</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <?php
    try {
        $total_shops = $pdo->query("SELECT COUNT(*) as count FROM shops WHERE status = 'active'")->fetch()['count'];
        $total_orders = $pdo->query("SELECT COUNT(*) as count FROM orders")->fetch()['count'];
        $total_users = $pdo->query("SELECT COUNT(*) as count FROM users")->fetch()['count'];
        $completed_orders = $pdo->query("SELECT COUNT(*) as count FROM orders WHERE status = 'completed'")->fetch()['count'];
    } catch(Exception $e) {
        $total_shops = $total_orders = $total_users = $completed_orders = 0;
    }
    ?>
    <section class="stats-section">
        <div class="container">
            <div class="row" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 30px; text-align: center;">
                <div>
                    <h1 style="font-size: 3rem; margin-bottom: 10px;"><?php echo $total_shops; ?>+</h1>
                    <p style="opacity: 0.9;">Active Service Providers</p>
                </div>
                <div>
                    <h1 style="font-size: 3rem; margin-bottom: 10px;"><?php echo $total_orders; ?>+</h1>
                    <p style="opacity: 0.9;">Orders Processed</p>
                </div>
                <div>
                    <h1 style="font-size: 3rem; margin-bottom: 10px;"><?php echo $total_users; ?>+</h1>
                    <p style="opacity: 0.9;">Happy Users</p>
                </div>
                <div>
                    <h1 style="font-size: 3rem; margin-bottom: 10px;"><?php echo $completed_orders; ?>+</h1>
                    <p style="opacity: 0.9;">Completed Projects</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container text-center">
            <h2 style="margin-bottom: 20px;">Ready to Get Started?</h2>
            <p style="font-size: 1.1rem; max-width: 600px; margin: 0 auto 40px;">
                Join thousands of satisfied customers and service providers on our platform.
                Whether you need embroidery services or want to offer them, we have the perfect solution for you.
            </p>
            <div style="display: flex; gap: 15px; justify-content: center;">
                <?php if(!isset($_SESSION['user'])): ?>
                    <a href="auth/register.php?type=client" class="btn btn-primary btn-lg">
                        <i class="fas fa-user-plus"></i> Sign Up as Client
                    </a>
                    <a href="auth/register.php?type=owner" class="btn btn-outline-primary btn-lg">
                        <i class="fas fa-store"></i> Register Your Shop
                    </a>
                <?php else: ?>
                    <a href="<?php echo $dashboard_url; ?>" class="btn btn-primary btn-lg">
                        <i class="fas fa-rocket"></i> Launch Dashboard
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer" style="background: #212529; color: white; padding: 50px 0 20px;">
        <div class="container">
            <div class="row" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 40px; margin-bottom: 40px;">
                <div>
                    <h4 style="color: white; margin-bottom: 20px;">
                        <i class="fas fa-threads"></i> Embroidery Platform
                    </h4>
                    <p>Professional platform connecting embroidery service providers with customers worldwide.</p>
                </div>
                
                <div>
                    <h4 style="color: white; margin-bottom: 20px;">Quick Links</h4>
                    <ul style="list-style: none; padding: 0;">
                        <li><a href="index.php" style="color: #adb5bd;">Home</a></li>
                        <li><a href="#features" style="color: #adb5bd;">Features</a></li>
                        <li><a href="#roles" style="color: #adb5bd;">Roles</a></li>
                        <li><a href="auth/login.php" style="color: #adb5bd;">Login</a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 style="color: white; margin-bottom: 20px;">Contact Us</h4>
                    <p><i class="fas fa-envelope"></i> support@embroidery.com</p>
                    <p><i class="fas fa-phone"></i> +1 (555) 123-4567</p>
                </div>
            </div>
            
            <hr style="border-color: #495057; margin: 30px 0;">
            
            <div class="text-center">
                <p>&copy; 2024 Embroidery Platform. All rights reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html>