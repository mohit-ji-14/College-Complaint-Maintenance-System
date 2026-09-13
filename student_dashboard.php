<?php
// student_dashboard.php - Student Portal Dashboard & Complaint Filing

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/functions.php';

checkAuth(['student']);

$currentUser = getCurrentUser();
$pdo = getDBConnection();

// Fetch categories for modal dropdown
$categoriesStmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
$categories = $categoriesStmt->fetchAll();

// Fetch student stats
$totalStmt = $pdo->prepare("SELECT COUNT(*) FROM complaints WHERE student_id = ?");
$totalStmt->execute([$currentUser['id']]);
$totalCount = $totalStmt->fetchColumn();

$pendingStmt = $pdo->prepare("SELECT COUNT(*) FROM complaints WHERE student_id = ? AND status = 'Pending'");
$pendingStmt->execute([$currentUser['id']]);
$pendingCount = $pendingStmt->fetchColumn();

$inProgressStmt = $pdo->prepare("SELECT COUNT(*) FROM complaints WHERE student_id = ? AND status IN ('Assigned', 'In Progress')");
$inProgressStmt->execute([$currentUser['id']]);
$inProgressCount = $inProgressStmt->fetchColumn();

$resolvedStmt = $pdo->prepare("SELECT COUNT(*) FROM complaints WHERE student_id = ? AND status = 'Resolved'");
$resolvedStmt->execute([$currentUser['id']]);
$resolvedCount = $resolvedStmt->fetchColumn();

// Fetch student complaints
$complaintsStmt = $pdo->prepare("
    SELECT c.*, cat.name as category_name, cat.icon as category_icon 
    FROM complaints c 
    JOIN categories cat ON c.category_id = cat.id 
    WHERE c.student_id = :student_id 
    ORDER BY c.created_at DESC
");
$complaintsStmt->execute(['student_id' => $currentUser['id']]);
$myComplaints = $complaintsStmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div class="row align-items-center mb-4">
    <div class="col-md-8">
        <h2 class="fw-bold mb-1">Welcome back, <?= htmlspecialchars($currentUser['name']) ?>! 👋</h2>
        <p class="text-muted mb-0">Track your maintenance tickets or lodge a new complaint.</p>
    </div>
    <div class="col-md-4 text-md-end mt-3 mt-md-0">
        <button type="button" class="btn btn-primary btn-lg rounded-pill shadow-sm" data-bs-toggle="modal" data-bs-target="#newComplaintModal">
            <i class="bi bi-plus-circle-fill me-2"></i> Submit New Complaint
        </button>
    </div>
</div>

<!-- Stats Row -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-card bg-white border">
            <span class="text-muted small fw-semibold text-uppercase">Total Submitted</span>
            <div class="stat-number text-dark mt-2"><?= $totalCount ?></div>
            <i class="bi bi-folder-fill stat-icon text-primary"></i>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card bg-white border">
            <span class="text-muted small fw-semibold text-uppercase">Pending Review</span>
            <div class="stat-number text-warning mt-2"><?= $pendingCount ?></div>
            <i class="bi bi-hourglass-split stat-icon text-warning"></i>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card bg-white border">
            <span class="text-muted small fw-semibold text-uppercase">In Progress</span>
            <div class="stat-number text-primary mt-2"><?= $inProgressCount ?></div>
            <i class="bi bi-tools stat-icon text-primary"></i>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card bg-white border">
            <span class="text-muted small fw-semibold text-uppercase">Resolved</span>
            <div class="stat-number text-success mt-2"><?= $resolvedCount ?></div>
            <i class="bi bi-check-circle-fill stat-icon text-success"></i>
        </div>
    </div>
</div>

<!-- Complaints Section -->
<div class="card card-custom p-4 mb-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h4 class="fw-bold mb-1">My Complaint Tickets</h4>
            <p class="text-muted small mb-0">Live status & detailed activity timeline of your requests</p>
        </div>
        <div class="col-md-4">
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" id="tableSearchInput" class="form-control bg-light border-start-0" placeholder="Search ticket code, title or location...">
            </div>
        </div>
    </div>

    <?php if (empty($myComplaints)): ?>
        <div class="text-center py-5">
            <div class="display-1 text-muted opacity-25 mb-3"><i class="bi bi-inbox-fill"></i></div>
            <h5 class="fw-bold text-secondary">No Complaints Lodged Yet</h5>
            <p class="text-muted mb-3">If you encounter any maintenance or infrastructure issue on campus, let us know!</p>
            <button type="button" class="btn btn-primary rounded-pill" data-bs-toggle="modal" data-bs-target="#newComplaintModal">
                <i class="bi bi-plus-lg me-1"></i> File First Complaint
            </button>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-custom align-middle filterable-table">
                <thead class="text-muted small text-uppercase">
                    <tr>
                        <th>Ticket Code</th>
                        <th>Category</th>
                        <th>Title & Location</th>
                        <th>Priority</th>
                        <th>Privacy</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($myComplaints as $c): ?>
                        <tr>
                            <td>
                                <a href="complaint_detail.php?id=<?= $c['id'] ?>" class="fw-bold text-primary text-decoration-none">
                                    <i class="bi bi-ticket-perforated-fill me-1"></i><?= htmlspecialchars($c['complaint_code']) ?>
                                </a>
                            </td>
                            <td>
                                <span class="d-flex align-items-center">
                                    <i class="bi <?= htmlspecialchars($c['category_icon']) ?> text-primary me-2 fs-5"></i>
                                    <span><?= htmlspecialchars($c['category_name']) ?></span>
                                </span>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark mb-1"><?= htmlspecialchars($c['title']) ?></div>
                                <div class="small text-muted"><i class="bi bi-geo-alt-fill me-1 text-danger"></i><?= htmlspecialchars($c['location']) ?></div>
                            </td>
                            <td><?= getPriorityBadge($c['priority']) ?></td>
                            <td>
                                <?php if ($c['is_anonymous']): ?>
                                    <span class="badge bg-purple-subtle text-purple border border-purple-subtle" title="Identity masked from repair staff">
                                        <i class="bi bi-incognito me-1"></i> Anonymous
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-light text-secondary border">Public</span>
                                <?php endif; ?>
                            </td>
                            <td><?= getStatusBadge($c['status']) ?></td>
                            <td class="small text-muted"><?= formatTimeAgo($c['created_at']) ?></td>
                            <td class="text-end">
                                <a href="complaint_detail.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                    View Ticket <i class="bi bi-arrow-right ms-1"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Modal: New Complaint Form -->
<div class="modal fade" id="newComplaintModal" tabindex="-1" aria-labelledby="newComplaintModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-primary text-white py-3">
                <h5 class="modal-header-title modal-title fw-bold" id="newComplaintModalLabel">
                    <i class="bi bi-file-earmark-plus-fill me-2"></i> Submit Maintenance Request
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="api/submit_complaint.php" method="POST" enctype="multipart/form-data">
                <div class="modal-body p-4">
                    
                    <!-- Anonymous Feature Callout Banner -->
                    <div class="anonymous-banner mb-4 d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-incognito fs-3 me-3 text-warning"></i>
                            <div>
                                <h6 class="fw-bold mb-1">Student Identity Protection</h6>
                                <p class="small mb-0 opacity-75">You can submit this complaint anonymously. Your name will be hidden from maintenance technicians.</p>
                            </div>
                        </div>
                        <div class="form-check form-switch fs-5 ms-3">
                            <input class="form-check-input" type="checkbox" role="switch" id="is_anonymous" name="is_anonymous" value="1">
                            <label class="form-check-label small text-white" for="is_anonymous">Hide Identity</label>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="category_id" class="form-label fw-semibold">Complaint Category <span class="text-danger">*</span></label>
                            <select class="form-select bg-light" id="category_id" name="category_id" required>
                                <option value="">Select Category...</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="priority" class="form-label fw-semibold">Priority Level <span class="text-danger">*</span></label>
                            <select class="form-select bg-light" id="priority" name="priority" required>
                                <option value="Low">Low - Normal routine repair</option>
                                <option value="Medium" selected>Medium - Attention needed soon</option>
                                <option value="High">High - Disrupting daily operations</option>
                                <option value="Urgent">Urgent - Emergency / Safety hazard</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="title" class="form-label fw-semibold">Complaint Title / Summary <span class="text-danger">*</span></label>
                        <input type="text" class="form-control bg-light" id="title" name="title" placeholder="e.g. Projector in Lab 4 display flickering" required>
                    </div>

                    <div class="mb-3">
                        <label for="location" class="form-label fw-semibold">Exact Location / Room No. <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-geo-alt-fill text-danger"></i></span>
                            <input type="text" class="form-control bg-light border-start-0" id="location" name="location" placeholder="e.g. Science Block, 2nd Floor Room 204" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label fw-semibold">Detailed Description <span class="text-danger">*</span></label>
                        <textarea class="form-control bg-light" id="description" name="description" rows="4" placeholder="Describe the maintenance issue clearly..." required></textarea>
                    </div>


                </div>
                <div class="modal-footer bg-light py-3">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">
                        <i class="bi bi-send-fill me-1"></i> Submit Complaint
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
