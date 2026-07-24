<?php
require_once __DIR__ . '/../config/database.php';

/**
 * Get all active meters (for any reader)
 */
function getMeters($reader_id = null) {
    global $conn;
    
    $sql = "SELECT 
                m.MeterID,
                m.MeterNumber,
                m.Area,
                m.LastReading,
                m.LastReadingDate,
                m.Status,
                m.ConsumerID,
                CONCAT(c.FirstName, ' ', c.LastName) as ConsumerName,
                c.Address,
                c.ContactNumber,
                c.Email
            FROM meter m
            LEFT JOIN consumer c ON m.ConsumerID = c.ConsumerID
            WHERE m.Status = 'active'
            ORDER BY m.MeterNumber";
    
    $result = $conn->query($sql);
    
    $meters = [];
    while ($row = $result->fetch_assoc()) {
        $meters[] = $row;
    }
    
    echo json_encode([
        "success" => true, 
        "count" => count($meters),
        "meters" => $meters
    ]);
}

/**
 * Get single meter details
 */
function getMeterDetails($meter_id) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT 
                                m.MeterID,
                                m.MeterNumber,
                                m.Area,
                                m.LastReading,
                                m.LastReadingDate,
                                m.Status,
                                m.ConsumerID,
                                CONCAT(c.FirstName, ' ', c.LastName) as ConsumerName,
                                c.Address,
                                c.ContactNumber,
                                c.Email
                            FROM meter m
                            LEFT JOIN consumer c ON m.ConsumerID = c.ConsumerID
                            WHERE m.MeterID = ?");
    $stmt->bind_param("i", $meter_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo json_encode([
            "success" => true,
            "meter" => $result->fetch_assoc()
        ]);
    } else {
        echo json_encode(["success" => false, "message" => "Meter not found"]);
    }
    
    $stmt->close();
}

/**
 * Submit new meter reading
 */
function submitReading($meter_id, $reader_id, $current_reading, $notes = '') {
    global $conn;
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Get meter details
        $stmt = $conn->prepare("SELECT LastReading, ConsumerID FROM meter WHERE MeterID = ?");
        $stmt->bind_param("i", $meter_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("Meter not found");
        }
        
        $meter = $result->fetch_assoc();
        $previous_reading = $meter['LastReading'];
        $consumer_id = $meter['ConsumerID'];
        
        // Validate reading
        if ($current_reading < $previous_reading) {
            throw new Exception("Current reading must be greater than or equal to previous reading");
        }
        
        $consumption = $current_reading - $previous_reading;
        
        // Insert reading data
        $stmt = $conn->prepare("INSERT INTO meterreadingdata 
                                (MeterID, ReaderID, PreviousReading, CurrentReading, Consumption, ReadingDate, Notes, Status) 
                                VALUES (?, ?, ?, ?, ?, NOW(), ?, 'approved')");
        $stmt->bind_param("iiddds", $meter_id, $reader_id, $previous_reading, $current_reading, $consumption, $notes);
        $stmt->execute();
        
        // Update meter
        $stmt = $conn->prepare("UPDATE meter SET LastReading = ?, LastReadingDate = NOW() WHERE MeterID = ?");
        $stmt->bind_param("di", $current_reading, $meter_id);
        $stmt->execute();
        
        // Calculate bill
        $rate_per_unit = 5.00;
        $fixed_charge = 50.00;
        $tax_rate = 12.00;
        
        $consumption_charge = $consumption * $rate_per_unit;
        $subtotal = $consumption_charge + $fixed_charge;
        $tax_amount = $subtotal * ($tax_rate / 100);
        $total_amount = $subtotal + $tax_amount;
        
        // Generate billing statement
        $billing_period = date('F Y');
        $billing_date = date('Y-m-d');
        $due_date = date('Y-m-d', strtotime('+15 days'));
        
        $stmt = $conn->prepare("INSERT INTO billingstatement 
                                (MeterID, BillingPeriod, BillingDate, DueDate, PreviousReading, CurrentReading, 
                                Consumption, RatePerUnit, FixedCharge, TaxRate, TaxAmount, TotalAmount, PaymentStatus) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'unpaid')");
        $stmt->bind_param("isssdddddddd", 
            $meter_id, $billing_period, $billing_date, $due_date,
            $previous_reading, $current_reading, $consumption,
            $rate_per_unit, $fixed_charge, $tax_rate, $tax_amount, $total_amount
        );
        $stmt->execute();
        
        $billing_id = $conn->insert_id;
        
        // Commit transaction
        $conn->commit();
        
        echo json_encode([
            "success" => true,
            "message" => "Reading submitted successfully",
            "data" => [
                "meter_id" => $meter_id,
                "previous_reading" => $previous_reading,
                "current_reading" => $current_reading,
                "consumption" => $consumption,
                "total_amount" => $total_amount,
                "billing_id" => $billing_id
            ]
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode([
            "success" => false,
            "message" => $e->getMessage()
        ]);
    }
    
    $stmt->close();
}

/**
 * Get reading history for a meter
 */
function getReadingHistory($meter_id, $limit = 10) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT 
                                r.ReadingID,
                                r.PreviousReading,
                                r.CurrentReading,
                                r.Consumption,
                                r.ReadingDate,
                                r.Notes,
                                r.Status,
                                CONCAT(u.FirstName, ' ', u.LastName) as ReaderName
                            FROM meterreadingdata r
                            LEFT JOIN utilityreader u ON r.ReaderID = u.ReaderID
                            WHERE r.MeterID = ?
                            ORDER BY r.ReadingDate DESC
                            LIMIT ?");
    $stmt->bind_param("ii", $meter_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $readings = [];
    while ($row = $result->fetch_assoc()) {
        $readings[] = $row;
    }
    
    echo json_encode([
        "success" => true,
        "count" => count($readings),
        "readings" => $readings
    ]);
    
    $stmt->close();
}
?>
