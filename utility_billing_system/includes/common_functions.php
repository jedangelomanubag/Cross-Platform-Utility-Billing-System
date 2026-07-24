<?php
/**
 * Common functions used across the application
 */

/**
 * Redirect to a different page
 * 
 * @param string $url The URL to redirect to
 * @return void
 */
function redirect($url) {
    header("Location: " . $url);
    exit();
}

/**
 * Sanitize user input
 * 
 * @param string $data The input to sanitize
 * @return string The sanitized input
 */
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Check if user is logged in
 * 
 * @return bool True if user is logged in, false otherwise
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Require user to be logged in
 * Redirects to login page if not logged in
 * 
 * @return void
 */
function require_login() {
    if (!is_logged_in()) {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        redirect('login.php');
    }
}

/**
 * Display a flash message
 * 
 * @param string $message The message to display
 * @param string $type The type of message (success, error, warning, info)
 * @return void
 */
function set_flash_message($message, $type = 'info') {
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = [];
    }
    $_SESSION['flash_messages'][] = [
        'message' => $message,
        'type' => $type
    ];
}

/**
 * Display flash messages
 * 
 * @return void
 */
function display_flash_messages() {
    if (empty($_SESSION['flash_messages'])) {
        return;
    }

    foreach ($_SESSION['flash_messages'] as $flash) {
        echo '<div class="alert alert-' . htmlspecialchars($flash['type']) . ' alert-dismissible fade show" role="alert">';
        echo htmlspecialchars($flash['message']);
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        echo '</div>';
    }
    
    // Clear the flash messages after displaying
    $_SESSION['flash_messages'] = [];
}

/**
 * Generate a CSRF token
 * 
 * @return string The generated token
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 * 
 * @param string $token The token to verify
 * @return bool True if token is valid, false otherwise
 */
function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Format date to a more readable format
 * 
 * @param string $date The date to format
 * @param string $format The format to use (default: F j, Y)
 * @return string The formatted date
 */
function format_date($date, $format = 'F j, Y') {
    $date = new DateTime($date);
    return $date->format($format);
}

/**
 * Format currency
 * 
 * @param float $amount The amount to format
 * @param string $currency The currency symbol (default: ₱)
 * @return string The formatted currency
 */
function format_currency($amount, $currency = '₱') {
    return $currency . number_format($amount, 2, '.', ',');
}
?>
