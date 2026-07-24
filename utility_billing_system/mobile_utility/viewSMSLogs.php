<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

require_once 'db.php';

// Get SMS logs from pending_readings table (consumer_id = 0 indicates SMS logs)
$query = "SELECT * FROM pending_readings WHERE consumer_id = 0 ORDER BY date_created DESC LIMIT 50";
$result = mysqli_query($conn, $query);

$smsLogs = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        // Format as SMS log entry
        $smsLogs[] = [
            'id' => $row['id'],
            'recipient' => 'SMS Log Entry',
            'message' => "Billing SMS - Previous: {$row['previous_reading']}, Current: {$row['current_reading']}, Amount: ₱{$row['total_amount']}",
            'sender_id' => 'UtilityBilling',
            'status' => 'sent',
            'created_at' => $row['date_created'],
            'consumer_id' => $row['consumer_id'],
            'reader_id' => $row['reader_id'],
            'previous_reading' => $row['previous_reading'],
            'current_reading' => $row['current_reading'],
            'total_amount' => $row['total_amount'],
            'consumption' => $row['consumption']
        ];
    }
    echo json_encode([
        "success" => true,
        "data" => $smsLogs,
        "count" => count($smsLogs),
        "message" => "SMS logs retrieved from pending_readings table"
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Error fetching SMS logs: " . mysqli_error($conn)
    ]);
}

mysqli_close($conn);
?>
