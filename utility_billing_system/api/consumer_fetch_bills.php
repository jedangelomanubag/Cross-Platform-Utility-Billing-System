<?php
header('Content-Type: application/json');
require_once '../includes/db_connect.php';

// Check if consumer is logged in
if (!isset($_SESSION['consumer_id']) || $_SESSION['user_type'] !== 'consumer') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$consumer_id = $_SESSION['consumer_id'];

// Get all meters for this consumer
$stmt = $conn->prepare("SELECT MeterID FROM meter WHERE ConsumerID = ?");
$stmt->bind_param("i", $consumer_id);
$stmt->execute();
$result = $stmt->get_result();

$meter_ids = [];
while ($row = $result->fetch_assoc()) {
    $meter_ids[] = $row['MeterID'];
}

if (empty($meter_ids)) {
    echo json_encode(['success' => true, 'bills' => []]);
    exit();
}

// Get billing statements for all meters
$placeholders = implode(',', array_fill(0, count($meter_ids), '?'));
$query = "SELECT b.*, m.MeterNumber 
          FROM billingstatement b 
          JOIN meter m ON b.MeterID = m.MeterID 
          WHERE b.MeterID IN ($placeholders) 
          ORDER BY b.BillingDate DESC";

$stmt = $conn->prepare($query);
$types = str_repeat('i', count($meter_ids));
$stmt->bind_param($types, ...$meter_ids);
$stmt->execute();
$result = $stmt->get_result();

$bills = [];
while ($row = $result->fetch_assoc()) {
    $bills[] = $row;
}

echo json_encode(['success' => true, 'bills' => $bills]);

$stmt->close();
$conn->close();
?>
