<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");

require_once "../config/db.php";

// Decode JSON input
$data = json_decode(file_get_contents("php://input"), true);
$consumer_id = isset($data['consumer_id']) ? intval($data['consumer_id']) : 0;

if ($consumer_id <= 0) {
    echo json_encode(["success" => false, "message" => "No consumer session found. Please log in first."]);
    exit;
}

// ✅ Step 1: Get all MeterIDs linked to this consumer
$stmt = $conn->prepare("SELECT MeterID FROM meter WHERE ConsumerID = ?");
$stmt->bind_param("i", $consumer_id);
$stmt->execute();
$result = $stmt->get_result();

$meter_ids = [];
while ($row = $result->fetch_assoc()) {
    $meter_ids[] = $row['MeterID'];
}
$stmt->close();

// Prepare array for notifications
$notifications = [];

// ✅ Step 2: Fetch latest billing statements for these meters
if (!empty($meter_ids)) {
    $placeholders = implode(',', array_fill(0, count($meter_ids), '?'));
    $types = str_repeat('i', count($meter_ids));

    $sql = "
        SELECT 
            b.BillingID AS id,
            c.ConsumerName AS customer_name,
            b.BillingPeriod AS period,
            b.TotalAmount AS amount,
            b.PaymentStatus AS status,
            DATE_FORMAT(b.BillingDate, '%M %d, %Y') AS date
        FROM billingstatement b
        JOIN meter m ON b.MeterID = m.MeterID
        JOIN consumer c ON m.ConsumerID = c.ConsumerID
        WHERE b.MeterID IN ($placeholders)
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$meter_ids);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $notifications[] = [
            "id" => $row['id'],
            "customer_name" => $row['customer_name'],
            "amount" => "₱" . number_format($row['amount'], 2),
            "status" => ucfirst($row['status']),
            "date" => $row['date'],
        ];
    }
    $stmt->close();
}

// ✅ Step 3: Fetch SMS notifications from consumer_inbox
$stmtSms = $conn->prepare("
    SELECT id, message, amount, status, DATE_FORMAT(date, '%M %d, %Y') AS date
    FROM consumer_inbox
    WHERE consumer_id = ?
");
$stmtSms->bind_param("i", $consumer_id);
$stmtSms->execute();
$resultSms = $stmtSms->get_result();

while ($row = $resultSms->fetch_assoc()) {
    $notifications[] = [
        "id" => "sms_" . $row['id'], // prefix to avoid id collision
        "customer_name" => "", // optional, can leave blank or "System"
        "amount" => $row['amount'] ? "₱" . number_format($row['amount'], 2) : "",
        "status" => ucfirst($row['status']),
        "date" => $row['date'],
        "message" => $row['message'], // include message text
    ];
}
$stmtSms->close();

$conn->close();

// ✅ Step 4: Sort notifications by date descending
usort($notifications, function ($a, $b) {
    return strtotime($b['date']) - strtotime($a['date']);
});

echo json_encode(["success" => true, "data" => $notifications]);
?>
