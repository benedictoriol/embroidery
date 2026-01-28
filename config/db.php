<?php
// Database Configuration
$host = "localhost";
$dbname = "embroidery_platform";
$username = "root";
$password = "";

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Function to check if user is logged in
function is_logged_in() {
    return isset($_SESSION['user']);
}

// Function to check user role
function check_role($required_role) {
    if(!isset($_SESSION['user']) || $_SESSION['user']['role'] != $required_role) {
        return false;
    }
    return true;
}

// Redirect if not logged in or wrong role
function require_role($role) {
    if(!check_role($role)) {
        header("Location: ../auth/login.php");
        exit();
    }
}

// Sanitize input
function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)));
}
?>