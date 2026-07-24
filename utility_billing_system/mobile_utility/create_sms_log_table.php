<?php
require_once 'db.php';

// Create SMS log table
$sql = "CREATE TABLE IF NOT EXISTS sms_log (
    id int(11) NOT NULL AUTO_INCREMENT,
    recipient varchar(20) NOT NULL,
    message text NOT NULL,
    sender_id varchar(50) DEFAULT 'UtilityBilling',
    status enum('sent','failed','pending') DEFAULT 'sent',
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

if (mysqli_query($conn, $sql)) {
    echo json_encode([
        "success" => true,
        "message" => "SMS log table created successfully"
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Error creating table: " . mysqli_error($conn)
    ]);
}

mysqli_close($conn);
?>
