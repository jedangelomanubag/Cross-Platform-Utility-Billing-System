<?php
header('Content-Type: application/json');
require_once 'db.php';

$sql = "SELECT * FROM pending_readings ORDER BY id DESC";
$result = $conn->query($sql);

$pending = [];
if($result){
    while($row = $result->fetch_assoc()){
        $pending[] = $row;
    }
}

echo json_encode([
    'success' => true,
    'data' => $pending
]);
