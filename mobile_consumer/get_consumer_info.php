<?php
header('Content-Type: application/json');

require_once "db.php";

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['consumer_id'])) {
    echo json_encode(['success' => false, 'message' => 'Consumer ID is required']);
    exit;
}

$consumer_id = intval($data['consumer_id']);

try {
    // Fetch consumer info
    $stmt = $conn->prepare("SELECT ConsumerID, AccountNo, FirstName, LastName, Email, ContactNumber, Address 
                            FROM consumer 
                            WHERE ConsumerID = ?");
    $stmt->bind_param("i", $consumer_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Consumer not found']);
        exit;
    }

    $consumer = $result->fetch_assoc();

    // Fetch meter info
    $stmt2 = $conn->prepare("SELECT MeterID, SerialNo, Area, Classification 
                             FROM meter 
                             WHERE ConsumerID = ?");
    $stmt2->bind_param("i", $consumer_id);
    $stmt2->execute();
    $meter_result = $stmt2->get_result();

    $meter = $meter_result->fetch_assoc();
    $consumer['ConsumerMeter'] = $meter['SerialNo'] ?? 'N/A';
    $consumer['MeterArea'] = $meter['Area'] ?? 'N/A';
    $consumer['MeterClassification'] = $meter['Classification'] ?? 'N/A';

    echo json_encode(['success' => true, 'data' => $consumer]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>
