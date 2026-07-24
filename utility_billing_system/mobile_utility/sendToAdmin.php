<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

require_once 'db.php';

// Read JSON input safely
$input = file_get_contents("php://input");
$data = json_decode($input, true);

if (!$data) {
    echo json_encode(["success" => false, "message" => "Invalid input."]);
    exit;
}

// Extract required fields
$consumerID = $data['consumerID'] ?? null;
$readerID = $data['readerID'] ?? null;
$previousReading = $data['previousReading'] ?? null;
$currentReading = $data['currentReading'] ?? null;

if (!$consumerID || !$readerID || $previousReading === null || $currentReading === null) {
    echo json_encode(["success" => false, "message" => "Missing required fields."]);
    exit;
}

// Step 1: Get MeterID and Classification
$stmt = $conn->prepare("SELECT MeterID, Classification FROM meter WHERE ConsumerID = ? LIMIT 1");
$stmt->bind_param("i", $consumerID);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "Meter not found for consumer."]);
    exit;
}

$meter = $result->fetch_assoc();
$meterID = $meter['MeterID'];
$classification = strtolower(trim($meter['Classification']));

// Step 2: Determine rates
switch ($classification) {
    case 'residential':
        $ratePerUnit = 2.3;
        $fixedCharge = 30.00;
        break;
    case 'commercial':
        $ratePerUnit = 3.5;
        $fixedCharge = 50.00;
        break;
    case 'industrial':
        $ratePerUnit = 5.0;
        $fixedCharge = 100.00;
        break;
    default:
        $ratePerUnit = 2.3;
        $fixedCharge = 30.00;
}

// Step 3: Compute consumption and charges
$consumption = $currentReading - $previousReading;
$readingDate = date("Y-m-d H:i:s");
$subTotal = $consumption * $ratePerUnit;
$taxRate = 0.12;
$taxAmount = $subTotal * $taxRate;
$totalDue = $subTotal + $taxAmount + $fixedCharge;

// Step 4: Insert into meterreadingdata
$stmtInsert = $conn->prepare("
    INSERT INTO meterreadingdata 
    (MeterID, ReaderID, PreviousReading, CurrentReading, Consumption, ReadingDate, Status)
    VALUES (?, ?, ?, ?, ?, ?, 'pending')
");
$stmtInsert->bind_param("iiddds", $meterID, $readerID, $previousReading, $currentReading, $consumption, $readingDate);

if (!$stmtInsert->execute()) {
    echo json_encode(["success" => false, "message" => "Failed to insert meter reading."]);
    exit;
}

// Step 5: Insert into billingstatement
$billingPeriod = date("F Y");
$billingDate = date("Y-m-d");
$dueDate = date("Y-m-d", strtotime("+15 days"));

$stmtBilling = $conn->prepare("
    INSERT INTO billingstatement 
    (MeterID, BillingPeriod, BillingDate, DueDate, PreviousReading, CurrentReading, Consumption, RatePerUnit, FixedCharge, TaxRate, TaxAmount, TotalAmount)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");
$stmtBilling->bind_param(
    "isssdddddddd",
    $meterID,
    $billingPeriod,
    $billingDate,
    $dueDate,
    $previousReading,
    $currentReading,
    $consumption,
    $ratePerUnit,
    $fixedCharge,
    $taxRate,
    $taxAmount,
    $totalDue
);

if (!$stmtBilling->execute()) {
    echo json_encode(["success" => false, "message" => "Failed to insert billing statement."]);
    exit;
}

// Step 6: Update meter table with new last reading
$stmtUpdate = $conn->prepare("
    UPDATE meter 
    SET LastReading = ?, LastReadingDate = ? 
    WHERE MeterID = ?
");
$stmtUpdate->bind_param("dsi", $currentReading, $readingDate, $meterID);

if (!$stmtUpdate->execute()) {
    echo json_encode(["success" => false, "message" => "Failed to update meter last reading."]);
    exit;
}

// ✅ Success response
echo json_encode([
    "success" => true,
    "message" => "Meter reading, billing statement, and meter last reading updated successfully.",
    "classification" => ucfirst($classification),
    "ratePerUnit" => $ratePerUnit,
    "fixedCharge" => $fixedCharge,
    "consumption" => $consumption,
    "totalAmount" => $totalDue,
    "updatedLastReading" => $currentReading,
    "updatedLastReadingDate" => $readingDate
]);

$conn->close();
exit;
?>
