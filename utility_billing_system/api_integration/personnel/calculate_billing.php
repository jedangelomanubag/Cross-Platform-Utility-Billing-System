<?php
// personnel/calculate_billing.php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../utils/auth_guard.php';

$reader = require_reader_auth();
$input = json_input();

$meterId = isset($input['MeterID']) ? (int)$input['MeterID'] : null;
$billingPeriod = isset($input['BillingPeriod']) ? $input['BillingPeriod'] : null; // e.g., 'Oct 2025'
$ratePerUnit = isset($input['RatePerUnit']) ? (float)$input['RatePerUnit'] : null;
$fixedCharge = isset($input['FixedCharge']) ? (float)$input['FixedCharge'] : 0.00;
$taxRate = isset($input['TaxRate']) ? (float)$input['TaxRate'] : 0.00; // percent e.g., 12.00

if (!$meterId || !$billingPeriod || $ratePerUnit === null) res_error('MeterID, BillingPeriod, and RatePerUnit required', 400);

// Get last reading from meter table
$stmt = $mysqli->prepare('SELECT LastReading FROM meter WHERE MeterID = ? LIMIT 1');
$stmt->bind_param('i', $meterId);
$stmt->execute();
$result = $stmt->get_result();
$meter = $result->fetch_assoc();
$stmt->close();

$previousReading = isset($meter['LastReading']) ? (float)$meter['LastReading'] : 0.00;

// Get latest reading record (most recent CurrentReading if exists)
$stmt = $mysqli->prepare('SELECT CurrentReading FROM meterreadingdata WHERE MeterID = ? ORDER BY ReadingDate DESC LIMIT 1');
$stmt->bind_param('i', $meterId);
$stmt->execute();
$result = $stmt->get_result();
$r = $result->fetch_assoc();
$stmt->close();

$currentReading = ($r && $r['CurrentReading'] !== null) ? (float)$r['CurrentReading'] : $previousReading;
$consumption = max(0, round($currentReading - $previousReading, 2));

$taxAmount = round(($consumption * $ratePerUnit + $fixedCharge) * ($taxRate / 100), 2);
$total = round($consumption * $ratePerUnit + $fixedCharge + $taxAmount, 2);

$res = [
    'MeterID' => $meterId,
    'BillingPeriod' => $billingPeriod,
    'PreviousReading' => $previousReading,
    'CurrentReading' => $currentReading,
    'Consumption' => $consumption,
    'RatePerUnit' => $ratePerUnit,
    'FixedCharge' => $fixedCharge,
    'TaxRate' => $taxRate,
    'TaxAmount' => $taxAmount,
    'TotalAmount' => $total
];

res_ok($res, 'Billing calculated');
?>
