<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Function to check if admin is logged in
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

// Function to redirect if not logged in
function requireAdminLogin() {
    if (!isAdminLoggedIn()) {
        header("Location: /utility_billing_system/public/login.php");
        exit();
    }
}

// Function to sanitize input
function sanitizeInput($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return mysqli_real_escape_string($conn, $data);
}

// Function to display alert messages
function displayAlert($type, $message) {
    return '<div class="alert alert-' . $type . ' alert-dismissible fade show" role="alert">
        ' . $message . '
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>';
}

// Function to log admin actions
function logAdminAction($adminId, $action) {
    global $conn;
    $adminId = (int)$adminId;
    $action = mysqli_real_escape_string($conn, $action);
    $ip = $_SERVER['REMOTE_ADDR'];
    
    // Using prepared statement to prevent SQL injection
    $query = "INSERT INTO adminlogs (AdminID, Action, IPAddress, Timestamp) 
              VALUES (?, ?, ?, NOW())";
    
    $stmt = mysqli_prepare($conn, $query);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'iss', $adminId, $action, $ip);
        $result = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $result;
    }
    
    error_log("Failed to prepare statement: " . mysqli_error($conn));
    return false;
}
?>
