<?php
// api/submit_feedback.php - Rating & Feedback Handler

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';

checkAuth(['student']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentUser = getCurrentUser();
    $complaint_id = intval($_POST['complaint_id'] ?? 0);
    $rating = intval($_POST['rating'] ?? 5);
    $comments = trim($_POST['comments'] ?? '');

    if ($complaint_id <= 0 || $rating < 1 || $rating > 5) {
        setFlashMsg('danger', 'Invalid rating submission.');
        redirect('../student_dashboard.php');
    }

    $pdo = getDBConnection();

    // Verify complaint belongs to student and is resolved
    $stmt = $pdo->prepare("SELECT id FROM complaints WHERE id = :cid AND student_id = :sid AND status = 'Resolved'");
    $stmt->execute(['cid' => $complaint_id, 'sid' => $currentUser['id']]);
    
    if (!$stmt->fetch()) {
        setFlashMsg('danger', 'You can only rate tickets that are completed/resolved.');
        redirect("../complaint_detail.php?id=$complaint_id");
    }

    try {
        $insert = $pdo->prepare("INSERT INTO feedbacks (complaint_id, rating, comments, created_at) VALUES (:cid, :rating, :comments, NOW()) ON DUPLICATE KEY UPDATE rating = :rating, comments = :comments");
        $insert->execute([
            'cid' => $complaint_id,
            'rating' => $rating,
            'comments' => $comments
        ]);

        setFlashMsg('success', 'Thank you for your rating & feedback!');
        redirect("../complaint_detail.php?id=$complaint_id");

    } catch (Exception $e) {
        setFlashMsg('danger', 'Error saving feedback: ' . $e->getMessage());
        redirect("../complaint_detail.php?id=$complaint_id");
    }
} else {
    redirect('../student_dashboard.php');
}
