<?php
/**
 * Authentication and Session Helpers
 * AI-Powered Smart Complaint & Escalation System
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';

/**
 * Attempt to authenticate a user.
 * 
 * @param string $username
 * @param string $password
 * @return bool True if authentication succeeds, false otherwise
 */
function auth_login($username, $password) {
    $user = db_fetch("SELECT * FROM users WHERE username = ?", [$username]);
    
    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['department_id'] = $user['department_id'];
        $_SESSION['email'] = $user['email'];
        return true;
    }
    
    return false;
}

/**
 * Check if the user is authenticated.
 * 
 * @return bool
 */
function auth_is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Get currently logged in user info.
 * 
 * @return array|null
 */
function auth_current_user() {
    if (!auth_is_logged_in()) {
        return null;
    }
    return [
        'id'            => $_SESSION['user_id'],
        'username'      => $_SESSION['username'],
        'role'          => $_SESSION['role'],
        'department_id' => $_SESSION['department_id'],
        'email'         => $_SESSION['email']
    ];
}

/**
 * Restrict page access to logged in users of specific roles.
 * 
 * @param array $allowedRoles Array of roles (e.g. ['staff', 'hod', 'principal'])
 */
function auth_require_roles($allowedRoles) {
    if (!auth_is_logged_in()) {
        header("Location: login.php");
        exit();
    }
    
    $role = $_SESSION['role'];
    if (!in_array($role, $allowedRoles)) {
        // Redirect unauthorized users to their respective dashboards or login
        if ($role === 'principal') {
            header("Location: dashboard_principal.php");
        } elseif ($role === 'hod') {
            header("Location: dashboard_hod.php");
        } elseif ($role === 'staff') {
            header("Location: dashboard_staff.php");
        } else {
            header("Location: login.php");
        }
        exit();
    }
}

/**
 * Log out current user and redirect.
 */
function auth_logout() {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(), 
            '', 
            time() - 42000,
            $params["path"], 
            $params["domain"],
            $params["secure"], 
            $params["httponly"]
        );
    }
    session_destroy();
    header("Location: login.php");
    exit();
}
