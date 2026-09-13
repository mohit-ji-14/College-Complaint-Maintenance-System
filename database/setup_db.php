<?php
// Automated Database Setup Script for College Complaint & Maintenance System

$host = '127.0.0.1';
$user = 'root';
$pass = '';




try {
    // Step 1: Connect to MySQL server without database
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "<div class='alert alert-info d-flex align-items-center mb-2'><i class='bi bi-check-circle-fill me-2 fs-5 text-info'></i> Connected to MySQL Server at $host successfully.</div>";

    // Step 2: Read schema.sql file
    $sqlFile = __DIR__ . '/schema.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("schema.sql file not found in database directory.");
    }
    
    $sql = file_get_contents($sqlFile);

    // Execute multi-query
    $queries = explode(";\n", $sql);
    foreach ($queries as $query) {
        $trimmed = trim($query);
        if (!empty($trimmed)) {
            $pdo->exec($trimmed);
        }
    }


} catch (Exception $e) {
    echo "<div class='alert alert-danger d-flex align-items-center'><i class='bi bi-exclamation-triangle-fill me-2 fs-5'></i> Setup Failed: " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "</div><div class='text-center mt-3'><a href='setup_db.php' class='btn btn-outline-light btn-sm'>Retry Setup</a></div>";
}

echo "</div></div></body></html>";
