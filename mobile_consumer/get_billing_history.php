<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");

require_once "db.php";

// Get the consumer_id from query parameter or POST JSON
$data = json_decode(file_get_contents("php://input"), true);
$consumer_id = isset($data['consumer_id']) ? intval($data['consumer_id']) : 0;

if ($consumer_id <= 0) {
    echo json_encode(["success" => false, "message" => "Consumer ID is required"]);
    exit;
}

// Fetch all meters for this consumer
$stmt = $conn->prepare("SELECT MeterID FROM meter WHERE ConsumerID = ?");
$stmt->bind_param("i", $consumer_id);
$stmt->execute();
$result = $stmt->get_result();

$meter_ids = [];
while ($row = $result->fetch_assoc()) {
    $meter_ids[] = $row['MeterID'];
}
$stmt->close();

if (empty($meter_ids)) {
    echo json_encode(["success" => true, "data" => []]);
    exit;
}

// Fetch all billing statements for these meters
$placeholders = implode(',', array_fill(0, count($meter_ids), '?'));
$types = str_repeat('i', count($meter_ids));

$stmt = $conn->prepare("SELECT BillingID, BillingPeriod, TotalAmount, PaymentStatus, BillingDate, DueDate FROM billingstatement WHERE MeterID IN ($placeholders) ORDER BY BillingDate DESC");

// Bind parameters dynamically
$stmt->bind_param($types, ...$meter_ids);
$stmt->execute();
$result = $stmt->get_result();

$billingHistory = [];
while ($row = $result->fetch_assoc()) {
    $billingHistory[] = [
        "id" => $row['BillingID'],
        "period" => $row['BillingPeriod'],
        "total_amount" => "₱" . number_format($row['TotalAmount'], 2),
        "status" => ucfirst($row['PaymentStatus']),
        "billing_date" => $row['BillingDate'],
        "due_date" => $row['DueDate']
    ];
}

$stmt->close();
$conn->close();

echo json_encode(["success" => true, "data" => $billingHistory]);
?>
    