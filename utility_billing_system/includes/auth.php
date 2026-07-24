<?php
// Require the configuration file
require_once __DIR__ . '/config.php';

/**
 * Check if user is logged in
 * @return bool True if user is logged in, false otherwise
 */
function isLoggedIn() {
    // Check for admin session (from login.php)
    if (isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id'])) {
        // Ensure user_role is set for backward compatibility
        if (!isset($_SESSION['user_role'])) {
            $_SESSION['user_role'] = 'admin';
        }
        return true;
    }
    
    // Check for regular user session (if any)
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Require user to be logged in
 * Redirects to login page if not logged in
 */
function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        header('Location: ' . $GLOBALS['base_url'] . '/public/login.php');
        exit();
    }
}

/**
 * Require admin privileges
 * Redirects to dashboard if user is not an admin
 */
function requireAdminLogin() {
    // Debug: Log the start of requireAdminLogin
    error_log('requireAdminLogin() called');
    error_log('Session data: ' . print_r($_SESSION, true));
    
    // First check if admin is logged in (from login.php)
    if (isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id'])) {
        // Set user_role for backward compatibility
        $_SESSION['user_role'] = 'admin';
        error_log('Admin access granted via admin_id');
        return;
    }
    
    // Fall back to regular user check
    requireLogin();
    
    // Debug: Log after requireLogin
    error_log('After requireLogin()');
    error_log('User role: ' . ($_SESSION['user_role'] ?? 'not set'));
    
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        // Debug: Log the reason for redirection
        $reason = !isset($_SESSION['user_role']) ? 'User role not set' : 'User is not an admin';
        error_log('Redirecting to dashboard. Reason: ' . $reason);
        
        setFlashMessage('danger', 'You do not have permission to access this page.');
        
        // Debug: Log the redirect URL
        $redirectUrl = $GLOBALS['base_url'] . '/public/dashboard.php';
        error_log('Redirecting to: ' . $redirectUrl);
        
        header('Location: ' . $redirectUrl);
        exit();
    }
    
    // Debug: Log successful admin validation
    error_log('Admin access granted');
}

/**
 * Require reader privileges
 * Redirects to dashboard if user is not a reader
 */
function requireReaderLogin() {
    requireLogin();
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'reader') {
        setFlashMessage('danger', 'You do not have permission to access this page.');
        header('Location: ' . $GLOBALS['base_url'] . '/public/dashboard.php');
        exit();
    }
}

/**
 * Logout the current user
 */
function logout() {
    // Unset all session variables
    $_SESSION = array();
    
    // Delete the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destroy the session
    session_destroy();
    
    // Redirect to login page
    header('Location: ' . $GLOBALS['base_url'] . '/public/login.php');
    exit();
}

/**
 * Get current user's ID
 * @return int|null User ID or null if not logged in
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user's role
 * @return string|null User role or null if not logged in
 */
function getCurrentUserRole() {
    return $_SESSION['user_role'] ?? null;
}

/**
 * Check if current user has admin role
 * @return bool True if user is admin, false otherwise
 */
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Check if current user has reader role
 * @return bool True if user is a reader, false otherwise
 */
function isReader() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'reader';
}

// Auto-include this file in all pages that require authentication
// require_once __DIR__ . '/auth.php';
?>
