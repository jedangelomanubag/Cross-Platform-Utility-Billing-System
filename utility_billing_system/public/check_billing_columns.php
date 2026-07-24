<?php
require_once '../config/db.php';

echo "<h2>Checking billingstatement table structure</h2>";

// Check if table exists
$result = mysqli_query($conn, "SHOW TABLES LIKE 'billingstatement'");
if (mysqli_num_rows($result) > 0) {
    echo "Table 'billingstatement' exists.<br><br>";
    
    // Get columns
    $result = mysqli_query($conn, "SHOW COLUMNS FROM billingstatement");
    if ($result) {
        echo "<h3>Columns in billingstatement table:</h3>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<tr>";
            echo "<td>" . $row['Field'] . "</td>";
            echo "<td>" . $row['Type'] . "</td>";
            echo "<td>" . $row['Null'] . "</td>";
            echo "<td>" . $row['Key'] . "</td>";
            echo "<td>" . $row['Default'] . "</td>";
            echo "<td>" . $row['Extra'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "Error getting columns: " . mysqli_error($conn);
    }
} else {
    echo "Error: Table 'billingstatement' does not exist.";
}

// Check consumer table structure
echo "<h3>Columns in consumer table:</h3>";
$result = mysqli_query($conn, "SHOW COLUMNS FROM consumer");
if ($result) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Error getting columns: " . mysqli_error($conn);
}

// Check foreign key constraints
echo "<h3>Foreign Key Constraints:</h3>";
$result = mysqli_query($conn, "
    SELECT 
        TABLE_NAME, COLUMN_NAME, 
        CONSTRAINT_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
    FROM 
        INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
    WHERE 
        REFERENCED_TABLE_SCHEMA = 'utility_billing_system'
        AND (REFERENCED_TABLE_NAME = 'billingstatement' OR REFERENCED_TABLE_NAME = 'consumer')");

if ($result) {
    if (mysqli_num_rows($result) > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Table</th><th>Column</th><th>Constraint</th><th>References</th><th>Referenced Column</th></tr>";
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<tr>";
            echo "<td>" . $row['TABLE_NAME'] . "</td>";
            echo "<td>" . $row['COLUMN_NAME'] . "</td>";
            echo "<td>" . $row['CONSTRAINT_NAME'] . "</td>";
            echo "<td>" . $row['REFERENCED_TABLE_NAME'] . "</td>";
            echo "<td>" . $row['REFERENCED_COLUMN_NAME'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "No foreign key constraints found.";
    }
} else {
    echo "Error checking foreign keys: " . mysqli_error($conn);
}
?>
