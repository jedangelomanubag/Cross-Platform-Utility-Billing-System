<?php
require_once '../config/db.php';
require_once '../functions/admin_functions.php';

// Ensure admin is logged in
requireAdminLogin();

$page_title = 'Meter Readings';

// Get search parameter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Base query
$query = "SELECT 
            mr.*, 
            m.Classification, 
            m.Area, 
            m.SerialNo, 
            c.AccountNo, 
            c.FirstName, 
            c.LastName, 
            c.Email
          FROM meterreadingdata mr
          JOIN meter m ON mr.MeterID = m.MeterID
          JOIN consumer c ON m.ConsumerID = c.ConsumerID
          WHERE 1=1";

// Add search filter
$params = [];
if (!empty($search)) {
    $query .= " AND (
        c.FirstName LIKE ? 
        OR c.LastName LIKE ? 
        OR m.SerialNo LIKE ? 
        OR c.AccountNo LIKE ?
    )";
    $searchParam = "%$search%";
    $params = [$searchParam, $searchParam, $searchParam, $searchParam];
}

$query .= " ORDER BY mr.ReadingDate DESC";

$stmt = mysqli_prepare($conn, $query);

if (!empty($params)) {
    $types = str_repeat('s', count($params));
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}

$readings = [];
if (mysqli_stmt_execute($stmt)) {
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $readings[] = $row;
    }
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Meter Readings</h1>
    </div>

    <!-- Search Bar -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-8">
                    <input type="text" class="form-control" name="search"
                           placeholder="Search by Consumer Name, Serial No, or Account No..."
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">Search</button>
                    <a href="meter_readings.php" class="btn btn-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Table -->
    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th>Reading ID</th>
                    <th>Meter Info</th>
                    <th>Consumer</th>
                    <th>Reading Date</th>
                    <th>Current Reading</th>
                    <th>Previous Reading</th>
                    <th>Consumption</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($readings)): ?>
                    <?php foreach ($readings as $reading): ?>
                        <tr>
                            <td>#<?php echo $reading['ReadingID']; ?></td>
                            <td>
                                <?php 
                                    echo htmlspecialchars($reading['Classification']) . ' - ' . 
                                         htmlspecialchars($reading['Area']) . '<br>' .
                                         'Serial: ' . htmlspecialchars($reading['SerialNo']) . '<br>' .
                                         'AccountNo: ' . htmlspecialchars($reading['AccountNo']);
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($reading['FirstName'] . ' ' . $reading['LastName']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($reading['ReadingDate'])); ?></td>
                            <td><?php echo number_format($reading['CurrentReading'], 2); ?> m³</td>
                            <td><?php echo number_format($reading['PreviousReading'], 2); ?> m³</td>
                            <td><?php echo number_format($reading['CurrentReading'] - $reading['PreviousReading'], 2); ?> m³</td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center">No meter readings found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

<?php include '../includes/footer.php'; ?>
