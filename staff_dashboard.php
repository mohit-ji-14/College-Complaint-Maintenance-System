<?php
// staff_dashboard.php - Technician Maintenance Work Portal

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/functions.php';

checkAuth(['staff']);

$currentUser = getCurrentUser();
$pdo = getDBConnection();

// Fetch metrics for technician
$assignedCountStmt = $pdo->prepare("SELECT COUNT(*) FROM complaints WHERE assigned_staff_id = ?");
$assignedCountStmt->execute([$currentUser['id']]);
$assignedTotal = $assignedCountStmt->fetchColumn();

$inProgressStmt = $pdo->prepare("SELECT COUNT(*) FROM complaints WHERE assigned_staff_id = ? AND status = 'In Progress'");
$inProgressStmt->execute([$currentUser['id']]);
$inProgressCount = $inProgressStmt->fetchColumn();

$resolvedStmt = $pdo->prepare("SELECT COUNT(*) FROM complaints WHERE assigned_staff_id = ? AND status = 'Resolved'");
$resolvedStmt->execute([$currentUser['id']]);
$resolvedCount = $resolvedStmt->fetchColumn();

$pendingUnassignedStmt = $pdo->query("SELECT COUNT(*) FROM complaints WHERE status = 'Pending' AND assigned_staff_id IS NULL");
$unassignedCount = $pendingUnassignedStmt->fetchColumn();

// Fetch assigned and unassigned complaints
$complaintsStmt = $pdo->prepare("
    SELECT c.*, cat.name as category_name, cat.icon as category_icon, s.name as student_name 
    FROM complaints c
    JOIN categories cat ON c.category_id = cat.id
    JOIN users s ON c.student_id = s.id
    WHERE c.assigned_staff_id = :staff_id OR (c.status = 'Pending' AND c.assigned_staff_id IS NULL)
    ORDER BY FIELD(c.priority, 'Urgent', 'High', 'Medium', 'Low'), c.created_at DESC
");
$complaintsStmt->execute(['staff_id' => $currentUser['id']]);
$workOrders = $complaintsStmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div class="row align-items-center mb-4">
    <div class="col-md-8">
        <h2 class="fw-bold mb-1"><i class="bi bi-tools text-primary me-2"></i>Technician Operations Console</h2>
        <p class="text-muted mb-0">Assigned Work Orders & Maintenance Tasks (Student privacy masked)</p>
    </div>
    <div class="col-md-4 text-md-end mt-3 mt-md-0">
        <span class="badge bg-primary fs-6 px-3 py-2 rounded-pill">
            <i class="bi bi-person-workspace me-1"></i> <?= htmlspecialchars($currentUser['department'] ?: 'Maintenance Dept') ?>
        </span>
    </div>
</div>

<!-- Stat Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-card bg-white border">
            <span class="text-muted small fw-semibold text-uppercase">My Work Orders</span>
            <div class="stat-number text-dark mt-2"><?= $assignedTotal ?></div>
            <i class="bi bi-clipboard-check-fill stat-icon text-primary"></i>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card bg-white border">
            <span class="text-muted small fw-semibold text-uppercase">In Progress</span>
            <div class="stat-number text-primary mt-2"><?= $inProgressCount ?></div>
            <i class="bi bi-gear-wide-connected stat-icon text-primary"></i>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card bg-white border">
            <span class="text-muted small fw-semibold text-uppercase">Completed / Resolved</span>
            <div class="stat-number text-success mt-2"><?= $resolvedCount ?></div>
            <i class="bi bi-check-circle-fill stat-icon text-success"></i>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card bg-white border">
            <span class="text-muted small fw-semibold text-uppercase">Unassigned Queue</span>
            <div class="stat-number text-warning mt-2"><?= $unassignedCount ?></div>
            <i class="bi bi-exclamation-circle-fill stat-icon text-warning"></i>
        </div>
    </div>
</div>

<!-- Work Orders Table -->
<div class="card card-custom p-4 mb-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h4 class="fw-bold mb-1">Maintenance Work Queue</h4>
            <p class="text-muted small mb-0">Prioritized repair tickets assigned to your department</p>
        </div>
        <div class="col-md-4">
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" id="tableSearchInput" class="form-control bg-light border-start-0" placeholder="Filter code, title or room...">
            </div>
        </div>
    </div>

    <?php if (empty($workOrders)): ?>
        <div class="text-center py-5">
            <div class="display-1 text-muted opacity-25 mb-3"><i class="bi bi-emoji-smile"></i></div>
            <h5 class="fw-bold text-secondary">No Pending Maintenance Orders</h5>
            <p class="text-muted mb-0">All assigned complaints are up to date!</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-custom align-middle filterable-table">
                <thead class="text-muted small text-uppercase">
                    <tr>
                        <th>Ticket</th>
                        <th>Category</th>
                        <th>Issue Summary & Room</th>
                        <th>Priority</th>
                        <th>Complainant</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($workOrders as $w): ?>
                        <tr>
                            <td>
                                <a href="complaint_detail.php?id=<?= $w['id'] ?>" class="fw-bold text-primary text-decoration-none">
                                    <i class="bi bi-ticket-detailed me-1"></i><?= htmlspecialchars($w['complaint_code']) ?>
                                </a>
                            </td>
                            <td>
                                <span class="d-flex align-items-center">
                                    <i class="bi <?= htmlspecialchars($w['category_icon']) ?> text-primary me-2"></i>
                                    <span class="small fw-semibold"><?= htmlspecialchars($w['category_name']) ?></span>
                                </span>
                            </td>
                            <td>
                                <div class="fw-bold text-dark mb-1"><?= htmlspecialchars($w['title']) ?></div>
                                <div class="small text-muted"><i class="bi bi-geo-alt-fill text-danger me-1"></i><?= htmlspecialchars($w['location']) ?></div>
                            </td>
                            <td><?= getPriorityBadge($w['priority']) ?></td>
                            <td>
                                <!-- ENFORCED ANONYMITY FOR MAINTENANCE STAFF -->
                                <?= getStudentDisplayName($w['student_name'], $w['is_anonymous'], 'staff') ?>
                            </td>
                            <td><?= getStatusBadge($w['status']) ?></td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-primary rounded-pill me-1" onclick="openUpdateModal(<?= $w['id'] ?>, '<?= $w['complaint_code'] ?>', '<?= $w['status'] ?>')">
                                    <i class="bi bi-pencil-square me-1"></i> Update Status
                                </button>
                                <a href="complaint_detail.php?id=<?= $w['id'] ?>" class="btn btn-sm btn-outline-secondary rounded-pill">
                                    Details
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Modal: Update Work Status & Upload Resolution Proof -->
<div class="modal fade" id="updateStatusModal" tabindex="-1" aria-labelledby="updateStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-primary text-white py-3">
                <h5 class="modal-header-title modal-title fw-bold" id="updateStatusModalLabel">
                    <i class="bi bi-tools me-2"></i> Update Ticket <span id="modal_ticket_code"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="api/update_status.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="complaint_id" id="modal_complaint_id" value="">
                
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="modal_status" class="form-label fw-semibold">New Work Status <span class="text-danger">*</span></label>
                        <select class="form-select bg-light" id="modal_status" name="status" required onchange="toggleProofUpload(this.value)">
                            <option value="In Progress">⚙️ In Progress - Repair underway</option>
                            <option value="Resolved">✅ Resolved - Fix completed</option>
                            <option value="Rejected">❌ Rejected - Unable to repair / Duplicate</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="comment" class="form-label fw-semibold">Technician Work Notes / Remarks</label>
                        <textarea class="form-control bg-light" id="comment" name="comment" rows="3" placeholder="Describe actions taken or parts replaced..."></textarea>
                    </div>

                    <!-- Proof Upload (Shown when Resolved) -->
                    <div id="proofUploadSection" class="mb-3">
                        <label class="form-label fw-semibold"><i class="bi bi-camera-fill text-success me-1"></i> Upload Completion Proof Photo</label>
                        <div class="image-preview-box" onclick="document.getElementById('resolution_proof').click();">
                            <i class="bi bi-cloud-arrow-up-fill display-6 text-success mb-2"></i>
                            <h6 class="fw-bold mb-1">Click to attach completion proof photo</h6>
                            <p class="small text-muted mb-0">Show fixed appliance/outlet/furniture</p>
                            <input type="file" id="resolution_proof" name="resolution_proof" class="d-none image-file-input" accept="image/*" data-preview-target="proof_img_preview">
                        </div>
                        <div id="proof_img_preview_container" class="mt-2 text-center d-none">
                            <img id="proof_img_preview" src="" class="img-thumbnail" style="max-height: 160px;" alt="Proof Preview">
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light py-3">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">
                        <i class="bi bi-check-circle-fill me-1"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openUpdateModal(id, code, currentStatus) {
    document.getElementById('modal_complaint_id').value = id;
    document.getElementById('modal_ticket_code').innerText = '#' + code;
    document.getElementById('modal_status').value = currentStatus !== 'Pending' ? currentStatus : 'In Progress';
    
    var bsModal = new bootstrap.Modal(document.getElementById('updateStatusModal'));
    bsModal.show();
}

function toggleProofUpload(status) {
    var proofSec = document.getElementById('proofUploadSection');
    if (status === 'Resolved') {
        proofSec.style.display = 'block';
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
