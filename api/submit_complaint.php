<?php
// api/submit_complaint.php - API Handler for New Complaint Submissions

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../includes/functions.php';

checkAuth(['student', 'admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentUser = getCurrentUser();
    $student_id = $currentUser['id'];

    $category_id = intval($_POST['category_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $priority = $_POST['priority'] ?? 'Medium';
    $is_anonymous = isset($_POST['is_anonymous']) ? 1 : 0; // Anonymous toggle!

    if ($category_id <= 0 || empty($title) || empty($description) || empty($location)) {
        setFlashMsg('danger', 'Please fill out all required fields (Category, Title, Description, Location).');
        redirect('../student_dashboard.php');
    }

    // Handle Image Upload if provided
    $image_path = null;
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['attachment']['tmp_name'];
        $fileName = $_FILES['attachment']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (in_array($fileExtension, $allowedExtensions)) {
            $uploadDir = __DIR__ . '/../assets/uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $newFileName = 'complaint_' . time() . '_' . rand(100, 999) . '.' . $fileExtension;
            $destPath = $uploadDir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $destPath)) {
                $image_path = 'assets/uploads/' . $newFileName;
            }
        }
    }

    // Generate unique complaint code
    $pdo = getDBConnection();
    $complaint_code = generateComplaintCode();

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT INTO complaints 
            (complaint_code, student_id, category_id, title, description, location, priority, status, is_anonymous, image_path, created_at) 
            VALUES 
            (:code, :student_id, :category_id, :title, :description, :location, :priority, 'Pending', :is_anonymous, :image_path, NOW())");

        $stmt->execute([
            'code' => $complaint_code,
            'student_id' => $student_id,
            'category_id' => $category_id,
            'title' => $title,
            'description' => $description,
            'location' => $location,
            'priority' => $priority,
            'is_anonymous' => $is_anonymous,
            'image_path' => $image_path
        ]);

        $complaint_id = $pdo->lastInsertId();

        // Add initial activity log
        $updateStmt = $pdo->prepare("INSERT INTO complaint_updates (complaint_id, user_id, status_to, comment) VALUES (:cid, :uid, 'Pending', :comment)");
        $updateStmt->execute([
            'cid' => $complaint_id,
            'uid' => $student_id,
            'comment' => $is_anonymous ? 'Complaint submitted anonymously by student.' : 'Complaint submitted by student.'
        ]);

        $pdo->commit();

        setFlashMsg('success', "Complaint $complaint_code submitted successfully! Our maintenance team will inspect it shortly.");
        redirect('../student_dashboard.php');

    } catch (Exception $e) {
        $pdo->rollBack();
        setFlashMsg('danger', 'Error submitting complaint: ' . $e->getMessage());
        redirect('../student_dashboard.php');
    }
} else {
    redirect('../student_dashboard.php');
}
