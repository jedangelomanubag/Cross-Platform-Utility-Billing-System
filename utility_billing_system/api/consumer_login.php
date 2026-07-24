<?php
header('Content-Type: application/json');
require_once '../includes/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$email = sanitize($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Email and password are required']);
    exit();
}

// Query to find consumer by email
$stmt = $conn->prepare("SELECT ConsumerID, FirstName, LastName, Email, Password, Status FROM consumer WHERE Email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
    exit();
}

$consumer = $result->fetch_assoc();

// Verify password
if (!password_verify($password, $consumer['Password'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
    exit();
}

// Check if account is active
if ($consumer['Status'] !== 'active') {
    echo json_encode(['success' => false, 'message' => 'Your account is not active. Please contact administrator.']);
    exit();
}

// Set session variables
$_SESSION['consumer_id'] = $consumer['ConsumerID'];
$_SESSION['consumer_name'] = $consumer['FirstName'] . ' ' . $consumer['LastName'];
$_SESSION['consumer_email'] = $consumer['Email'];
$_SESSION['user_type'] = 'consumer';

echo json_encode([
    'success' => true, 
    'message' => 'Login successful',
    'redirect' => '../consumer_portal/dashboard.php'
]);

$stmt->close();
$conn->close();
?>
