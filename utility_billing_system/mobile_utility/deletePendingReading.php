<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");

require_once 'db.php';

$input = file_get_contents("php://input");
$data = json_decode($input, true);

if (!$data || !isset($data['id'])) {
    echo json_encode(["success" => false, "message" => "Invalid input"]);
    exit;
}

$id = intval($data['id']);

// Delete from pending_readings table
$stmt = $conn->prepare("DELETE FROM pending_readings WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Deleted successfully"]);
} else {
    echo json_encode(["success" => false, "message" => "Failed to delete"]);
}

$conn->close();
