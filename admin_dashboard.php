<?php
// admin_dashboard.php - Administrator Control Center & Analytics

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/functions.php';

checkAuth(['admin']);

$currentUser = getCurrentUser();
$pdo = getDBConnection();

// Fetch System Stats
$totalCount = $pdo->query("SELECT COUNT(*) FROM complaints")->fetchColumn();
$pendingCount = $pdo->query("SELECT COUNT(*) FROM complaints WHERE status = 'Pending'")->fetchColumn();
$inProgressCount = $pdo->query("SELECT COUNT(*) FROM complaints WHERE status IN ('Assigned', 'In Progress')")->fetchColumn();
$resolvedCount = $pdo->query("SELECT COUNT(*) FROM complaints WHERE status = 'Resolved'")->fetchColumn();
$avgRating = $pdo->query("SELECT ROUND(AVG(rating), 1) FROM feedbacks")->fetchColumn() ?: '5.0';

// Fetch Status Counts for Chart
$statusCountsStmt = $pdo->query("SELECT status, COUNT(*) as cnt FROM complaints GROUP BY status");
$statusData = [];
while ($row = $statusCountsStmt->fetch()) {
    $statusData[$row['status']] = (int)$row['cnt'];
}

// Fetch Category Counts for Chart
$categoryCountsStmt = $pdo->query("SELECT cat.name, COUNT(c.id) as cnt FROM categories cat LEFT JOIN complaints c ON cat.id = c.category_id GROUP BY cat.id ORDER BY cat.name ASC");
$categoryLabels = [];
$categoryData = [];
while ($row = $categoryCountsStmt->fetch()) {
    $categoryLabels[] = $row['name'];
    $categoryData[] = (int)$row['cnt'];
}

// Fetch all staff/technicians for assignment dropdown
$staffListStmt = $pdo->query("SELECT id, name, department FROM users WHERE role = 'staff' ORDER BY name ASC");
$staffList = $staffListStmt->fetchAll();

// Fetch all complaints
$complaintsStmt = $pdo->query("
    SELECT c.*, cat.name as category_name, cat.icon as category_icon, 
           s.name as student_name, st.name as staff_name 
    FROM complaints c
    JOIN categories cat ON c.category_id = cat.id
    JOIN users s ON c.student_id = s.id
    LEFT JOIN users st ON c.assigned_staff_id = st.id
    ORDER BY c.created_at DESC
");
$allComplaints = $complaintsStmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div class="row align-items-center mb-4">
    <div class="col-md-8">
        <h2 class="fw-bold mb-1"><i class="bi bi-shield-lock-fill text-primary me-2"></i>Executive Admin Control Center</h2>
        <p class="text-muted mb-0">Overview of campus maintenance requests, assignments & performance analytics</p>
    </div>
    <div class="col-md-4 text-md-end mt-3 mt-md-0">
        <a href="manage_users.php" class="btn btn-outline-primary rounded-pill me-2">
            <i class="bi bi-people-fill me-1"></i> Manage Users
        </a>
        <a href="manage_categories.php" class="btn btn-outline-secondary rounded-pill">
            <i class="bi bi-tags-fill me-1"></i> Categories
        </a>
    </div>
</div>

<!-- Key Performance Indicators (KPIs) -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-4 col-lg-2-4">
        <div class="stat-card bg-white border">
            <span class="text-muted small fw-semibold text-uppercase">Total Complaints</span>
            <div class="stat-number text-dark mt-2"><?= $totalCount ?></div>
            <i class="bi bi-collection-fill stat-icon text-primary"></i>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2-4">
        <div class="stat-card bg-white border">
            <span class="text-muted small fw-semibold text-uppercase">Unassigned / Pending</span>
            <div class="stat-number text-warning mt-2"><?= $pendingCount ?></div>
            <i class="bi bi-hourglass-split stat-icon text-warning"></i>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2-4">
        <div class="stat-card bg-white border">
            <span class="text-muted small fw-semibold text-uppercase">In Progress</span>
            <div class="stat-number text-primary mt-2"><?= $inProgressCount ?></div>
            <i class="bi bi-tools stat-icon text-primary"></i>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2-4">
        <div class="stat-card bg-white border">
            <span class="text-muted small fw-semibold text-uppercase">Resolved</span>
            <div class="stat-number text-success mt-2"><?= $resolvedCount ?></div>
            <i class="bi bi-check-circle-fill stat-icon text-success"></i>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2-4">
        <div class="stat-card bg-white border">
            <span class="text-muted small fw-semibold text-uppercase">Avg Student Score</span>
            <div class="stat-number text-warning mt-2"><?= $avgRating ?> <i class="bi bi-star-fill fs-5"></i></div>
            <i class="bi bi-award-fill stat-icon text-warning"></i>
        </div>
    </div>
</div>

<!-- Analytics Charts Row -->
<div class="row g-4 mb-4">
    <div class="col-md-5">
        <div class="card card-custom p-4 h-100">
            <h5 class="fw-bold mb-3"><i class="bi bi-pie-chart-fill text-primary me-2"></i>Status Distribution</h5>
            <div style="height: 240px;" class="position-relative">
                <canvas id="statusChart"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-7">
        <div class="card card-custom p-4 h-100">
            <h5 class="fw-bold mb-3"><i class="bi bi-bar-chart-line-fill text-primary me-2"></i>Complaints by Category</h5>
            <div style="height: 240px;" class="position-relative">
                <canvas id="categoryChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Master Complaints Table -->
<div class="card card-custom p-4 mb-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h4 class="fw-bold mb-1">Master Complaints Ledger</h4>
            <p class="text-muted small mb-0">Assign technicians, view details & override ticket status</p>
        </div>
        <div class="col-md-4">
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" id="tableSearchInput" class="form-control bg-light border-start-0" placeholder="Search ticket, student, location...">
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-custom align-middle filterable-table">
            <thead class="text-muted small text-uppercase">
                <tr>
                    <th>Code</th>
                    <th>Complainant</th>
                    <th>Issue & Location</th>
                    <th>Category</th>
                    <th>Priority</th>
                    <th>Assigned Staff</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($allComplaints as $ac): ?>
                    <tr>
                        <td>
                            <a href="complaint_detail.php?id=<?= $ac['id'] ?>" class="fw-bold text-primary text-decoration-none">
                                <i class="bi bi-ticket-perforated me-1"></i><?= htmlspecialchars($ac['complaint_code']) ?>
                            </a>
                        </td>
                        <td>
                            <!-- Admin view respects anonymous setting flag display -->
                            <?= getStudentDisplayName($ac['student_name'], $ac['is_anonymous'], 'admin', $ac['student_id'], $currentUser['id']) ?>
                        </td>
                        <td>
                            <div class="fw-bold text-dark mb-1"><?= htmlspecialchars($ac['title']) ?></div>
                            <div class="small text-muted"><i class="bi bi-geo-alt-fill text-danger me-1"></i><?= htmlspecialchars($ac['location']) ?></div>
                        </td>
                        <td>
                            <span class="small fw-semibold"><i class="bi <?= htmlspecialchars($ac['category_icon']) ?> text-primary me-1"></i><?= htmlspecialchars($ac['category_name']) ?></span>
                        </td>
                        <td><?= getPriorityBadge($ac['priority']) ?></td>
                        <td>
                            <?php if ($ac['staff_name']): ?>
                                <span class="badge bg-info-subtle text-info border border-info-subtle">
                                    <i class="bi bi-person-fill me-1"></i><?= htmlspecialchars($ac['staff_name']) ?>
                                </span>
                            <?php else: ?>
                                <button type="button" class="btn btn-xs btn-outline-warning rounded-pill" onclick="openAssignModal(<?= $ac['id'] ?>, '<?= $ac['complaint_code'] ?>')">
                                    <i class="bi bi-person-plus-fill me-1"></i> Assign Staff
                                </button>
                            <?php endif; ?>
                        </td>
                        <td><?= getStatusBadge($ac['status']) ?></td>
                        <td class="text-end">
                            <div class="btn-group">
                                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 me-1" onclick="openAssignModal(<?= $ac['id'] ?>, '<?= $ac['complaint_code'] ?>')">
                                    Assign
                                </button>
                                <a href="complaint_detail.php?id=<?= $ac['id'] ?>" class="btn btn-sm btn-light border rounded-pill">
                                    View
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Assign Technician -->
<div class="modal fade" id="assignModal" tabindex="-1" aria-labelledby="assignModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-primary text-white py-3">
                <h5 class="modal-header-title modal-title fw-bold" id="assignModalLabel">
                    <i class="bi bi-person-check-fill me-2"></i> Assign Technician to <span id="assign_ticket_code"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="api/assign_technician.php" method="POST">
                <input type="hidden" name="complaint_id" id="assign_complaint_id" value="">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="staff_id" class="form-label fw-semibold">Select Maintenance Staff / Technician <span class="text-danger">*</span></label>
                        <select class="form-select bg-light" id="staff_id" name="staff_id" required>
                            <option value="">Select Technician...</option>
                            <?php foreach ($staffList as $st): ?>
                                <option value="<?= $st['id'] ?>">
                                    <?= htmlspecialchars($st['name']) ?> (<?= htmlspecialchars($st['department'] ?: 'Maintenance') ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light py-3">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">
                        <i class="bi bi-check-lg me-1"></i> Confirm Assignment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="assets/js/charts.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    initAdminCharts(
        <?= json_encode($statusData) ?>,
        <?= json_encode($categoryLabels) ?>,
        <?= json_encode($categoryData) ?>
    );
});

function openAssignModal(id, code) {
    document.getElementById('assign_complaint_id').value = id;
    document.getElementById('assign_ticket_code').innerText = '#' + code;
    var modal = new bootstrap.Modal(document.getElementById('assignModal'));
    modal.show();
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
