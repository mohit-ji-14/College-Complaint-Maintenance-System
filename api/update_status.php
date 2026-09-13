<?php
// api/update_status.php - Technician / Admin Work Status & Proof Image Upload API

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

checkAuth(['staff', 'admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentUser = getCurrentUser();
    $complaint_id = intval($_POST['complaint_id'] ?? 0);
    $new_status = $_POST['status'] ?? '';
    $comment = trim($_POST['comment'] ?? '');

    if ($complaint_id <= 0 || empty($new_status)) {
        setFlashMsg('danger', 'Invalid complaint update parameters.');
        redirect('../staff_dashboard.php');
    }

    $pdo = getDBConnection();

    // Fetch existing complaint
    $stmt = $pdo->prepare("SELECT * FROM complaints WHERE id = :id LIMIT 1");
    $stmt->execute(['id' => $complaint_id]);
    $complaint = $stmt->fetch();

    if (!$complaint) {
        setFlashMsg('danger', 'Complaint ticket not found.');
        redirect('../staff_dashboard.php');
    }

    $old_status = $complaint['status'];

    // Handle Resolution Proof Image Upload if status is Resolved
    $resolution_image = $complaint['resolution_image'];
    if (isset($_FILES['resolution_proof']) && $_FILES['resolution_proof']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['resolution_proof']['tmp_name'];
        $fileName = $_FILES['resolution_proof']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (in_array($fileExtension, $allowedExtensions)) {
            $uploadDir = __DIR__ . '/../assets/uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $newFileName = 'proof_' . time() . '_' . rand(100, 999) . '.' . $fileExtension;
            $destPath = $uploadDir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $destPath)) {
                $resolution_image = 'assets/uploads/' . $newFileName;
            }
        }
    }

    try {
        $pdo->beginTransaction();

        // Update complaints table
        $updateQuery = "UPDATE complaints SET status = :status, updated_at = NOW()";
        $params = ['status' => $new_status, 'id' => $complaint_id];

        if ($resolution_image !== $complaint['resolution_image']) {
            $updateQuery .= ", resolution_image = :res_img";
            $params['res_img'] = $resolution_image;
        }

        if (!empty($comment)) {
            $updateQuery .= ", resolution_notes = :res_notes";
            $params['res_notes'] = $comment;
        }

        // Auto-assign staff if unassigned and changing status
        if (empty($complaint['assigned_staff_id']) && $currentUser['role'] === 'staff') {
            $updateQuery .= ", assigned_staff_id = :staff_id";
            $params['staff_id'] = $currentUser['id'];
        }

        $updateQuery .= " WHERE id = :id";

        $updateStmt = $pdo->prepare($updateQuery);
        $updateStmt->execute($params);

        // Insert update activity history
        $logStmt = $pdo->prepare("INSERT INTO complaint_updates (complaint_id, user_id, status_from, status_to, comment) VALUES (:cid, :uid, :sfrom, :sto, :comment)");
        $logStmt->execute([
            'cid' => $complaint_id,
            'uid' => $currentUser['id'],
            'sfrom' => $old_status,
            'sto' => $new_status,
            'comment' => $comment ?: "Status updated to $new_status by staff."
        ]);

        $pdo->commit();

        setFlashMsg('success', "Ticket #" . $complaint['complaint_code'] . " status updated to $new_status.");
        
        if ($currentUser['role'] === 'admin') {
            redirect('../admin_dashboard.php');
        } else {
            redirect('../staff_dashboard.php');
        }

    } catch (Exception $e) {
        $pdo->rollBack();
        setFlashMsg('danger', 'Failed to update ticket: ' . $e->getMessage());
        redirect('../staff_dashboard.php');
    }
} else {
    redirect('../staff_dashboard.php');
}
