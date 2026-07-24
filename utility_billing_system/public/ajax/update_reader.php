<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../config/db.php';
require_once '../../includes/auth.php';

// Function to log admin actions
function logAdminAction($adminId, $action) {
    global $conn;
    
    $adminId = intval($adminId);
    $action = $conn->real_escape_string($action);
    $ip = $_SERVER['REMOTE_ADDR'];
    
    // Using direct query since we don't see user_agent column in the table
    $sql = "INSERT INTO adminlogs (AdminID, Action, IPAddress) 
            VALUES ($adminId, '$action', '$ip')";
    
    if (!$conn->query($sql)) {
        // Log error but don't fail the main operation
        error_log("Failed to log admin action: " . $conn->error);
    }
}

// Function to send JSON response
function sendJsonResponse($success, $message = '', $data = []) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

// Ensure the request is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    sendJsonResponse(false, 'Method not allowed');
}

// Ensure admin is logged in
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    sendJsonResponse(false, 'Unauthorized');
}

// Get and validate input
$readerId = filter_input(INPUT_POST, 'reader_id', FILTER_VALIDATE_INT);
$firstName = trim($_POST['first_name'] ?? '');
$lastName = trim($_POST['last_name'] ?? '');
$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$contactNumber = trim($_POST['contact_number'] ?? '');
$area = trim($_POST['area'] ?? '');
$status = ($_POST['status'] ?? '') === 'active' ? 'active' : 'inactive';

// Validate required fields
$requiredFields = [
    'reader_id' => $readerId,
    'first_name' => $firstName,
    'last_name' => $lastName,
    'username' => $username,
    'email' => $email
];

$missingFields = [];
foreach ($requiredFields as $field => $value) {
    if (empty($value)) {
        $missingFields[] = str_replace('_', ' ', $field);
    }
}

if (!empty($missingFields)) {
    $message = 'The following fields are required: ' . implode(', ', $missingFields);
    http_response_code(400);
    sendJsonResponse(false, $message);
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    sendJsonResponse(false, 'Please enter a valid email address');
}

// Check if email already exists for another reader
try {
    $checkEmail = $conn->prepare("SELECT ReaderID FROM utilityreader WHERE Email = ? AND ReaderID != ?");
    if (!$checkEmail) {
        throw new Exception('Database prepare failed: ' . $conn->error);
    }
    
    $checkEmail->bind_param('si', $email, $readerId);
    if (!$checkEmail->execute()) {
        throw new Exception('Database execute failed: ' . $checkEmail->error);
    }
    
    $result = $checkEmail->get_result();
    if ($result->num_rows > 0) {
        http_response_code(400);
        sendJsonResponse(false, 'Email already in use by another reader');
    }
} catch (Exception $e) {
    error_log('Email check error: ' . $e->getMessage());
    http_response_code(500);
    sendJsonResponse(false, 'Error checking email availability');
}

// Check if username already exists for another reader
try {
    $checkUsername = $conn->prepare("SELECT ReaderID FROM utilityreader WHERE Username = ? AND ReaderID != ?");
    if (!$checkUsername) {
        throw new Exception('Database prepare failed: ' . $conn->error);
    }
    
    $checkUsername->bind_param('si', $username, $readerId);
    if (!$checkUsername->execute()) {
        throw new Exception('Database execute failed: ' . $checkUsername->error);
    }
    
    $result = $checkUsername->get_result();
    if ($result->num_rows > 0) {
        http_response_code(400);
        sendJsonResponse(false, 'Username already in use by another reader');
    }
} catch (Exception $e) {
    error_log('Username check error: ' . $e->getMessage());
    http_response_code(500);
    sendJsonResponse(false, 'Error checking username availability');
}

// Update the reader in the database
try {
    // Log the data we're trying to update
    error_log('Updating reader with data: ' . print_r([
        'reader_id' => $readerId,
        'first_name' => $firstName,
        'last_name' => $lastName,
        'username' => $username,
        'email' => $email,
        'contact_number' => $contactNumber,
        'area' => $area,
        'status' => $status
    ], true));

    $sql = "UPDATE utilityreader SET 
            FirstName = ?, 
            LastName = ?, 
            Username = ?,
            Email = ?, 
            ContactNumber = ?, 
            Area = ?, 
            Status = ?,
            CreatedAt = NOW()
            WHERE ReaderID = ?";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log('Prepare failed: ' . $conn->error);
        throw new Exception('Database error. Please try again.');
    }
    
    $bindResult = $stmt->bind_param('sssssssi', 
        $firstName,
        $lastName,
        $username,
        $email,
        $contactNumber,
        $area,
        $status,
        $readerId
    );
    
    if (!$bindResult) {
        error_log('Bind param failed: ' . $stmt->error);
        throw new Exception('Database error. Please try again.');
    }
    
    $executeResult = $stmt->execute();
    if (!$executeResult) {
        error_log('Execute failed: ' . $stmt->error);
        throw new Exception('Failed to save changes. Please try again.');
    }
    
    if ($stmt->affected_rows > 0) {
        // Log the action
        logAdminAction($_SESSION['admin_id'], "Updated reader: $firstName $lastName (ID: $readerId)");
        sendJsonResponse(true, 'Reader updated successfully');
    } else if ($stmt->affected_rows === 0) {
        // Check if the reader exists
        $check = $conn->prepare("SELECT ReaderID FROM utilityreader WHERE ReaderID = ?");
        $check->bind_param('i', $readerId);
        $check->execute();
        $result = $check->get_result();
        
        if ($result->num_rows === 0) {
            error_log("Reader with ID $readerId not found");
            sendJsonResponse(false, 'Reader not found');
        } else {
            error_log("No changes made to reader ID $readerId");
            sendJsonResponse(true, 'No changes were made');
        }
    } else {
        error_log('Unexpected affected_rows value: ' . $stmt->affected_rows);
        throw new Exception('An unexpected error occurred');
    }
    
} catch (Exception $e) {
    error_log('Update reader error: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    http_response_code(500);
    sendJsonResponse(false, $e->getMessage());
}

$stmt->close();
$conn->close();
