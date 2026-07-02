<?php
/**
 * Staff Portal Login
 * AI-Powered Smart Complaint & Escalation System
 */

$pageTitle = "Staff Login";
require_once __DIR__ . '/includes/auth.php';

// If user is already logged in, redirect them to their respective dashboard
if (auth_is_logged_in()) {
    $user = auth_current_user();
    if ($user['role'] === 'principal') {
        header("Location: dashboard_principal.php");
    } elseif ($user['role'] === 'hod') {
        header("Location: dashboard_hod.php");
    } else {
        header("Location: dashboard_staff.php");
    }
    exit();
}

$errorMessage = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $errorMessage = "Please enter both username and password.";
    } else {
        if (auth_login($username, $password)) {
            $user = auth_current_user();
            if ($user['role'] === 'principal') {
                header("Location: dashboard_principal.php");
            } elseif ($user['role'] === 'hod') {
                header("Location: dashboard_hod.php");
            } else {
                header("Location: dashboard_staff.php");
            }
            exit();
        } else {
            $errorMessage = "Invalid username or password. Please try again.";
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="app-container" style="max-width: 1100px; min-height: calc(100vh - 200px); display: flex; align-items: center; justify-content: center;">
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 3rem; align-items: center; width: 100%;">
        
        <!-- Left Pane: Staff Portal Greeting & generated illustration -->
        <div style="display: flex; flex-direction: column; gap: 2rem;">
            <div style="text-align: left;">
                <span style="font-size: 0.85rem; color: var(--primary); text-transform: uppercase; font-weight: 700; letter-spacing: 0.08em; display: block;">Administrative Access</span>
                <h1 style="font-size: 2.5rem; line-height: 1.2; font-weight: 800; margin-top: 0.25rem;">Staff & Management Portal</h1>
                <p style="margin-top: 1rem; font-size: 1.1rem; color: var(--text-muted);">
                    Sign in to access your department routing logs, acknowledge pending student complaints, apply override corrections, or manage SLA escalations.
                </p>
            </div>
            
            <div style="border-radius: var(--radius-md); overflow: hidden; border: 1px solid var(--border); box-shadow: var(--shadow-md); position: relative; aspect-ratio: 1.6 / 1; background-color: var(--bg-card);">
                <img src="assets/images/students.png" alt="Sri Krishna College Staff and Students" style="width: 100%; height: 100%; object-fit: cover; display: block;">
                <div style="position: absolute; bottom: 0; left: 0; right: 0; background: linear-gradient(180deg, rgba(0,0,0,0) 0%, rgba(20,20,18,0.85) 100%); padding: 1.5rem; color: white;">
                    <span style="font-size: 0.75rem; text-transform: uppercase; font-weight: 700; color: var(--primary); letter-spacing: 0.08em; display: block;">Administrative Oversight</span>
                    <strong style="font-size: 1.1rem; font-weight: 700; margin-top: 0.25rem; display: block;">Sri Krishna Arts & Science College</strong>
                </div>
            </div>
        </div>
        
        <!-- Right Pane: Login Card -->
        <div>
            <?php if (!empty($errorMessage)): ?>
                <div class="alert alert-danger flex">
                    <i data-lucide="alert-circle" style="margin-right: 0.5rem; flex-shrink: 0;"></i>
                    <?php echo htmlspecialchars($errorMessage); ?>
                </div>
            <?php endif; ?>

            <div class="card" style="padding: 2.5rem; margin-bottom: 0;">
                <div class="text-center" style="margin-bottom: 2rem;">
                    <div class="stat-icon" style="background-color: var(--primary-light); color: var(--primary); margin: 0 auto 1rem; width: 56px; height: 56px; border-radius: 50%;">
                        <i data-lucide="lock" style="width: 26px; height: 26px;"></i>
                    </div>
                    <h2 style="font-size: 1.5rem; font-weight: 700; color: var(--text-main); margin-bottom: 0.25rem;">Sign In</h2>
                    <p style="color: var(--text-muted); font-size: 0.85rem;">Enter your credentials to enter your role dashboard.</p>
                </div>

                <form action="login.php" method="POST" autocomplete="off">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <div style="position: relative;">
                            <input type="text" id="username" name="username" placeholder="e.g. staff_academic" required style="padding-left: 2.75rem;">
                            <i data-lucide="user" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); width: 18px; height: 18px; color: var(--text-muted);"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <div style="position: relative;">
                            <input type="password" id="password" name="password" placeholder="••••••••" required style="padding-left: 2.75rem;">
                            <i data-lucide="key" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); width: 18px; height: 18px; color: var(--text-muted);"></i>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block flex" style="margin-top: 1rem;">
                        <i data-lucide="log-in"></i> Sign In
                    </button>
                </form>
            </div>

            <div class="text-center" style="margin-top: 1.5rem;">
                <a href="index.php" class="flex" style="justify-content: center; gap: 0.25rem; font-size: 0.9rem;">
                    <i data-lucide="arrow-left" style="width: 16px; height: 16px;"></i> Return to Student Portal
                </a>
            </div>
        </div>
        
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
