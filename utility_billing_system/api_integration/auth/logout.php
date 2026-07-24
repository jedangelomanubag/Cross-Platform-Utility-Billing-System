<?php
// api_integration/auth/logout.php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils/response.php';

header('Content-Type: application/json');

$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? '';

if (!$authHeader) {
    sendResponse(false, "No token provided");
    exit;
}

$token = str_replace('Bearer ', '', $authHeader);

// Remove token from utilityreader table
mysqli_query($conn, "UPDATE utilityreader SET AuthToken=NULL WHERE AuthToken='$token'");
sendResponse(true, "Logout successful");
