<?php
/**
 * Helper script to create test users with known passwords
 * Run this once via browser: http://localhost/utility_billing_system/database/create_test_users.php
 */

require_once '../includes/db_connect.php';

echo "<h2>Creating Test Users</h2>";
echo "<pre>";

// Test password: password123
$test_password = 'password123';
$hashed_password = password_hash($test_password, PASSWORD_BCRYPT);

echo "Test Password: password123\n";
echo "Hashed: $hashed_password\n\n";

// Update existing reader (lucas) with test password
$sql = "UPDATE utilityreader SET Password = ? WHERE Username = 'lucas'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $hashed_password);
if ($stmt->execute()) {
    echo "✓ Updated reader 'lucas' with password: password123\n";
} else {
    echo "✗ Failed to update reader 'lucas'\n";
}

// Update existing consumer with test password
$sql = "UPDATE consumer SET Password = ? WHERE Email = 'michaelacristobal1998@gmail.com'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $hashed_password);
if ($stmt->execute()) {
    echo "✓ Updated consumer 'michaelacristobal1998@gmail.com' with password: password123\n";
} else {
    echo "✗ Failed to update consumer\n";
}

// Assign reader to meter
$sql = "UPDATE meter SET ReaderID = 1 WHERE MeterID = 1";
if ($conn->query($sql)) {
    echo "✓ Assigned reader ID 1 (lucas) to meter ID 1\n";
} else {
    echo "✗ Failed to assign reader to meter\n";
}

echo "\n=== Test Credentials ===\n\n";
echo "Reader Portal:\n";
echo "URL: http://localhost/utility_billing_system/reader_portal\n";
echo "Username: lucas\n";
echo "Password: password123\n\n";

echo "Consumer Portal:\n";
echo "URL: http://localhost/utility_billing_system/consumer_portal\n";
echo "Email: michaelacristobal1998@gmail.com\n";
echo "Password: password123\n\n";

echo "Admin Portal:\n";
echo "URL: http://localhost/utility_billing_system/portal/login.php\n";
echo "Email: admin@example.com\n";
echo "Password: password (default)\n\n";

echo "=== Setup Complete! ===\n";
echo "You can now test the portals with the credentials above.\n";
echo "\n⚠️ IMPORTANT: Delete this file after testing for security!\n";

echo "</pre>";

$stmt->close();
$conn->close();
?>
