<?php
header("Content-Type: application/json");
require_once '../config/db.php';

if (!isset($_POST['billing_id'])) {
    echo json_encode(["success" => false, "message" => "Billing ID missing"]);
    exit;
}

$billingID = intval($_POST['billing_id']);

// Get consumer info using BillingID
$sql = "
    SELECT c.ConsumerID, c.ContactNumber, c.FirstName, c.LastName
    FROM billingstatement b
    INNER JOIN consumer c ON b.ConsumerID = c.ConsumerID
    WHERE b.BillingID = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $billingID);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows == 0) {
    echo json_encode(["success" => false, "message" => "Consumer not found"]);
    exit;
}

$consumer = $res->fetch_assoc();

$phone = $consumer["ContactNumber"];
$name = $consumer["FirstName"];
$consumerID = $consumer["ConsumerID"];

// Auto-generated SMS message
$message = "Hello $name! Your water billing statement is now available.";

// PhilSMS v3 API
$url = "https://dashboard.philsms.com/api/v3/sms/send";

$payload = [
    "recipient" => $phone,
    "sender_id" => "PhilSMS",
    "type" => "plain",
    "message" => $message
];

$apiToken = "145|X322NKUg2qJJHUg2PWKZyeqZX9ZqUNanp7OTtmxI62341a13";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer $apiToken",
        "Content-Type: application/json"
    ],
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_RETURNTRANSFER => true
]);

$response = curl_exec($ch);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo json_encode(["success" => false, "message" => "Curl Error: $error"]);
    exit;
}

echo json_encode([
    "success" => true,
    "message" => "SMS sent successfully",
    "phone" => $phone,
    "raw" => json_decode($response, true)
]);
