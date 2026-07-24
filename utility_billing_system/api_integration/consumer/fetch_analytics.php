<?php
// consumer/fetch_analytics.php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils/response.php';

$consumerId = isset($_GET['ConsumerID']) ? (int)$_GET['ConsumerID'] : null;
$months = isset($_GET['Months']) ? (int)$_GET['Months'] : 6;
if (!$consumerId) res_error('ConsumerID required', 400);

// fetch meters
$stmt = $mysqli->prepare('SELECT MeterID FROM meter WHERE ConsumerID = ?');
$stmt->bind_param('i', $consumerId);
$stmt->execute();
$result = $stmt->get_result();
$meters = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$meterIds = array_column($meters, 'MeterID');
if (empty($meterIds)) res_ok(['analytics' => []]);

$placeholders = implode(',', array_fill(0, count($meterIds), '?'));
$sql = "SELECT DATE_FORMAT(BillingDate, '%Y-%m') as ym, SUM(Consumption) as total_consumption, SUM(TotalAmount) as total_amount FROM billingstatement WHERE MeterID IN ($placeholders) AND BillingDate >= DATE_SUB(CURDATE(), INTERVAL ? MONTH) GROUP BY ym ORDER BY ym DESC";

$stmt = $mysqli->prepare($sql);
if (!$stmt) res_error('Prepare failed: ' . $mysqli->error, 500);

$types = str_repeat('i', count($meterIds)) . 'i';
$params = array_merge($meterIds, [$months]);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$rows = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

res_ok(['analytics' => $rows]);
?>
