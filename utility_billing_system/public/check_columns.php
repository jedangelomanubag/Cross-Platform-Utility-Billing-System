<?php
require_once '../config/db.php';

// Check billingstatement table structure
$result = mysqli_query($conn, "SHOW COLUMNS FROM billingstatement");
if ($result) {
    echo "Columns in billingstatement table:<br>";
    while ($row = mysqli_fetch_assoc($result)) {
        echo $row['Field'] . " (" . $row['Type'] . ")<br>";
    }
} else {
    echo "Error: " . mysqli_error($conn);
}

// Check consumer table structure
$result = mysqli_query($conn, "SHOW COLUMNS FROM consumer");
if ($result) {
    echo "<br>Columns in consumer table:<br>";
    while ($row = mysqli_fetch_assoc($result)) {
        echo $row['Field'] . " (" . $row['Type'] . ")<br>";
    }
} else {
    echo "<br>Error: " . mysqli_error($conn);
}
?>
