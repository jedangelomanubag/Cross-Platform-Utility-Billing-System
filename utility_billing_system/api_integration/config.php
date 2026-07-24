<?php
// config.php (mysqli version)
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Database settings — follow the provided SQL dump exactly
define('DB_HOST', 'localhost');
define('DB_NAME', 'utility_billing_system');
define('DB_USER', 'root');
define('DB_PASS', '');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_errno) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed: ' . $conn->connect_error]);
    exit;
}
$conn->set_charset('utf8mb4');


// helper to read JSON body
function json_input() {
    $data = file_get_contents('php://input');
    return $data ? json_decode($data, true) : [];
}
?>
