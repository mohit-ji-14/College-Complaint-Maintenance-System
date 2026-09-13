<?php
// config/db.php - Database Connection & Core Utility Helper Functions

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'college_complaint_db');

function getDBConnection() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            die("Database Connection Error: Please ensure MySQL is running in XAMPP and run <a href='database/setup_db.php'>database setup</a>. Details: " . $e->getMessage());
        }
    }
    return $pdo;
}

// Redirect helper
function redirect($path) {
    header("Location: $path");
    exit();
}

// Flash Message Helper
function setFlashMsg($type, $message) {
    $_SESSION['flash_msg'] = [
        'type' => $type, // success, danger, warning, info
        'msg' => $message
    ];
}

function displayFlashMsg() {
    if (isset($_SESSION['flash_msg'])) {
        $type = $_SESSION['flash_msg']['type'];
        $msg = $_SESSION['flash_msg']['msg'];
        unset($_SESSION['flash_msg']);
        echo "
        <div class='alert alert-{$type} alert-dismissible fade show shadow-sm border-0 mb-4' role='alert'>
            <i class='bi bi-" . ($type === 'success' ? 'check-circle-fill' : ($type === 'danger' ? 'exclamation-octagon-fill' : 'info-circle-fill')) . " me-2'></i>
            " . htmlspecialchars($msg) . "
            <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
        </div>";
    }
}

// Current Logged-in User Helpers
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getCurrentUser() {
    if (!isLoggedIn()) return null;
    return [
        'id' => $_SESSION['user_id'],
        'name' => $_SESSION['user_name'],
        'email' => $_SESSION['user_email'],
        'role' => $_SESSION['user_role'],
        'department' => $_SESSION['user_department'] ?? ''
    ];
}
