<?php
// Disable error reporting for clean JSON output
error_reporting(0);
ini_set('display_errors', 0);

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST");

require_once 'db.php';

// Read JSON input
$input = file_get_contents("php://input");
$data = json_decode($input, true);

if (!$data) {
    echo json_encode(["success" => false, "message" => "Invalid input"]);
    exit;
}

$phoneNumber = $data['recipient'] ?? '';
$message = $data['message'] ?? '';
$senderId = $data['sender_id'] ?? 'UtilityBilling';
$consumerId = $data['consumer_id'] ?? 0;
$readerId = $data['reader_id'] ?? 1;

// Verify consumer exists and has valid phone number
if ($consumerId > 0) {
    $verifyQuery = "SELECT ConsumerID, ContactNumber FROM consumer WHERE ConsumerID = ? AND ContactNumber IS NOT NULL AND ContactNumber != '' LIMIT 1";
    $stmt = $conn->prepare($verifyQuery);
    $stmt->bind_param("i", $consumerId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode([
            "success" => false,
            "message" => "Consumer not found or no phone number on record"
        ]);
        exit;
    }
    
    $consumer = $result->fetch_assoc();
    $verifiedPhone = $consumer['ContactNumber'];
    
    // Check if provided phone matches database phone
    if ($phoneNumber !== $verifiedPhone) {
        echo json_encode([
            "success" => false,
            "message" => "Phone number mismatch. Database: $verifiedPhone, Provided: $phoneNumber"
        ]);
        exit;
    }
    
    $stmt->close();
}

// Send SMS via IPROG SMS API (using query parameters)
$apiToken = '2b34d1eab4dc5248498965fffcb626421754a33a';

// Format phone number for IPROG (remove leading 0, add 63 for Philippines)
$formattedPhone = $phoneNumber;
if (strpos($phoneNumber, '09') === 0) {
    $formattedPhone = '63' . substr($phoneNumber, 1);
}

// Build URL with query parameters
$url = 'https://www.iprogsms.com/api/v1/sms_messages?' . http_build_query([
    'api_token' => $apiToken,
    'phone_number' => $formattedPhone,
    'message' => $message
]);

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/x-www-form-urlencoded'
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$smsResponse = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

// Parse SMS API response
$smsResult = json_decode($smsResponse, true);
$smsSuccess = false;
$smsMessage = '';

if ($curlError) {
    $smsMessage = "CURL Error: $curlError";
} elseif ($httpCode !== 200) {
    $smsMessage = "HTTP Error $httpCode: $smsResponse";
} elseif (isset($smsResult['status']) && $smsResult['status'] == 200) {
    $smsSuccess = true;
    $smsMessage = "SMS sent successfully via IPROG";
    if (isset($smsResult['message_id'])) {
        $smsMessage .= " (ID: {$smsResult['message_id']})";
    }
} else {
    $smsMessage = $smsResult['message'] ?? 'Unknown SMS API error';
}

// Store in pending_readings table as SMS log with real consumer and reader info
$logQuery = "INSERT INTO pending_readings (consumer_id, reader_id, previous_reading, current_reading, total_amount, consumption, date_created) VALUES (?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($logQuery);

// Extract billing details from message
$previousReading = '';
$currentReading = '';
$amount = '';

if (preg_match('/Previous: (\d+)/', $message, $matches)) {
    $previousReading = $matches[1];
}
if (preg_match('/Current: (\d+)/', $message, $matches)) {
    $currentReading = $matches[1];
}
if (preg_match('/Amount: ₱([\d.]+)/', $message, $matches)) {
    $amount = $matches[1];
}

$consumption = $currentReading - $previousReading;
$dateCreated = date('Y-m-d H:i:s');

$stmt->bind_param("iidddds", $consumerId, $readerId, $previousReading, $currentReading, $amount, $consumption, $dateCreated);

if ($stmt->execute()) {
    echo json_encode([
        "success" => $smsSuccess,
        "message" => $smsMessage,
        "data" => [
            "recipient" => $phoneNumber,
            "formatted_phone" => $formattedPhone,
            "message" => $message,
            "consumer_id" => $consumerId,
            "reader_id" => $readerId,
            "previous_reading" => $previousReading,
            "current_reading" => $currentReading,
            "amount" => $amount,
            "logged_at" => $dateCreated,
            "sms_api_response" => $smsResult
        ]
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Failed to log SMS: " . $stmt->error
    ]);
}

$stmt->close();
$conn->close();
?>
