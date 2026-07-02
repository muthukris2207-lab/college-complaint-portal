<?php
/**
 * Common Header Layout
 * AI-Powered Smart Complaint & Escalation System
 */

require_once __DIR__ . '/auth.php';
$currentUser = auth_current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . " - Smart Complaint Portal" : "Smart Complaint Portal"; ?></title>
    
    <!-- SEO Meta Tags -->
    <meta name="description" content="AI-Powered Smart Complaint and Escalation System for modern college portals. Quick resolution, AI routing, and transparent status tracking.">
    <meta name="robots" content="index, follow">
    
    <!-- Styling -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <!-- Lucide icons from CDN for a clean interface -->
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body>
    <header class="app-header">
        <div class="header-inner">
            <a href="index.php" class="logo">
                <i data-lucide="shield-alert" style="color: var(--primary);"></i>
                <span>SmartComplaint</span>
            </a>
            
            <nav class="nav-links">
                <?php if (!$currentUser): ?>
                    <a href="index.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : ''; ?>">Submit Complaint</a>
                    <a href="track.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'track.php') ? 'active' : ''; ?>">Track Status</a>
                    <a href="login.php" class="nav-link btn btn-secondary flex" style="padding: 0.4rem 1rem;">
                        <i data-lucide="log-in" style="width: 16px; height: 16px;"></i> Staff Login
                    </a>
                <?php else: ?>
                    <!-- Logged in staff menu -->
                    <?php if ($currentUser['role'] === 'staff'): ?>
                        <a href="dashboard_staff.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard_staff.php') ? 'active' : ''; ?>">Staff Dashboard</a>
                    <?php elseif ($currentUser['role'] === 'hod'): ?>
                        <a href="dashboard_hod.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard_hod.php') ? 'active' : ''; ?>">HOD Dashboard</a>
                    <?php elseif ($currentUser['role'] === 'principal'): ?>
                        <a href="dashboard_principal.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard_principal.php') ? 'active' : ''; ?>">Principal Dashboard</a>
                    <?php endif; ?>
                    
                    <div class="flex" style="gap: 1rem; border-left: 1px solid var(--border); padding-left: 1rem;">
                        <span class="user-meta" style="font-size: 0.85rem; text-align: right;">
                            <strong style="display: block; color: var(--text-main);"><?php echo htmlspecialchars($currentUser['username']); ?></strong>
                            <span style="color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase;"><?php echo htmlspecialchars($currentUser['role']); ?></span>
                        </span>
                        <a href="logout.php" class="btn btn-danger flex" style="padding: 0.4rem 0.8rem; font-size: 0.85rem;" title="Logout">
                            <i data-lucide="log-out" style="width: 14px; height: 14px;"></i>
                        </a>
                    </div>
                <?php endif; ?>
            </nav>
        </div>
    </header>
    <main>
