<?php
require_once '../../config/db.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

// Ensure admin is logged in
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get and validate input
$readerId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$status = ($_POST['status'] ?? '') === 'active' ? 'active' : 'inactive';

if (!$readerId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid reader ID']);
    exit;
}

try {
    // Update the reader status
    $stmt = $conn->prepare("UPDATE utilityreader SET Status = ?, UpdatedAt = NOW() WHERE ReaderID = ?");
    $stmt->bind_param('si', $status, $readerId);
    
    if ($stmt->execute()) {
        // Log the action
        $action = $status === 'active' ? 'Activated' : 'Deactivated';
        logAdminAction($_SESSION['admin_id'], "$action reader with ID: $readerId");
        
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('Failed to update reader status');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

$stmt->close();
$conn->close();
