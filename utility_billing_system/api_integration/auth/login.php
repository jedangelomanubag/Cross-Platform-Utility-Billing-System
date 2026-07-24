<?php
// api_integration/auth/login.php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils/response.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$username = $data['username'] ?? '';
$password = $data['password'] ?? '';

if (!$username || !$password) {
    sendResponse(false, "Username and password are required");
    exit;
}

// Check utilityreader table
$sql = "SELECT ReaderID, Username, Password, FirstName, LastName FROM utilityreader WHERE Username='$username' LIMIT 1";
$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);

if ($row && password_verify($password, $row['Password'])) {
    // Generate token
    $token = bin2hex(random_bytes(16));
    mysqli_query($conn, "UPDATE utilityreader SET AuthToken='$token' WHERE ReaderID={$row['ReaderID']}");
    
    sendResponse(true, "Login successful", [
        'token' => $token,
        'reader_id' => $row['ReaderID'],
        'name' => $row['FirstName'].' '.$row['LastName'],
        'username' => $row['Username']
    ]);
} else {
    sendResponse(false, "Invalid credentials");
}
