<?php
require_once '../../config/db.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'No data received']);
    exit;
}

$consumerID = $data['consumerID'] ?? '';
$prev = $data['previousReading'] ?? '';
$curr = $data['currentReading'] ?? '';
$total = $data['totalAmount'] ?? 0;
$readerID = $data['readerID'] ?? 1;
$date = date('Y-m-d H:i:s');

if (!$consumerID || $prev === '' || $curr === '') {
    echo json_encode(['success' => false, 'message' => 'Missing fields']);
    exit;
}

$stmt = $conn->prepare("INSERT INTO pending_readings (consumer_id, previous_reading, current_reading, total_amount, reader_id, date_created) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("idddis", $consumerID, $prev, $curr, $total, $readerID, $date);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Reading stored offline in DB']);
} else {
    echo json_encode(['success' => false, 'message' => $stmt->error]);
}
?>
