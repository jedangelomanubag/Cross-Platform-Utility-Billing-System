<?php
// consumer/fetch_history.php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils/response.php';

$consumerId = isset($_GET['ConsumerID']) ? (int)$_GET['ConsumerID'] : null;
if (!$consumerId) res_error('ConsumerID required', 400);

$stmt = $mysqli->prepare('SELECT MeterID FROM meter WHERE ConsumerID = ?');
$stmt->bind_param('i', $consumerId);
$stmt->execute();
$result = $stmt->get_result();
$meters = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$meterIds = array_column($meters, 'MeterID');
if (empty($meterIds)) res_ok(['history' => []]);

$placeholders = implode(',', array_fill(0, count($meterIds), '?'));
$sql = "SELECT bs.* FROM billingstatement bs WHERE bs.MeterID IN ($placeholders) ORDER BY bs.BillingDate DESC";

$stmt = $mysqli->prepare($sql);
if (!$stmt) res_error('Prepare failed: ' . $mysqli->error, 500);

$types = str_repeat('i', count($meterIds));
$stmt->bind_param($types, ...$meterIds);
$stmt->execute();
$result = $stmt->get_result();
$rows = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

res_ok(['history' => $rows]);
?>
