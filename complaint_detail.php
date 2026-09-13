<?php
// complaint_detail.php - Ticket Progress Timeline, Anonymity Handling & Feedback

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/functions.php';

checkAuth(['student', 'staff', 'admin']);

$currentUser = getCurrentUser();
$complaint_id = intval($_GET['id'] ?? 0);

if ($complaint_id <= 0) {
    setFlashMsg('danger', 'Invalid complaint ticket ID.');
    redirect('index.php');
}

$pdo = getDBConnection();

// Fetch complaint with student and technician details
$query = "
    SELECT c.*, 
           cat.name as category_name, cat.icon as category_icon,
           s.name as student_name, s.email as student_email, s.department as student_dept,
           st.name as staff_name, st.phone as staff_phone
    FROM complaints c
    JOIN categories cat ON c.category_id = cat.id
    JOIN users s ON c.student_id = s.id
    LEFT JOIN users st ON c.assigned_staff_id = st.id
    WHERE c.id = :id
";
$stmt = $pdo->prepare($query);
$stmt->execute(['id' => $complaint_id]);
$complaint = $stmt->fetch();

if (!$complaint) {
    setFlashMsg('danger', 'Complaint ticket not found.');
    redirect('index.php');
}

// Student security check: Students can only view their own ticket
if ($currentUser['role'] === 'student' && $complaint['student_id'] !== $currentUser['id']) {
    setFlashMsg('danger', 'Access denied.');
    redirect('student_dashboard.php');
}

// Staff security check: Technicians can only view assigned tickets or tickets in their category
if ($currentUser['role'] === 'staff' && $complaint['assigned_staff_id'] !== $currentUser['id'] && $complaint['status'] !== 'Pending') {
    // Technicians can view assigned or unassigned pending tickets
}

// Fetch complaint updates activity timeline
$updatesStmt = $pdo->prepare("
    SELECT u.*, usr.name as updater_name, usr.role as updater_role 
    FROM complaint_updates u 
    JOIN users usr ON u.user_id = usr.id 
    WHERE u.complaint_id = :cid 
    ORDER BY u.created_at ASC
");
$updatesStmt->execute(['cid' => $complaint_id]);
$updates = $updatesStmt->fetchAll();

// Fetch feedback if resolved
$feedbackStmt = $pdo->prepare("SELECT * FROM feedbacks WHERE complaint_id = :cid LIMIT 1");
$feedbackStmt->execute(['cid' => $complaint_id]);
$feedback = $feedbackStmt->fetch();

require_once __DIR__ . '/includes/header.php';

// Determine stepper stage
$statusStages = ['Pending' => 1, 'Assigned' => 2, 'In Progress' => 3, 'Resolved' => 4, 'Rejected' => 0];
$currentStage = $statusStages[$complaint['status']] ?? 1;
?>

<!-- Top Breadcrumb & Actions -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="<?= $currentUser['role'] === 'student' ? 'student_dashboard.php' : ($currentUser['role'] === 'staff' ? 'staff_dashboard.php' : 'admin_dashboard.php') ?>" class="text-decoration-none text-muted small fw-semibold">
            <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
        </a>
        <h3 class="fw-bold text-dark mb-0 mt-1">
            Ticket <span class="text-primary">#<?= htmlspecialchars($complaint['complaint_code']) ?></span>
        </h3>
    </div>
    <div>
        <?= getStatusBadge($complaint['status']) ?>
    </div>
</div>

<!-- Stepper Timeline (For non-rejected complaints) -->
<?php if ($complaint['status'] !== 'Rejected'): ?>
<div class="card card-custom p-4 mb-4">
    <h6 class="fw-bold text-muted text-uppercase mb-4">Live Resolution Stepper</h6>
    <div class="stepper-wrapper">
        <div class="stepper-item <?= $currentStage >= 1 ? ($currentStage > 1 ? 'completed' : 'active') : '' ?>">
            <div class="step-counter"><i class="<?= $currentStage > 1 ? 'bi bi-check-lg' : 'bi bi-card-text' ?>"></i></div>
            <div class="step-name">Logged</div>
        </div>
        <div class="stepper-item <?= $currentStage >= 2 ? ($currentStage > 2 ? 'completed' : 'active') : '' ?>">
            <div class="step-counter"><i class="<?= $currentStage > 2 ? 'bi bi-check-lg' : 'bi bi-person-check-fill' ?>"></i></div>
            <div class="step-name">Assigned</div>
        </div>
        <div class="stepper-item <?= $currentStage >= 3 ? ($currentStage > 3 ? 'completed' : 'active') : '' ?>">
            <div class="step-counter"><i class="<?= $currentStage > 3 ? 'bi bi-check-lg' : 'bi bi-tools' ?>"></i></div>
            <div class="step-name">In Progress</div>
        </div>
        <div class="stepper-item <?= $currentStage >= 4 ? 'completed active' : '' ?>">
            <div class="step-counter"><i class="bi bi-patch-check-fill"></i></div>
            <div class="step-name">Resolved</div>
        </div>
    </div>
</div>
<?php else: ?>
<div class="alert alert-danger p-3 mb-4 rounded-3 border-0">
    <i class="bi bi-x-circle-fill me-2 fs-5"></i> <strong>This complaint ticket was rejected.</strong> Check notes below for details.
</div>
<?php endif; ?>

<div class="row g-4">
    <!-- Left Main Column: Complaint Info & Timeline -->
    <div class="col-lg-8">
        <div class="card card-custom p-4 mb-4">
            <div class="d-flex align-items-center mb-3">
                <span class="bg-primary-subtle text-primary p-3 rounded-3 me-3 fs-3">
                    <i class="bi <?= htmlspecialchars($complaint['category_icon']) ?>"></i>
                </span>
                <div>
                    <span class="badge bg-light text-dark border mb-1"><?= htmlspecialchars($complaint['category_name']) ?></span>
                    <h4 class="fw-bold mb-0"><?= htmlspecialchars($complaint['title']) ?></h4>
                </div>
            </div>

            <div class="p-3 bg-light rounded-3 mb-4">
                <div class="row g-2 small">
                    <div class="col-sm-6">
                        <span class="text-muted">Location:</span> 
                        <strong class="text-dark"><i class="bi bi-geo-alt-fill text-danger me-1"></i><?= htmlspecialchars($complaint['location']) ?></strong>
                    </div>
                    <div class="col-sm-6">
                        <span class="text-muted">Priority Level:</span> 
                        <?= getPriorityBadge($complaint['priority']) ?>
                    </div>
                    <div class="col-sm-6">
                        <span class="text-muted">Date Filed:</span> 
                        <strong class="text-dark"><?= date('F j, Y, g:i a', strtotime($complaint['created_at'])) ?></strong>
                    </div>
                    <div class="col-sm-6">
                        <span class="text-muted">Complainant:</span> 
                        <?= getStudentDisplayName($complaint['student_name'], $complaint['is_anonymous'], $currentUser['role'], $complaint['student_id'], $currentUser['id']) ?>
                    </div>
                </div>
            </div>

            <h6 class="fw-bold mb-2">Description:</h6>
            <p class="text-secondary lh-lg mb-4"><?= nl2br(htmlspecialchars($complaint['description'])) ?></p>


        <!-- Activity Timeline Card -->
        <div class="card card-custom p-4 mb-4">
            <h5 class="fw-bold mb-4"><i class="bi bi-journal-text text-primary me-2"></i> Activity & Progress Updates</h5>
            
            <div class="timeline position-relative ps-4 border-start border-2 border-primary-subtle">
                <?php foreach ($updates as $upd): ?>
                    <div class="timeline-item mb-4 position-relative">
                        <div class="position-absolute bg-primary rounded-circle" style="width: 12px; height: 12px; left: -31px; top: 4px;"></div>
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="fw-bold text-dark me-2">
                                <?= htmlspecialchars($upd['updater_name']) ?> 
                                <span class="badge bg-secondary-subtle text-secondary small text-uppercase ms-1"><?= $upd['updater_role'] ?></span>
                            </span>
                            <span class="small text-muted"><?= formatTimeAgo($upd['created_at']) ?></span>
                        </div>
                        <?php if ($upd['status_to']): ?>
                            <div class="mb-1">Changed status to <?= getStatusBadge($upd['status_to']) ?></div>
                        <?php endif; ?>
                        <?php if ($upd['comment']): ?>
                            <p class="text-secondary small bg-light p-2 rounded-2 mb-0"><?= htmlspecialchars($upd['comment']) ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Student Feedback Section (Only when resolved) -->
        <?php if ($complaint['status'] === 'Resolved' && $currentUser['role'] === 'student'): ?>
            <div class="card card-custom p-4 bg-light-subtle border-success">
                <h5 class="fw-bold text-success mb-3"><i class="bi bi-star-fill text-warning me-2"></i> Student Satisfaction Feedback</h5>
                
                <?php if ($feedback): ?>
                    <div class="p-3 bg-white rounded-3 border">
                        <div class="mb-2">
                            <?php for ($i=1; $i<=5; $i++): ?>
                                <i class="bi bi-star-fill <?= $i <= $feedback['rating'] ? 'text-warning' : 'text-muted opacity-25' ?> fs-5"></i>
                            <?php endfor; ?>
                            <span class="fw-bold ms-2"><?= $feedback['rating'] ?> / 5 Stars</span>
                        </div>
                        <?php if ($feedback['comments']): ?>
                            <p class="mb-0 text-muted italic">"<?= htmlspecialchars($feedback['comments']) ?>"</p>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <form action="api/submit_feedback.php" method="POST">
                        <input type="hidden" name="complaint_id" value="<?= $complaint['id'] ?>">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Rate Service Quality:</label>
                            <select name="rating" class="form-select bg-white" required>
                                <option value="5">⭐⭐⭐⭐⭐ 5 Stars - Excellent Service</option>
                                <option value="4">⭐⭐⭐⭐ 4 Stars - Good</option>
                                <option value="3">⭐⭐⭐ 3 Stars - Average</option>
                                <option value="2">⭐⭐ 2 Stars - Needs Improvement</option>
                                <option value="1">⭐ 1 Star - Poor</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Review / Remarks (Optional):</label>
                            <textarea name="comments" class="form-control bg-white" rows="2" placeholder="Tell us about your experience..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-success rounded-pill px-4">Submit Feedback</button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Right Sidebar Column: Technician & Control Info -->
    <div class="col-lg-4">
        <!-- Technician Card -->
        <div class="card card-custom p-4 mb-4">
            <h6 class="fw-bold text-muted text-uppercase mb-3">Assigned Maintenance Staff</h6>
            <?php if ($complaint['staff_name']): ?>
                <div class="d-flex align-items-center mb-3">
                    <div class="bg-info text-dark rounded-circle p-3 me-3 fw-bold fs-4 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                        <?= strtoupper(substr($complaint['staff_name'], 0, 1)) ?>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($complaint['staff_name']) ?></h6>
                        <span class="small text-muted d-block"><i class="bi bi-telephone-fill me-1"></i><?= htmlspecialchars($complaint['staff_phone'] ?? 'N/A') ?></span>
                    </div>
                </div>
            <?php else: ?>
                <div class="text-center py-3 text-muted">
                    <i class="bi bi-clock-history fs-3 d-block mb-1 text-warning"></i>
                    <span class="small">Awaiting Technician Assignment</span>
                </div>
            <?php endif; ?>
        </div>

        <!-- Anonymity Status Banner -->
        <div class="card card-custom p-4 mb-4 bg-dark text-white">
            <h6 class="fw-bold text-warning mb-2"><i class="bi bi-shield-check me-1"></i> Privacy & Anonymity Status</h6>
            <?php if ($complaint['is_anonymous']): ?>
                <p class="small text-white-50 mb-0">
                    This ticket is marked <strong>ANONYMOUS</strong>. Student details are hidden from technicians to ensure non-biased maintenance service.
                </p>
            <?php else: ?>
                <p class="small text-white-50 mb-0">
                    Standard complaint record. Staff can view location details for repairs.
                </p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
