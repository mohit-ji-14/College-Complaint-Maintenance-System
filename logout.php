<?php
// logout.php - Destroy session & logout user

require_once __DIR__ . '/config/db.php';

session_unset();
session_destroy();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

setFlashMsg('info', 'You have been logged out successfully.');
redirect('index.php');
