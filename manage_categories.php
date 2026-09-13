<?php
// manage_categories.php - Admin Category Management

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/functions.php';

checkAuth(['admin']);

$pdo = getDBConnection();

// Add Category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $name = trim($_POST['name'] ?? '');
    $icon = trim($_POST['icon'] ?? 'bi-wrench');
    $description = trim($_POST['description'] ?? '');

    if (!empty($name)) {
        $ins = $pdo->prepare("INSERT INTO categories (name, icon, description) VALUES (:name, :icon, :desc)");
        $ins->execute(['name' => $name, 'icon' => $icon, 'desc' => $description]);
        setFlashMsg('success', "Category '$name' added successfully!");
        redirect('manage_categories.php');
    }
}

// Delete Category
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $delId = intval($_GET['id']);
    $del = $pdo->prepare("DELETE FROM categories WHERE id = :id");
    $del->execute(['id' => $delId]);
    setFlashMsg('success', 'Category deleted.');
    redirect('manage_categories.php');
}

$categories = $pdo->query("SELECT cat.*, COUNT(c.id) as complaint_count FROM categories cat LEFT JOIN complaints c ON cat.id = c.category_id GROUP BY cat.id ORDER BY cat.name ASC")->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="admin_dashboard.php" class="text-decoration-none text-muted small fw-semibold">
            <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
        </a>
        <h3 class="fw-bold text-dark mb-0 mt-1"><i class="bi bi-tags-fill text-primary me-2"></i>Maintenance Categories</h3>
    </div>
    <button type="button" class="btn btn-primary rounded-pill" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
        <i class="bi bi-plus-circle-fill me-1"></i> Add Category
    </button>
</div>

<div class="row g-4">
    <?php foreach ($categories as $cat): ?>
        <div class="col-md-6 col-lg-4">
            <div class="card card-custom p-4 h-100 position-relative">
                <div class="d-flex align-items-center mb-3">
                    <span class="bg-primary text-white p-3 rounded-3 me-3 fs-3">
                        <i class="bi <?= htmlspecialchars($cat['icon']) ?>"></i>
                    </span>
                    <div>
                        <h5 class="fw-bold mb-0"><?= htmlspecialchars($cat['name']) ?></h5>
                        <span class="badge bg-light text-dark border"><?= $cat['complaint_count'] ?> Total Tickets</span>
                    </div>
                </div>
                <p class="text-muted small mb-4 flex-grow-1"><?= htmlspecialchars($cat['description']) ?></p>
                <div class="text-end">
                    <a href="manage_categories.php?action=delete&id=<?= $cat['id'] ?>" class="btn btn-sm btn-outline-danger rounded-pill" onclick="return confirm('Delete this category? All associated tickets will be removed.');">
                        <i class="bi bi-trash-fill me-1"></i> Delete
                    </a>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Modal: Add Category -->
<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-primary text-white py-3">
                <h5 class="modal-header-title modal-title fw-bold"><i class="bi bi-plus-circle-fill me-2"></i> Add Maintenance Category</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="manage_categories.php" method="POST">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Category Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control bg-light" placeholder="e.g. Elevator & Lifts" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Bootstrap Icon Class</label>
                        <input type="text" name="icon" class="form-control bg-light" value="bi-wrench" placeholder="e.g. bi-lightning-charge-fill">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Description</label>
                        <textarea name="description" class="form-control bg-light" rows="3" placeholder="Scope of issues covered under this department..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light py-3">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_category" class="btn btn-primary rounded-pill px-4">Save Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
