<?php
// includes/header.php - Global Navbar & Top Navigation Header

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/functions.php';

$currentUser = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>College Complaint & Maintenance System</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom Design CSS -->
    <link href="assets/css/custom.css" rel="stylesheet">
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-custom sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="index.php">
            <span class="bg-primary text-white rounded-3 p-2 me-2 d-inline-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                <i class="bi bi-tools fs-5"></i>
            </span>
            <span>EduCare Maintenance</span>
        </a>

        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent" aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarContent">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-4">
                <?php if ($currentUser): ?>
                    <?php if ($currentUser['role'] === 'student'): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'student_dashboard.php') ? 'active' : '' ?>" href="student_dashboard.php">
                                <i class="bi bi-speedometer2 me-1"></i> Dashboard
                            </a>
                        </li>
                    <?php elseif ($currentUser['role'] === 'staff'): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'staff_dashboard.php') ? 'active' : '' ?>" href="staff_dashboard.php">
                                <i class="bi bi-list-task me-1"></i> Assigned Work Orders
                            </a>
                        </li>
                    <?php elseif ($currentUser['role'] === 'admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'admin_dashboard.php') ? 'active' : '' ?>" href="admin_dashboard.php">
                                <i class="bi bi-bar-chart-line-fill me-1"></i> Executive Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'manage_users.php') ? 'active' : '' ?>" href="manage_users.php">
                                <i class="bi bi-people-fill me-1"></i> Users
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) == 'manage_categories.php') ? 'active' : '' ?>" href="manage_categories.php">
                                <i class="bi bi-tags-fill me-1"></i> Categories
                            </a>
                        </li>
                    <?php endif; ?>
                <?php endif; ?>
            </ul>

            <div class="d-flex align-items-center">
                <?php if ($currentUser): ?>
                    <div class="dropdown">
                        <button class="btn btn-light border dropdown-toggle d-flex align-items-center px-3 py-2 rounded-pill" type="button" id="userMenu" data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="bg-primary text-white rounded-circle me-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; font-size: 0.85rem;">
                                <?= strtoupper(substr($currentUser['name'], 0, 1)) ?>
                            </div>
                            <div class="text-start me-2">
                                <div class="fw-semibold small text-dark lh-1"><?= htmlspecialchars($currentUser['name']) ?></div>
                                <span class="badge bg-secondary-subtle text-secondary border fs-8 mt-1 text-uppercase"><?= htmlspecialchars($currentUser['role']) ?></span>
                            </div>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 mt-2" aria-labelledby="userMenu">
                            <li><h6 class="dropdown-header">Logged in as <?= htmlspecialchars($currentUser['email']) ?></h6></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger d-flex align-items-center" href="logout.php">
                                    <i class="bi bi-box-arrow-right me-2"></i> Sign Out
                                </a>
                            </li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="index.php" class="btn btn-outline-primary me-2 rounded-pill"><i class="bi bi-box-arrow-in-right me-1"></i> Log In</a>
                    <a href="register.php" class="btn btn-primary rounded-pill"><i class="bi bi-person-plus me-1"></i> Student Register</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<!-- Main Container Wrap -->
<main class="py-4">
    <div class="container">
        <?php displayFlashMsg(); ?>
