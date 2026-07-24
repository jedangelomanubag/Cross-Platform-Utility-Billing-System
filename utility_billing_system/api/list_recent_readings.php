<?php
header('Content-Type: application/json');
require_once '../includes/db_connect.php';

// Check if reader is logged in
if (!isset($_SESSION['reader_id']) || $_SESSION['user_type'] !== 'reader') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$reader_id = $_SESSION['reader_id'];
$limit = intval($_GET['limit'] ?? 20);

// Get recent readings by this reader
$stmt = $conn->prepare("
    SELECT 
        mrd.ReadingID,
        mrd.ReadingDate,
        mrd.PreviousReading,
        mrd.CurrentReading,
        mrd.Consumption,
        mrd.Notes,
        mrd.Status,
        m.MeterNumber,
        CONCAT(c.FirstName, ' ', c.LastName) as ConsumerName
    FROM meterreadingdata mrd
    JOIN meter m ON mrd.MeterID = m.MeterID
    LEFT JOIN consumer c ON m.ConsumerID = c.ConsumerID
    WHERE mrd.ReaderID = ?
    ORDER BY mrd.ReadingDate DESC
    LIMIT ?
");
$stmt->bind_param("ii", $reader_id, $limit);
$stmt->execute();
$result = $stmt->get_result();

$readings = [];
while ($row = $result->fetch_assoc()) {
    $readings[] = $row;
}

echo json_encode(['success' => true, 'readings' => $readings]);

$stmt->close();
$conn->close();
?>
