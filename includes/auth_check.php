<?php
// includes/auth_check.php - Role-Based Authorization Middleware

require_once __DIR__ . '/../config/db.php';

function checkAuth($allowedRoles = []) {
    if (!isLoggedIn()) {
        setFlashMsg('danger', 'Please log in to access the maintenance portal.');
        redirect('index.php');
    }

    if (!empty($allowedRoles)) {
        $currentUser = getCurrentUser();
        if (!in_array($currentUser['role'], $allowedRoles)) {
            setFlashMsg('warning', 'Access Denied: You do not have permission to access that section.');
            
            // Redirect based on actual role
            if ($currentUser['role'] === 'student') redirect('student_dashboard.php');
            if ($currentUser['role'] === 'staff') redirect('staff_dashboard.php');
            if ($currentUser['role'] === 'admin') redirect('admin_dashboard.php');
            redirect('index.php');
        }
    }
}
