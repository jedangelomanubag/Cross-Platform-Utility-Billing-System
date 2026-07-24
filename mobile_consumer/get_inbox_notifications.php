<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");

require_once "db.php";

// Decode JSON input
$data = json_decode(file_get_contents("php://input"), true);
$consumer_id = isset($data['consumer_id']) ? intval($data['consumer_id']) : 0;

if ($consumer_id <= 0) {
    echo json_encode(["success" => false, "message" => "No consumer session found. Please log in first."]);
    exit;
}

// Prepare array for notifications
$notifications = [];

// ✅ Fetch SMS notifications from pending_readings (SMS logs) only
$stmtSms = $conn->prepare("
    SELECT id, consumer_id, reader_id, previous_reading, current_reading, total_amount, consumption, 
           DATE_FORMAT(date_created, '%M %d, %Y') AS date
    FROM pending_readings
    WHERE consumer_id = ?
");
$stmtSms->bind_param("i", $consumer_id);
$stmtSms->execute();
$resultSms = $stmtSms->get_result();

while ($row = $resultSms->fetch_assoc()) {
    $notifications[] = [
        "id" => "sms_" . $row['id'], // prefix to avoid id collision
        "customer_name" => "SMS Notification",
        "amount" => $row['total_amount'] ? "₱" . number_format($row['total_amount'], 2) : "₱0.00",
        "status" => "Delivered", // SMS status
        "date" => $row['date'],
        "message" => "Meter reading: " . $row['previous_reading'] . " → " . $row['current_reading'] . 
                   " (Consumption: " . $row['consumption'] . ")",
        "type" => "sms", // add type identifier
        // Add individual fields for easier access
        "previous_reading" => $row['previous_reading'],
        "current_reading" => $row['current_reading'],
        "consumption" => $row['consumption'],
        "total_amount" => $row['total_amount'],
        "reader_id" => $row['reader_id'],
    ];
}
$stmtSms->close();

$conn->close();

// ✅ Sort notifications by date descending
usort($notifications, function ($a, $b) {
    return strtotime($b['date']) - strtotime($a['date']);
});

echo json_encode(["success" => true, "data" => $notifications]);
?>
