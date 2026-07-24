<?php
require_once __DIR__ . '/../config/database.php';

/**
 * Get consumer data by ID
 */
function getConsumerData($consumer_id) {
    global $conn;

    $stmt = $conn->prepare("SELECT 
                                ConsumerID,
                                FirstName,
                                LastName,
                                Email,
                                ContactNumber,
                                Address,
                                Status,
                                BillingPreference,
                                RegistrationDate
                            FROM consumer 
                            WHERE ConsumerID = ?");
    $stmt->bind_param("i", $consumer_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo json_encode([
            "success" => true,
            "consumer" => $result->fetch_assoc()
        ]);
    } else {
        echo json_encode(["success" => false, "message" => "Consumer not found"]);
    }

    $stmt->close();
}

/**
 * Get consumer's meters
 */
function getConsumerMeters($consumer_id) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT 
                                MeterID,
                                MeterNumber,
                                Area,
                                LastReading,
                                LastReadingDate,
                                Status,
                                InstallationDate
                            FROM meter
                            WHERE ConsumerID = ?
                            ORDER BY MeterNumber");
    $stmt->bind_param("i", $consumer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $meters = [];
    while ($row = $result->fetch_assoc()) {
        $meters[] = $row;
    }
    
    echo json_encode([
        "success" => true,
        "count" => count($meters),
        "meters" => $meters
    ]);
    
    $stmt->close();
}

/**
 * Get consumer's billing history
 */
function getConsumerBills($consumer_id, $limit = 12) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT 
                                b.BillingID,
                                b.MeterID,
                                m.MeterNumber,
                                b.BillingPeriod,
                                b.BillingDate,
                                b.DueDate,
                                b.PreviousReading,
                                b.CurrentReading,
                                b.Consumption,
                                b.RatePerUnit,
                                b.FixedCharge,
                                b.TaxRate,
                                b.TaxAmount,
                                b.TotalAmount,
                                b.PaymentStatus,
                                b.PaymentDate
                            FROM billingstatement b
                            JOIN meter m ON b.MeterID = m.MeterID
                            WHERE m.ConsumerID = ?
                            ORDER BY b.BillingDate DESC
                            LIMIT ?");
    $stmt->bind_param("ii", $consumer_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $bills = [];
    $total_unpaid = 0;
    
    while ($row = $result->fetch_assoc()) {
        if ($row['PaymentStatus'] === 'unpaid' || $row['PaymentStatus'] === 'overdue') {
            $total_unpaid += $row['TotalAmount'];
        }
        $bills[] = $row;
    }
    
    echo json_encode([
        "success" => true,
        "count" => count($bills),
        "total_unpaid" => $total_unpaid,
        "bills" => $bills
    ]);
    
    $stmt->close();
}

/**
 * Get consumer usage statistics
 */
function getConsumerStats($consumer_id) {
    global $conn;
    
    // Get total consumption for last 6 months
    $stmt = $conn->prepare("SELECT 
                                b.BillingPeriod,
                                b.Consumption,
                                b.TotalAmount,
                                b.BillingDate
                            FROM billingstatement b
                            JOIN meter m ON b.MeterID = m.MeterID
                            WHERE m.ConsumerID = ?
                            ORDER BY b.BillingDate DESC
                            LIMIT 6");
    $stmt->bind_param("i", $consumer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $usage_history = [];
    $total_consumption = 0;
    $total_amount = 0;
    
    while ($row = $result->fetch_assoc()) {
        $usage_history[] = $row;
        $total_consumption += $row['Consumption'];
        $total_amount += $row['TotalAmount'];
    }
    
    $avg_consumption = count($usage_history) > 0 ? $total_consumption / count($usage_history) : 0;
    
    echo json_encode([
        "success" => true,
        "stats" => [
            "total_consumption" => $total_consumption,
            "total_amount" => $total_amount,
            "average_consumption" => round($avg_consumption, 2),
            "usage_history" => array_reverse($usage_history)
        ]
    ]);
    
    $stmt->close();
}
?>
