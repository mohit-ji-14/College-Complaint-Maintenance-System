<?php
// api/assign_technician.php - Admin Technician Assignment API

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

checkAuth(['admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentUser = getCurrentUser();
    $complaint_id = intval($_POST['complaint_id'] ?? 0);
    $staff_id = intval($_POST['staff_id'] ?? 0);

    if ($complaint_id <= 0 || $staff_id <= 0) {
        setFlashMsg('danger', 'Please select a valid technician.');
        redirect('../admin_dashboard.php');
    }

    $pdo = getDBConnection();

    // Check staff exists
    $staffStmt = $pdo->prepare("SELECT name FROM users WHERE id = :id AND role = 'staff' LIMIT 1");
    $staffStmt->execute(['id' => $staff_id]);
    $staff = $staffStmt->fetch();

    if (!$staff) {
        setFlashMsg('danger', 'Selected technician does not exist.');
        redirect('../admin_dashboard.php');
    }

    // Get current complaint status
    $compStmt = $pdo->prepare("SELECT complaint_code, status FROM complaints WHERE id = :id LIMIT 1");
    $compStmt->execute(['id' => $complaint_id]);
    $complaint = $compStmt->fetch();

    if (!$complaint) {
        setFlashMsg('danger', 'Complaint ticket not found.');
        redirect('../admin_dashboard.php');
    }

    $new_status = ($complaint['status'] === 'Pending') ? 'Assigned' : $complaint['status'];

    try {
        $pdo->beginTransaction();

        $update = $pdo->prepare("UPDATE complaints SET assigned_staff_id = :sid, status = :status, updated_at = NOW() WHERE id = :cid");
        $update->execute([
            'sid' => $staff_id,
            'status' => $new_status,
            'cid' => $complaint_id
        ]);

        $log = $pdo->prepare("INSERT INTO complaint_updates (complaint_id, user_id, status_from, status_to, comment) VALUES (:cid, :uid, :sfrom, :sto, :comment)");
        $log->execute([
            'cid' => $complaint_id,
            'uid' => $currentUser['id'],
            'sfrom' => $complaint['status'],
            'sto' => $new_status,
            'comment' => "Assigned ticket to technician " . $staff['name'] . "."
        ]);

        $pdo->commit();

        setFlashMsg('success', "Assigned ticket #" . $complaint['complaint_code'] . " to " . htmlspecialchars($staff['name']) . ".");
        redirect('../admin_dashboard.php');

    } catch (Exception $e) {
        $pdo->rollBack();
        setFlashMsg('danger', 'Error assigning technician: ' . $e->getMessage());
        redirect('../admin_dashboard.php');
    }
} else {
    redirect('../admin_dashboard.php');
}
