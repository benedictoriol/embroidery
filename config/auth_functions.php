<?php
// config/auth_functions.php

/**
 * Check if user has required role
 * @param string $required_role Role required to access the page
 */
function require_role($required_role) {
    if (!isset($_SESSION['user'])) {
        header("Location: ../auth/login.php");
        exit();
    }
    
    if ($_SESSION['user']['role'] != $required_role) {
        // Redirect to appropriate dashboard based on user's actual role
        $role = $_SESSION['user']['role'];
        switch($role) {
            case 'sys_admin':
                header("Location: ../sys_admin/dashboard.php");
                break;
            case 'owner':
                header("Location: ../owner/dashboard.php");
                break;
            case 'employee':
                header("Location: ../employee/dashboard.php");
                break;
            case 'client':
                header("Location: ../client/dashboard.php");
                break;
            default:
                header("Location: ../index.php");
        }
        exit();
    }
}
?>