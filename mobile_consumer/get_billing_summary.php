<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");

require_once "db.php";

// Get consumer_id from POST JSON
$data = json_decode(file_get_contents("php://input"), true);
$consumer_id = isset($data['consumer_id']) ? intval($data['consumer_id']) : 0;

if ($consumer_id <= 0) {
    echo json_encode(["success" => false, "message" => "Consumer ID is required"]);
    exit;
}

// Get all meters for this consumer
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
    echo json_encode(["success" => true, "data" => ["paid" => 0, "unpaid" => 0, "overdue" => 0]]);
    exit;
}

// Fetch billing summary
$placeholders = implode(',', array_fill(0, count($meter_ids), '?'));
$types = str_repeat('i', count($meter_ids));

$stmt = $conn->prepare("
    SELECT 
        PaymentStatus, SUM(TotalAmount) as total 
    FROM billingstatement 
    WHERE MeterID IN ($placeholders)
    GROUP BY PaymentStatus
");

// Bind dynamic params
$stmt->bind_param($types, ...$meter_ids);
$stmt->execute();
$result = $stmt->get_result();

$summary = ["paid" => 0, "unpaid" => 0, "overdue" => 0];
while ($row = $result->fetch_assoc()) {
    $status = strtolower($row['PaymentStatus']);
    if ($status === 'paid') $summary['paid'] = floatval($row['total']);
    elseif ($status === 'unpaid') $summary['unpaid'] = floatval($row['total']);
    elseif ($status === 'overdue') $summary['overdue'] = floatval($row['total']);
}

$stmt->close();
$conn->close();

echo json_encode(["success" => true, "data" => $summary]);
?>
