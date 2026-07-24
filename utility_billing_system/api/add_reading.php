<?php
header('Content-Type: application/json');
require_once '../includes/db_connect.php';

// Check if reader is logged in
if (!isset($_SESSION['reader_id']) || $_SESSION['user_type'] !== 'reader') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$meter_id = intval($_POST['meter_id'] ?? 0);
$current_reading = floatval($_POST['current_reading'] ?? 0);
$notes = sanitize($_POST['notes'] ?? '');
$reader_id = $_SESSION['reader_id'];

if ($meter_id <= 0 || $current_reading < 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid meter or reading value']);
    exit();
}

// Start transaction
$conn->begin_transaction();

try {
    // Get meter details and last reading
    $stmt = $conn->prepare("SELECT LastReading, ConsumerID FROM meter WHERE MeterID = ? AND Status = 'active'");
    $stmt->bind_param("i", $meter_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Meter not found or inactive');
    }
    
    $meter = $result->fetch_assoc();
    $previous_reading = floatval($meter['LastReading']);
    $consumer_id = $meter['ConsumerID'];
    
    // Validate current reading is greater than previous
    if ($current_reading < $previous_reading) {
        throw new Exception('Current reading must be greater than or equal to previous reading');
    }
    
    $consumption = $current_reading - $previous_reading;
    $reading_date = date('Y-m-d H:i:s');
    
    // Insert into meterreadingdata with status 'approved'
    $stmt = $conn->prepare("INSERT INTO meterreadingdata (MeterID, ReaderID, PreviousReading, CurrentReading, Consumption, ReadingDate, Notes, Status) VALUES (?, ?, ?, ?, ?, ?, ?, 'approved')");
    $stmt->bind_param("iidddss", $meter_id, $reader_id, $previous_reading, $current_reading, $consumption, $reading_date, $notes);
    $stmt->execute();
    $reading_id = $conn->insert_id;
    
    // Update meter's last reading and date
    $stmt = $conn->prepare("UPDATE meter SET LastReading = ?, LastReadingDate = ? WHERE MeterID = ?");
    $stmt->bind_param("dsi", $current_reading, $reading_date, $meter_id);
    $stmt->execute();
    
    // Calculate billing
    $rate_per_unit = 5.00;
    $fixed_charge = 50.00;
    $tax_rate = 12.00;
    
    $consumption_charge = $consumption * $rate_per_unit;
    $subtotal = $consumption_charge + $fixed_charge;
    $tax_amount = ($subtotal * $tax_rate) / 100;
    $total_amount = $subtotal + $tax_amount;
    
    // Generate billing period (current month)
    $billing_period = date('F Y');
    $billing_date = date('Y-m-d');
    $due_date = date('Y-m-d', strtotime('+15 days'));
    
    // Insert billing statement with status 'unpaid'
    $stmt = $conn->prepare("INSERT INTO billingstatement (MeterID, BillingPeriod, BillingDate, DueDate, PreviousReading, CurrentReading, Consumption, RatePerUnit, FixedCharge, TaxRate, TaxAmount, TotalAmount, PaymentStatus) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'unpaid')");
    $stmt->bind_param("isssdddddddd", $meter_id, $billing_period, $billing_date, $due_date, $previous_reading, $current_reading, $consumption, $rate_per_unit, $fixed_charge, $tax_rate, $tax_amount, $total_amount);
    $stmt->execute();
    $billing_id = $conn->insert_id;
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Reading added and bill generated successfully',
        'data' => [
            'reading_id' => $reading_id,
            'billing_id' => $billing_id,
            'consumption' => $consumption,
            'total_amount' => $total_amount
        ]
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$stmt->close();
$conn->close();
?>
