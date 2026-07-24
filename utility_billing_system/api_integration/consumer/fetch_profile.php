<?php
// consumer/fetch_profile.php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils/response.php';

$consumerId = isset($_GET['ConsumerID']) ? (int)$_GET['ConsumerID'] : null;
if (!$consumerId) res_error('ConsumerID required', 400);

$stmt = $mysqli->prepare('SELECT ConsumerID, FirstName, LastName, Email, ContactNumber, Address, Status, BillingPreference, AppNotificationToken, RegistrationDate FROM consumer WHERE ConsumerID = ? LIMIT 1');
$stmt->bind_param('i', $consumerId);
$stmt->execute();
$result = $stmt->get_result();
$profile = $result->fetch_assoc();
$stmt->close();

if (!$profile) res_error('Consumer not found', 404);
res_ok(['profile' => $profile]);
?>
