<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils/response.php';
// require_once __DIR__ . '/../utils/auth_guard.php'; // temporarily commented for testing

// $reader = require_reader_auth(); // skip auth for now

// Optional query params
$area   = $_GET['area'] ?? null;
$status = $_GET['status'] ?? null;
$search = $_GET['search'] ?? null;

// Use LEFT JOIN to include all meters
$sql = '
SELECT 
    m.MeterID,
    m.MeterNumber,
    m.Area,
    m.Status AS MeterStatus,
    c.ConsumerID,
    c.FirstName,
    c.LastName,
    c.Email,
    c.ContactNumber,
    c.Address,
    c.Status AS ConsumerStatus
FROM meter m
LEFT JOIN consumer c ON m.ConsumerID = c.ConsumerID
WHERE LOWER(m.Status) = "active"
';

$params = [];
$types = '';

if ($area) {
    $sql .= ' AND m.Area = ?';
    $params[] = $area;
    $types .= 's';
}

if ($status) {
    $sql .= ' AND c.Status = ?';
    $params[] = $status;
    $types .= 's';
}

if ($search) {
    $sql .= ' AND (
        c.FirstName LIKE ? OR
        c.LastName LIKE ? OR
        c.Email LIKE ? OR
        m.MeterNumber LIKE ?
    )';
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= 'ssss';
}

$sql .= ' ORDER BY m.MeterNumber ASC';

// Prepare and execute
$stmt = $conn->prepare($sql);
if (!$stmt) res_error('Prepare failed: ' . $conn->error, 500);

if (count($params) > 0) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$meters = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'meters' => $meters
]);
exit;

