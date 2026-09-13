<?php
// manage_users.php - Admin User Management (Create Technicians, Change Roles, View Users)

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/includes/functions.php';

checkAuth(['admin']);

$currentUser = getCurrentUser();
$pdo = getDBConnection();

// Handle User Creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_user'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? 'Password123';
    $role = $_POST['role'] ?? 'student';
    $department = trim($_POST['department'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if (empty($name) || empty($email)) {
        setFlashMsg('danger', 'Name and Email are required.');
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        if ($stmt->fetch()) {
            setFlashMsg('danger', 'Email address is already in use.');
        } else {
            $hashedPass = password_hash($password, PASSWORD_DEFAULT);
            $ins = $pdo->prepare("INSERT INTO users (name, email, password, role, department, phone) VALUES (:name, :email, :pass, :role, :dept, :phone)");
            $ins->execute([
                'name' => $name,
                'email' => $email,
                'pass' => $hashedPass,
                'role' => $role,
                'dept' => $department,
                'phone' => $phone
            ]);
            setFlashMsg('success', "User '$name' ($role) created successfully!");
            redirect('manage_users.php');
        }
    }
}

// Handle User Deletion
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $deleteId = intval($_GET['id']);
    if ($deleteId === $currentUser['id']) {
        setFlashMsg('danger', 'You cannot delete your own admin account!');
    } else {
        $del = $pdo->prepare("DELETE FROM users WHERE id = :id");
        $del->execute(['id' => $deleteId]);
        setFlashMsg('success', 'User deleted successfully.');
    }
    redirect('manage_users.php');
}

// Fetch all users
$users = $pdo->query("SELECT * FROM users ORDER BY role ASC, name ASC")->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="admin_dashboard.php" class="text-decoration-none text-muted small fw-semibold">
            <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
        </a>
        <h3 class="fw-bold text-dark mb-0 mt-1"><i class="bi bi-people-fill text-primary me-2"></i>User Management</h3>
    </div>
    <button type="button" class="btn btn-primary rounded-pill" data-bs-toggle="modal" data-bs-target="#addUserModal">
        <i class="bi bi-person-plus-fill me-1"></i> Add New User / Technician
    </button>
</div>

<div class="card card-custom p-4 mb-4">
    <div class="table-responsive">
        <table class="table table-custom align-middle">
            <thead class="text-muted small text-uppercase">
                <tr>
                    <th>User ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Department</th>
                    <th>Phone</th>
                    <th>Joined</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td class="fw-bold">#<?= $u['id'] ?></td>
                        <td>
                            <div class="fw-semibold text-dark"><?= htmlspecialchars($u['name']) ?></div>
                        </td>
                        <td class="small text-muted"><?= htmlspecialchars($u['email']) ?></td>
                        <td>
                            <?php if ($u['role'] === 'admin'): ?>
                                <span class="badge bg-danger px-3 py-1 rounded-pill">Admin</span>
                            <?php elseif ($u['role'] === 'staff'): ?>
                                <span class="badge bg-info text-dark px-3 py-1 rounded-pill">Technician/Staff</span>
                            <?php else: ?>
                                <span class="badge bg-success px-3 py-1 rounded-pill">Student</span>
                            <?php endif; ?>
                        </td>
                        <td class="small"><?= htmlspecialchars($u['department'] ?: 'N/A') ?></td>
                        <td class="small"><?= htmlspecialchars($u['phone'] ?: 'N/A') ?></td>
                        <td class="small text-muted"><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
                        <td class="text-end">
                            <?php if ($u['id'] !== $currentUser['id']): ?>
                                <a href="manage_users.php?action=delete&id=<?= $u['id'] ?>" class="btn btn-sm btn-outline-danger rounded-circle" onclick="return confirm('Are you sure you want to delete this user?');" data-bs-toggle="tooltip" title="Delete User">
                                    <i class="bi bi-trash-fill"></i>
                                </a>
                            <?php else: ?>
                                <span class="badge bg-light text-muted border">You</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Add User -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-primary text-white py-3">
                <h5 class="modal-header-title modal-title fw-bold"><i class="bi bi-person-plus-fill me-2"></i> Add New User</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="manage_users.php" method="POST">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control bg-light" placeholder="e.g. John Doe" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control bg-light" placeholder="john@college.edu" required>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Role <span class="text-danger">*</span></label>
                            <select name="role" class="form-select bg-light" required>
                                <option value="student">Student</option>
                                <option value="staff" selected>Technician / Maintenance Staff</option>
                                <option value="admin">System Administrator</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Department</label>
                            <input type="text" name="department" class="form-control bg-light" placeholder="e.g. Electrical Dept">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Password</label>
                        <input type="password" name="password" class="form-control bg-light" value="Password123" required>
                        <div class="form-text">Default password set to Password123</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Phone Number</label>
                        <input type="tel" name="phone" class="form-control bg-light" placeholder="9876543210">
                    </div>
                </div>
                <div class="modal-footer bg-light py-3">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="create_user" class="btn btn-primary rounded-pill px-4">Create Account</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
