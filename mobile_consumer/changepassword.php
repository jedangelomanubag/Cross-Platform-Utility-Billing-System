<?php
require_once "db.php";

// Consumer ID 17: cliemente vesquiza
$consumerId = 3;
$newPassword = "c22-0008";

// Hash the new password
$hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

// Update the password in database
$stmt = $conn->prepare("UPDATE consumer SET Password = ? WHERE ConsumerID = ?");
$stmt->bind_param("si", $hashedPassword, $consumerId);

if ($stmt->execute()) {
    echo "Password updated successfully for Consumer ID: $consumerId (cliemente vesquiza)\n";
    echo "New password: c22-0008\n";
    echo "Hashed password: $hashedPassword\n";
    
    // Verify the update
    $verifyStmt = $conn->prepare("SELECT FirstName, LastName, Email FROM consumer WHERE ConsumerID = ?");
    $verifyStmt->bind_param("i", $consumerId);
    $verifyStmt->execute();
    $result = $verifyStmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        echo "Updated user: " . $user['FirstName'] . " " . $user['LastName'] . " (" . $user['Email'] . ")\n";
    }
    $verifyStmt->close();
} else {
    echo "Error updating password: " . $conn->error . "\n";
}

$stmt->close();
$conn->close();
?>
