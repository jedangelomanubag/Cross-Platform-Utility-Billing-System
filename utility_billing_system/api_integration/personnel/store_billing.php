<?php
// personnel/store_billing.php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../utils/auth_guard.php';

$reader = require_reader_auth();
$input = json_input();

$meterId = isset($input['MeterID']) ? (int)$input['MeterID'] : null;
$billingPeriod = isset($input['BillingPeriod']) ? $input['BillingPeriod'] : null;
$billingDate = isset($input['BillingDate']) ? $input['BillingDate'] : date('Y-m-d');
$dueDate = isset($input['DueDate']) ? $input['DueDate'] : date('Y-m-d', strtotime('+15 days'));
$previousReading = isset($input['PreviousReading']) ? (float)$input['PreviousReading'] : null;
$currentReading = isset($input['CurrentReading']) ? (float)$input['CurrentReading'] : null;
$consumption = isset($input['Consumption']) ? (float)$input['Consumption'] : null;
$ratePerUnit = isset($input['RatePerUnit']) ? (float)$input['RatePerUnit'] : null;
$fixedCharge = isset($input['FixedCharge']) ? (float)$input['FixedCharge'] : 0.00;
$taxRate = isset($input['TaxRate']) ? (float)$input['TaxRate'] : 0.00;

if (!$meterId || !$billingPeriod || $previousReading === null || $currentReading === null || $consumption === null || $ratePerUnit === null) res_error('Missing billing fields', 400);

$taxAmount = round(($consumption * $ratePerUnit + $fixedCharge) * ($taxRate / 100), 2);
$totalAmount = round($consumption * $ratePerUnit + $fixedCharge + $taxAmount, 2);

$stmt = $mysqli->prepare('INSERT INTO billingstatement (MeterID, BillingPeriod, BillingDate, DueDate, PreviousReading, CurrentReading, Consumption, RatePerUnit, FixedCharge, TaxRate, TaxAmount, TotalAmount, PaymentStatus) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
if (!$stmt) res_error('Prepare failed: ' . $mysqli->error, 500);

$paymentStatus = 'unpaid';
$stmt->bind_param('isssdddddddds', $meterId, $billingPeriod, $billingDate, $dueDate, $previousReading, $currentReading, $consumption, $ratePerUnit, $fixedCharge, $taxRate, $taxAmount, $totalAmount, $paymentStatus);

$ok = $stmt->execute();
if (!$ok) res_error('Insert billing failed: ' . $stmt->error, 500);
$billingId = $stmt->insert_id;
$stmt->close();

// Update meter last reading and date
$lastReadingDate = date('Y-m-d H:i:s');
$update = $mysqli->prepare('UPDATE meter SET LastReading = ?, LastReadingDate = ? WHERE MeterID = ?');
if ($update) {
    $update->bind_param('dsi', $currentReading, $lastReadingDate, $meterId);
    $update->execute();
    $update->close();
}

res_ok(['BillingID' => $billingId], 'Billing stored');
?>
