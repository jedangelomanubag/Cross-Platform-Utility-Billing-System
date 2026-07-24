<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");

require_once "db.php";

function jsonResponse($success, $data = null, $message = '') {
    echo json_encode([
        "success" => $success,
        "data" => $data,
        "message" => $message
    ]);
    exit;
}

try {
    $data = json_decode(file_get_contents("php://input"), true);
    $consumer_id = isset($data["consumer_id"]) ? intval($data["consumer_id"]) : 0;

    if ($consumer_id <= 0) {
        jsonResponse(false, null, "Missing or invalid consumer ID");
    }

    // Step 1: Get all meters for this consumer
    $stmt = $conn->prepare("SELECT MeterID FROM meter WHERE ConsumerID = ?");
    $stmt->bind_param("i", $consumer_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $meter_ids = [];
    while ($row = $result->fetch_assoc()) {
        $meter_ids[] = $row["MeterID"];
    }
    $stmt->close();

    if (empty($meter_ids)) {
        jsonResponse(true, [], "No meter found for this consumer");
    }

    // Step 2: Get billing data per BillingPeriod
    $placeholders = implode(",", array_fill(0, count($meter_ids), "?"));
    $types = str_repeat("i", count($meter_ids));

    $sql = "
        SELECT BillingPeriod, 
               SUM(Consumption) AS total_consumption
        FROM billingstatement
        WHERE MeterID IN ($placeholders)
        GROUP BY BillingPeriod
        ORDER BY STR_TO_DATE(BillingPeriod, '%M %Y') ASC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$meter_ids);
    $stmt->execute();
    $result = $stmt->get_result();

    $analytics = [];
    $prev_total = null;

    while ($row = $result->fetch_assoc()) {
        $period = $row["BillingPeriod"];
        $total = floatval($row["total_consumption"]);
        $difference = $prev_total !== null ? $total - $prev_total : 0;
        $analytics[] = [
            "period" => $period,
            "total_consumption" => $total,
            "difference" => $difference
        ];
        $prev_total = $total;
    }

    $stmt->close();
    $conn->close();

    jsonResponse(true, $analytics);
} catch (Exception $e) {
    jsonResponse(false, null, "Server error: " . $e->getMessage());
}
?>
