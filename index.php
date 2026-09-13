<?php
// index.php - Login Page & Portal Landing

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

// Redirect logged in users directly to their dashboard
if (isLoggedIn()) {
    $user = getCurrentUser();
    if ($user['role'] === 'student') redirect('student_dashboard.php');
    if ($user['role'] === 'staff') redirect('staff_dashboard.php');
    if ($user['role'] === 'admin') redirect('admin_dashboard.php');
}

// Handle Login Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_submit'])) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        setFlashMsg('danger', 'Please provide both email address and password.');
    } else {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Set session details
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_department'] = $user['department'];

            setFlashMsg('success', 'Welcome back, ' . htmlspecialchars($user['name']) . '!');

            if ($user['role'] === 'student') redirect('student_dashboard.php');
            if ($user['role'] === 'staff') redirect('staff_dashboard.php');
            if ($user['role'] === 'admin') redirect('admin_dashboard.php');
        } else {
            setFlashMsg('danger', 'Invalid email address or password.');
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="row align-items-center py-4">
    <!-- Left Column: Hero Information -->
    <div class="col-lg-7 mb-4 mb-lg-0">
        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 rounded-pill fw-semibold mb-3">
            <i class="bi bi-shield-lock-fill me-1"></i> Campus Operations & Maintenance
        </span>
        <h1 class="display-4 fw-bold mb-3 text-dark">Smart College Complaint & Maintenance System</h1>
        <p class="lead text-secondary mb-4">
            Report infrastructure, IT, electrical, or plumbing issues effortlessly. Track repair progress in real-time with full student privacy support.
        </p>

        <div class="row g-3 mb-4">
            <div class="col-sm-6">
                <div class="d-flex align-items-start p-3 bg-white border rounded-3 shadow-sm">
                    <div class="bg-primary text-white p-2 rounded-3 me-3">
                        <i class="bi bi-incognito fs-4"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-1">Anonymous Reporting</h6>
                        <p class="small text-muted mb-0">Hide student identity to prevent bias during repairs.</p>
                    </div>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="d-flex align-items-start p-3 bg-white border rounded-3 shadow-sm">
                    <div class="bg-success text-white p-2 rounded-3 me-3">
                        <i class="bi bi-clock-history fs-4"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-1">Live Status Timeline</h6>
                        <p class="small text-muted mb-0">Track ticket stage from Pending to Resolution.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Login Box -->
    <div class="col-lg-5">
        <div class="card card-custom shadow-lg p-4 p-md-5">
            <div class="text-center mb-4">
                <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3 shadow" style="width: 54px; height: 54px;">
                    <i class="bi bi-lock-fill fs-3"></i>
                </div>
                <h3 class="fw-bold">Sign In to Portal</h3>
                <p class="text-muted small">Select your role or sign in with your email</p>
            </div>

            <form action="index.php" method="POST">
                <div class="mb-3">
                    <label for="email" class="form-label fw-semibold">Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-envelope text-muted"></i></span>
                        <input type="email" class="form-field form-control bg-light border-start-0" id="email" name="email" placeholder="student@college.edu" required>
                    </div>
                </div>

                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label for="password" class="form-label fw-semibold mb-0">Password</label>
                    </div>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-key text-muted"></i></span>
                        <input type="password" class="form-control bg-light border-start-0" id="password" name="password" placeholder="••••••••" required>
                    </div>
                </div>

                <button type="submit" name="login_submit" class="btn btn-primary w-100 py-2 fs-6 mb-3">
                    <i class="bi bi-box-arrow-in-right me-2"></i> Log In
                </button>
            </form>

            <div class="text-center">
                <p class="small text-muted mb-3">New Student? <a href="register.php" class="text-primary fw-bold text-decoration-none">Create Account</a></p>
            </div>

           
        </div>
    </div>
</div>

<script>
function fillLogin(email) {
    document.getElementById('email').value = email;
    document.getElementById('password').value = 'Password123';
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
