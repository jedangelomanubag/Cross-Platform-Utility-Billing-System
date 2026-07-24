<?php
header('Content-Type: application/json');
require_once '../../config/db.php';

if (!isset($_GET['serial_no']) || trim($_GET['serial_no']) === '') {
    echo json_encode(['success' => false, 'message' => 'Serial number is required']);
    exit;
}

$serial_no = trim($_GET['serial_no']);

$sql = "
    SELECT 
        m.MeterID,
        m.SerialNo,
        m.Area,
        m.Classification,
        m.LastReading,
        m.LastReadingDate,
        m.Status,
        m.ConsumerID,
        c.AccountNo,
        CONCAT(c.FirstName, ' ', c.LastName) AS ConsumerName,
        c.Address,
        c.ContactNumber,
        c.Email
    FROM meter m
    LEFT JOIN consumer c ON m.ConsumerID = c.ConsumerID
    WHERE m.SerialNo LIKE '%$serial_no%'
      AND m.Status = 'active'
";

$result = $conn->query($sql);

if (!$result) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $conn->error
    ]);
    exit;
}

$meters = [];
while ($row = $result->fetch_assoc()) {
    $meters[] = $row;
}

if (count($meters) > 0) {
    echo json_encode(['success' => true, 'data' => $meters]);
} else {
    echo json_encode(['success' => false, 'message' => 'No active meter found for this serial number.']);
}

$conn->close();
?>
