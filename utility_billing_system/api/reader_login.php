<?php
header('Content-Type: application/json');
require_once '../includes/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$username = sanitize($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($username) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Username and password are required']);
    exit();
}

// Query to find reader by username
$stmt = $conn->prepare("SELECT ReaderID, FirstName, LastName, Username, Password, Status FROM utilityreader WHERE Username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid username or password']);
    exit();
}

$reader = $result->fetch_assoc();

// Verify password
if (!password_verify($password, $reader['Password'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid username or password']);
    exit();
}

// Check if account is active
if ($reader['Status'] !== 'active') {
    echo json_encode(['success' => false, 'message' => 'Your account is inactive. Please contact administrator.']);
    exit();
}

// Set session variables
$_SESSION['reader_id'] = $reader['ReaderID'];
$_SESSION['reader_name'] = $reader['FirstName'] . ' ' . $reader['LastName'];
$_SESSION['reader_username'] = $reader['Username'];
$_SESSION['user_type'] = 'reader';

echo json_encode([
    'success' => true, 
    'message' => 'Login successful',
    'redirect' => '../reader_portal/dashboard.php'
]);

$stmt->close();
$conn->close();
?>
