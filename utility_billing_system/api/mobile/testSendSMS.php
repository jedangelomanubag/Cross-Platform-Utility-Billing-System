<?php
// sendSMSfromWeb.php
header('Content-Type: application/json');

// === CONFIG ===
$apiToken = "131|jRk0K9bTTPPj5cMIekXWmi12MhyB7YbIo1Q73gAOe60011c7";
$senderID = "PhilSMS"; // Sender name representing your web system

// === TEST DATA (web to phone) ===
$recipient = "+639656543831"; // Recipient phone number
$message   = "Hello! This SMS is sent from the Water Billing Web System.";

// Build payload for PhilSMS API
$payload = [
    "recipient" => $recipient,
    "sender_id" => $senderID,
    "type"      => "plain",
    "message"   => $message
];

// Initialize cURL
$ch = curl_init("https://dashboard.philsms.com/api/v3/sms/send");
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $apiToken",
    "Content-Type: application/json",
    "Accept: application/json"
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

// Execute the request
$response = curl_exec($ch);
$err = curl_error($ch);
curl_close($ch);

// Log web-to-phone activity
file_put_contents('sms_web_to_phone.log', date('Y-m-d H:i:s') . " | From: Web | To: $recipient | Response: $response\n", FILE_APPEND);

// Handle cURL error
if ($err) {
    echo json_encode([
        'success' => false,
        'message' => "cURL error: $err"
    ]);
    exit;
}

// Decode PhilSMS response
$result = json_decode($response, true);

// Handle invalid JSON
if (!$result) {
    echo json_encode([
        'success' => false,
        'message' => "Invalid JSON response from PhilSMS API",
        'raw_response' => $response
    ]);
    exit;
}

// Return result in a clear web-to-phone format
echo json_encode([
    'success' => $result['status'] === 'success',
    'message' => $result['message'] ?? '',
    'sent_from' => 'Web System',
    'recipient' => $recipient,
    'raw_response' => $result
]);
