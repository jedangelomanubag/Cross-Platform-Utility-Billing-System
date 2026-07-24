<?php
require_once '../config/db.php';
require_once '../functions/admin_functions.php';

// Ensure admin is logged in
requireAdminLogin();

// Set page title
$page_title = 'Dashboard';

// ------------------- Summary Cards -------------------

// Get total consumers
$counts['consumers'] = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM consumer"))['count'];

// Get total utility readers
$counts['readers'] = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM utilityreader"))['count'];

// Get total meters
$counts['meters'] = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM meter"))['count'];

// Get unpaid bills (last 30 days)
$counts['unpaid_bills'] = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM billingstatement 
                                                                WHERE PaymentStatus='unpaid' AND DueDate >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)"))['count'];

// ------------------- Charts Data -------------------

// Water consumption per area (sum of latest meter reading data)
$water_data = [];
$water_labels = [];
$result = mysqli_query($conn, "SELECT m.Area, SUM(b.Consumption) as total_consumption
                                FROM meter m
                                JOIN billingstatement b ON b.MeterID = m.MeterID
                                GROUP BY m.Area
                                ORDER BY total_consumption DESC");
while ($row = mysqli_fetch_assoc($result)) {
    $water_labels[] = $row['Area'];
    $water_data[] = $row['total_consumption'];
}

// Registered consumers per month (last 12 months)
$consumer_month_labels = [];
$consumer_month_data = [];
$result = mysqli_query($conn, "SELECT DATE_FORMAT(RegistrationDate, '%Y-%m') as month, COUNT(*) as count
                                FROM consumer
                                WHERE RegistrationDate >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                                GROUP BY month
                                ORDER BY month ASC");
while ($row = mysqli_fetch_assoc($result)) {
    $consumer_month_labels[] = $row['month'];
    $consumer_month_data[] = $row['count'];
}

// ------------------- Recent Activities -------------------
$recent_activities = [];
$result = mysqli_query($conn, "SELECT * FROM adminlogs ORDER BY Timestamp DESC LIMIT 10");
while ($row = mysqli_fetch_assoc($result)) {
    $recent_activities[] = $row;
}

// Include header and sidebar
include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Dashboard</h1>
    </div>

    <!-- Summary Cards -->
    <div class="container-fluid">
        <div class="row justify-content-center">
            <!-- Total Consumers Card -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Consumers</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($counts['consumers']); ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-users fa-2x text-gray-300"></i>
                            </div>
                        </div>
                        <a href="consumers.php" class="stretched-link"></a>
                    </div>
                </div>
            </div>

            <!-- Utility Readers Card -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Utility Readers</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($counts['readers']); ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-user-tie fa-2x text-gray-300"></i>
                            </div>
                        </div>
                        <a href="utility_readers.php" class="stretched-link"></a>
                    </div>
                </div>
            </div>

            <!-- Total Meters Card -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-info shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total Meters</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($counts['meters']); ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-tachometer-alt fa-2x text-gray-300"></i>
                            </div>
                        </div>
                        <a href="meters.php" class="stretched-link"></a>
                    </div>
                </div>
            </div>

            <!-- Unpaid Bills Card -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-warning shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Unpaid Bills</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($counts['unpaid_bills']); ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-file-invoice-dollar fa-2x text-gray-300"></i>
                            </div>
                        </div>
                        <a href="billing_statements.php" class="stretched-link"></a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row">
            <!-- Water Consumption Chart -->
            <div class="col-lg-6 mb-4">
                <div class="card shadow">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">Water Consumption per Area</h6>
                    </div>
                    <div class="card-body">
                        <canvas id="waterChart" height="250"></canvas>
                    </div>
                </div>
            </div>

            <!-- Registered Consumers per Month -->
            <div class="col-lg-6 mb-4">
                <div class="card shadow">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">Registered Consumers per Month</h6>
                    </div>
                    <div class="card-body">
                        <canvas id="consumerChart" height="250"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activities -->
        <div class="row">
            <div class="col-lg-12 mb-4">
                <div class="card shadow">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">Recent Activities</h6>
                    </div>
                    <div class="card-body">
                        <div id="activitiesContainer" class="list-group list-group-flush">
                            <?php if (!empty($recent_activities)): ?>
                                <?php foreach ($recent_activities as $activity): ?>
                                    <div class="list-group-item px-0">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1"><?= htmlspecialchars($activity['Action']); ?></h6>
                                            <small class="text-muted"><?= date('M d, Y h:i A', strtotime($activity['Timestamp'])); ?></small>
                                        </div>
                                        <small class="text-muted">IP: <?= htmlspecialchars($activity['IPAddress']); ?></small>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <p class="text-muted">No recent activities found</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Water Consumption per Area
const waterCtx = document.getElementById('waterChart').getContext('2d');
new Chart(waterCtx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($water_labels); ?>,
        datasets: [{
            label: 'Consumption (m³)',
            data: <?= json_encode($water_data); ?>,
            backgroundColor: 'rgba(54, 162, 235, 0.6)',
            borderColor: 'rgba(54, 162, 235, 1)',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true }
        }
    }
});

// Registered Consumers per Month (Line Chart)
const consumerCtx = document.getElementById('consumerChart').getContext('2d');
new Chart(consumerCtx, {
    type: 'line',
    data: {
        labels: <?= json_encode($consumer_month_labels); ?>,
        datasets: [{
            label: 'Registered Consumers',
            data: <?= json_encode($consumer_month_data); ?>,
            backgroundColor: 'rgba(255, 99, 132, 0.2)',
            borderColor: 'rgba(255, 99, 132, 1)',
            borderWidth: 2,
            fill: true,
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: true } },
        scales: {
            y: { beginAtZero: true, precision: 0 }
        }
    }
});
</script>

<?php include '../includes/footer.php'; ?>
