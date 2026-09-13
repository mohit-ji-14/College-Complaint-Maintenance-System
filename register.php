<?php
// register.php - Student Registration Page

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) {
    redirect('student_dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_submit'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $department = trim($_POST['department'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if (empty($name) || empty($email) || empty($password)) {
        setFlashMsg('danger', 'Name, Email, and Password are required fields.');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        setFlashMsg('danger', 'Please enter a valid email address.');
    } else {
        $pdo = getDBConnection();
        // Check if email already registered
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        
        if ($stmt->fetch()) {
            setFlashMsg('danger', 'That email address is already registered. Please login.');
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $insertStmt = $pdo->prepare("INSERT INTO users (name, email, password, role, department, phone) VALUES (:name, :email, :password, 'student', :department, :phone)");
            $insertStmt->execute([
                'name' => $name,
                'email' => $email,
                'password' => $hashedPassword,
                'department' => $department,
                'phone' => $phone
            ]);

            $newUserId = $pdo->lastInsertId();
            $_SESSION['user_id'] = $newUserId;
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_role'] = 'student';
            $_SESSION['user_department'] = $department;

            setFlashMsg('success', 'Registration successful! Welcome to the College Maintenance Portal.');
            redirect('student_dashboard.php');
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center py-4">
    <div class="col-md-8 col-lg-6">
        <div class="card card-custom p-4 p-md-5 shadow-lg">
            <div class="text-center mb-4">
                <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3 shadow" style="width: 54px; height: 54px;">
                    <i class="bi bi-person-plus-fill fs-3"></i>
                </div>
                <h3 class="fw-bold">Student Registration</h3>
                <p class="text-muted small">Create an account to submit & track maintenance requests</p>
            </div>

            <form action="register.php" method="POST">
                <div class="mb-3">
                    <label for="name" class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control bg-light" id="name" name="name" placeholder="e.g. Alex Johnson" required>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label fw-semibold">College Email Address <span class="text-danger">*</span></label>
                    <input type="email" class="form-control bg-light" id="email" name="email" placeholder="student@college.edu" required>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="department" class="form-label fw-semibold">Department / Major</label>
                        <select class="form-select bg-light" id="department" name="department">
                            <option value="">Select Department...</option>
                            <option value="Computer Science">Computer Science</option>
                            <option value="Electrical Engineering">Electrical Engineering</option>
                            <option value="Mechanical Engineering">Mechanical Engineering</option>
                            <option value="Civil Engineering">Civil Engineering</option>
                            <option value="Business Administration">Business Administration</option>
                            <option value="Basic Sciences">Basic Sciences</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="phone" class="form-label fw-semibold">Phone Number</label>
                        <input type="tel" class="form-control bg-light" id="phone" name="phone" placeholder="9876543210">
                    </div>
                </div>

                <div class="mb-4">
                    <label for="password" class="form-label fw-semibold">Choose Password <span class="text-danger">*</span></label>
                    <input type="password" class="form-control bg-light" id="password" name="password" placeholder="••••••••" required>
                    <div class="form-text">Password should be at least 6 characters.</div>
                </div>

                <button type="submit" name="register_submit" class="btn btn-primary w-100 py-2 fs-6 mb-3">
                    <i class="bi bi-check-circle-fill me-2"></i> Register Account
                </button>
            </form>

            <div class="text-center border-top pt-3">
                <p class="small text-muted mb-0">Already have an account? <a href="index.php" class="text-primary fw-bold text-decoration-none">Sign In</a></p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
