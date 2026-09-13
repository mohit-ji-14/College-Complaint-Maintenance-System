<?php
// includes/functions.php - Business Logic & Helper Functions

require_once __DIR__ . '/../config/db.php';

/**
 * Anonymity Logic: Hides the student identity if complaint is marked anonymous
 * OR if the current viewing role is Maintenance Staff / Technician.
 */
function getStudentDisplayName($studentName, $isAnonymous, $userRole, $studentId = 0, $currentUserId = 0) {
    // If student requested anonymity
    if ($isAnonymous) {
        // Only Admin can see identity if needed for security, or the student viewing their own ticket
        if ($userRole === 'admin' || ($userRole === 'student' && $studentId === $currentUserId)) {
            return htmlspecialchars($studentName) . ' <span class="badge bg-secondary ms-1"><i class="bi bi-eye-slash-fill me-1"></i>Anonymous to Staff</span>';
        }
        return '<span class="text-muted fst-italic"><i class="bi bi-incognito me-1"></i>Anonymous Student</span>';
    }

    // If viewer is maintenance staff/technician, default to hiding student name to eliminate bias
    if ($userRole === 'staff') {
        return '<span class="text-muted fst-italic"><i class="bi bi-shield-lock-fill me-1"></i>Student Identity Hidden</span>';
    }

    return htmlspecialchars($studentName);
}

/**
 * Returns formatted HTML badge for Complaint Status
 */
function getStatusBadge($status) {
    switch ($status) {
        case 'Pending':
            return '<span class="badge bg-warning text-dark px-3 py-2 rounded-pill"><i class="bi bi-hourglass-split me-1"></i>Pending</span>';
        case 'Assigned':
            return '<span class="badge bg-info text-dark px-3 py-2 rounded-pill"><i class="bi bi-person-check-fill me-1"></i>Assigned</span>';
        case 'In Progress':
            return '<span class="badge bg-primary px-3 py-2 rounded-pill"><i class="bi bi-gear-fill me-1 spin-icon"></i>In Progress</span>';
        case 'Resolved':
            return '<span class="badge bg-success px-3 py-2 rounded-pill"><i class="bi bi-check-circle-fill me-1"></i>Resolved</span>';
        case 'Rejected':
            return '<span class="badge bg-danger px-3 py-2 rounded-pill"><i class="bi bi-x-circle-fill me-1"></i>Rejected</span>';
        default:
            return '<span class="badge bg-secondary px-3 py-2 rounded-pill">' . htmlspecialchars($status) . '</span>';
    }
}

/**
 * Returns formatted HTML badge for Priority
 */
function getPriorityBadge($priority) {
    switch ($priority) {
        case 'Low':
            return '<span class="badge bg-secondary bg-opacity-75 text-white px-2 py-1"><i class="bi bi-arrow-down-short"></i>Low</span>';
        case 'Medium':
            return '<span class="badge bg-primary bg-opacity-75 text-white px-2 py-1"><i class="bi bi-dash-short"></i>Medium</span>';
        case 'High':
            return '<span class="badge bg-warning text-dark px-2 py-1"><i class="bi bi-arrow-up-short"></i>High</span>';
        case 'Urgent':
            return '<span class="badge bg-danger text-white px-2 py-1"><i class="bi bi-exclamation-triangle-fill me-1"></i>Urgent</span>';
        default:
            return '<span class="badge bg-secondary px-2 py-1">' . htmlspecialchars($priority) . '</span>';
    }
}

/**
 * Generate unique complaint tracking code
 */
function generateComplaintCode() {
    return 'CMP-' . rand(1000, 9999);
}

/**
 * Format relative date time
 */
function formatTimeAgo($datetime) {
    $time = strtotime($datetime);
    $diff = time() - $time;

    if ($diff < 60) return "Just now";
    if ($diff < 3600) return round($diff / 60) . " mins ago";
    if ($diff < 86400) return round($diff / 3600) . " hours ago";
    if ($diff < 604800) return round($diff / 86400) . " days ago";
    return date("M j, Y h:i A", $time);
}
