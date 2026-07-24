<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

require_once 'db.php';

// Test database connection first
if (!$conn) {
    echo json_encode(["success" => false, "message" => "Database connection failed: " . mysqli_connect_error()]);
    exit;
}

// Read JSON input safely
$input = file_get_contents("php://input");
error_log("Raw input: " . $input);

$data = json_decode($input, true);

if (!$data) {
    echo json_encode(["success" => false, "message" => "Invalid input. JSON decode failed.", "raw_input" => $input]);
    exit;
}

// Extract required fields
$consumerID = $data['consumerID'] ?? null;
$readerID = $data['readerID'] ?? null;
$previousReading = $data['previousReading'] ?? null;
$currentReading = $data['currentReading'] ?? null;

if (!$consumerID || !$readerID || $previousReading === null || $currentReading === null) {
    echo json_encode([
        "success" => false, 
        "message" => "Missing required fields.",
        "received" => [
            "consumerID" => $consumerID,
            "readerID" => $readerID,
            "previousReading" => $previousReading,
            "currentReading" => $currentReading
        ]
    ]);
    exit;
}

// Test response
echo json_encode([
    "success" => true,
    "message" => "Test successful - data received correctly",
    "received_data" => $data
]);

$conn->close();
?>
