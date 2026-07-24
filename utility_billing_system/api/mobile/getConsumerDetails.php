<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Include database connection
require_once '../../config/db.php';

// Get consumer_id from query parameter
if (!isset($_GET['consumer_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Consumer ID is required'
    ]);
    exit;
}

$consumerId = intval($_GET['consumer_id']);

// Query to get consumer details along with meter info
$query = "
    SELECT 
        c.ConsumerID,
        c.AccountNo,
        CONCAT(c.FirstName, ' ', c.LastName) AS ConsumerName,
        c.FirstName,
        c.LastName,
        c.Email,
        c.ContactNumber,
        c.Address,
        c.Status,
        c.BillingPreference,
        m.MeterID,
        m.SerialNo,
        m.Classification,
        m.Area,
        m.LastReading,
        m.LastReadingDate
    FROM consumer c
    LEFT JOIN meter m ON c.ConsumerID = m.ConsumerID
    WHERE c.ConsumerID = $consumerId
    LIMIT 1
";

$result = mysqli_query($conn, $query);

if (!$result) {
    echo json_encode([
        'success' => false,
        'message' => 'Query error: ' . mysqli_error($conn)
    ]);
    mysqli_close($conn);
    exit;
}

$consumer = mysqli_fetch_assoc($result);

if ($consumer) {
    echo json_encode([
        'success' => true,
        'data' => $consumer
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Consumer not found'
    ]);
}

mysqli_close($conn);
?>