<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include configuration and authentication functions
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

// Use the existing logout function
try {
    // Store any success message before logout
    $_SESSION['success'] = "You have been successfully logged out.";
    
    // Call the logout function from auth.php
    logout();
    
    // Redirect to login page
    header('Location: login.php');
    exit();
} catch (Exception $e) {
    // If there's an error, still try to destroy the session and redirect
    session_destroy();
    header('Location: login.php?error=logout_failed');
    exit();
}
