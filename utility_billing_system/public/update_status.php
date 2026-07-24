<?php
require_once '../config/db.php';
require_once '../functions/admin_functions.php';
requireAdminLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';
    $status = $_POST['status'] ?? '';

    if (in_array($status, ['paid', 'unpaid', 'overdue']) && !empty($id)) {
        $stmt = $conn->prepare("UPDATE billingstatement SET PaymentStatus = ? WHERE BillingID = ?");
        $stmt->bind_param("si", $status, $id);
        if ($stmt->execute()) {
            echo "Billing status updated to " . strtoupper($status);
        } else {
            echo "Error updating status.";
        }
        $stmt->close();
    } else {
        echo "Invalid request.";
    }
}
?>
