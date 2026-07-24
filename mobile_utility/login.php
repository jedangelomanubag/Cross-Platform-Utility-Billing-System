<?php
header("Content-Type: application/json");
require_once 'db.php';
// Get POST data
$input = json_decode(file_get_contents("php://input"), true);

if (!isset($input['username']) || !isset($input['password'])) {
    echo json_encode(["success" => false, "message" => "Username and password required"]);
    exit;
}

$username = trim($input['username']);
$password = $input['password'];

// Prepare and fetch reader
$stmt = $conn->prepare("SELECT ReaderID, FirstName, LastName, Username, Email, ContactNumber, Area, Status 
                        FROM utilityreader 
                        WHERE Username = ? AND Status = 'active'");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "Invalid username or account inactive"]);
    exit;
}

$reader = $result->fetch_assoc();

// Verify password
$stmt2 = $conn->prepare("SELECT Password FROM utilityreader WHERE ReaderID = ?");
$stmt2->bind_param("i", $reader['ReaderID']);
$stmt2->execute();
$passResult = $stmt2->get_result();
$passData = $passResult->fetch_assoc();

if (!password_verify($password, $passData['Password'])) {
    echo json_encode(["success" => false, "message" => "Invalid password"]);
    exit;
}

// Generate auth token
$token = bin2hex(random_bytes(32));

// Save auth token in DB
$updateStmt = $conn->prepare("UPDATE utilityreader SET AuthToken = ? WHERE ReaderID = ?");
$updateStmt->bind_param("si", $token, $reader['ReaderID']);
$updateStmt->execute();

$reader['auth_token'] = $token;

// Return success
echo json_encode([
    "success" => true,
    "message" => "Login successful",
    "reader" => $reader
]);

// Close statements
$stmt->close();
$stmt2->close();
$updateStmt->close();
$conn->close();
?>
