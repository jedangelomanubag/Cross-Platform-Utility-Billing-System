<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

include 'db.php'; // adjust path

$consumerID = $_GET['consumerID'] ?? null;

if (!$consumerID) {
    echo json_encode(['success' => false, 'message' => 'Missing consumerID']);
    exit;
}

// Get MeterID and LastReading
$stmt = $conn->prepare("SELECT MeterID, LastReading, LastReadingDate FROM meter WHERE ConsumerID = ? LIMIT 1");
$stmt->bind_param("i", $consumerID);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Meter not found']);
    exit;
}

$meter = $result->fetch_assoc();

echo json_encode([
    'success' => true,
    'MeterID' => $meter['MeterID'],
    'LastReading' => floatval($meter['LastReading']),
    'LastReadingDate' => $meter['LastReadingDate']
]);

$conn->close();
