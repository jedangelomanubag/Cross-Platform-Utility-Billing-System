<?php
require_once '../config/db.php';
require_once '../functions/admin_functions.php';

// Ensure admin is logged in
requireAdminLogin();

// Set page title
$page_title = 'Dashboard';

// === Hardcoded Barangay list for Iligan City (shared with reports) ===
$barangay_map = [
    'Abuno'              => ['Abuno'],
    'Acmac‑Mariano Badelles Sr.' => ['Acmac', 'Acmac‑Mariano Badelles', 'Acmac Mariano Badelles Sr'],
    'Bagong Silang'      => ['Bagong Silang', 'BagongSilang', 'Silang'],
    'Bonbonon'           => ['Bonbonon'],
    'Bunawan'            => ['Bunawan'],
    'Buru-un'            => ['Buru-un', 'Buruun'],
    'Dalipuga'           => ['Dalipuga'],
    'Del Carmen'         => ['Del Carmen', 'DelCarmen'],
    'Digkilaan'          => ['Digkilaan'],
    'Ditucalan'          => ['Ditucalan'],
    'Dulag'              => ['Dulag'],
    'Hinaplanon'         => ['Hinaplanon', 'Upper Hinaplanon', 'Hinaplanon Upper'],
    'Hindang'            => ['Hindang'],
    'Kabacsanan'         => ['Kabacsanan'],
    'Kalilangan'         => ['Kalilangan'],
    'Kiwalan'            => ['Kiwalan'],
    'Lanipao'            => ['Lanipao'],
    'Luinab'             => ['Luinab'],
    'Mahayahay'          => ['Mahayahay'],
    'Mainit'             => ['Mainit'],
    'Mandulog'           => ['Mandulog'],
    'Maria Cristina'     => ['Maria Cristina', 'MariaCristina'],
    'Pala-o'             => ['Pala-o', 'Palao', 'Pala o'],
    'Panoroganan'        => ['Panoroganan'],
    'Poblacion'          => ['Poblacion', 'Pob.', 'Downtown'],
    'Puga-an'            => ['Puga-an', 'Pugaan', 'Puga an'],
    'Rogongon'           => ['Rogongon'],
    'San Miguel'         => ['San Miguel', 'SanMiguel'],
    'San Roque'          => ['San Roque', 'SanRoque'],
    'Santa Elena'        => ['Sta. Elena', 'Santa Elena', 'Sta Elena'],
    'Santa Filomena'     => ['Santa Filomena', 'Sta. Filomena', 'SantaF'],
    'Santiago'           => ['Santiago'],
    'Saray‑Tibanga'      => ['Saray', 'Tibanga', 'Saray Tibanga', 'Saray‑Tibanga'],
    'Santo Rosario'      => ['Santo Rosario', 'Sto. Rosario', 'SantoRosario'],
    'Suarez'             => ['Suarez'],
    'Tambacan'           => ['Tambacan'],
    'Tibanga'            => ['Tibanga', 'Tibanga (Canaway)', 'Canaway', 'franciscan'],
    'Tipanoy'            => ['Tipanoy'],
    'Tomas L. Cabili'    => ['Tomas Cabili', 'Tomas L Cabili', 'Cabili'],
    'Tubod'              => ['Tubod','Baraas'],
    'Ubaldo Laya'        => ['Ubaldo Laya', 'UbaldoLaya'],
    'Upper Tominobo'     => ['Upper Tominobo', 'Tominobo Upper'],
    'Upper Hinaplanon'   => ['Upper Hinaplanon', 'Hinaplanon Upper'],
    'Villa Verde'        => ['Villa Verde', 'Villaverde', 'VillaVerde'],
];

function detectBarangayDashboard($address, $map) {
    if (empty($address)) {
        return 'Unassigned';
    }

    $addr = strtolower($address);

    foreach ($map as $barangay => $keywords) {
        foreach ($keywords as $kw) {
            $kw = strtolower($kw);
            if (substr($addr, -strlen($kw)) === $kw) {
                return $barangay;
            }
        }
    }

    foreach ($map as $barangay => $keywords) {
        foreach ($keywords as $kw) {
            if (stripos($addr, strtolower($kw)) !== false) {
                return $barangay;
            }
        }
    }

    return 'Unassigned';
}

// ------------------- Summary Cards -------------------

// Get total consumers
$counts['consumers'] = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM consumer"))['count'];

// Get total utility readers
$counts['readers'] = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM utilityreader"))['count'];

// Get total meters
$counts['meters'] = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM meter"))['count'];

// Get billing statements snapshot
$billing_snapshot = mysqli_fetch_assoc(mysqli_query($conn, "SELECT 
                                                                COUNT(*) AS count, 
                                                                COALESCE(SUM(TotalAmount), 0) AS total_amount 
                                                            FROM billingstatement"));
$counts['billing_statements'] = $billing_snapshot['count'] ?? 0;
$counts['billing_total_amount'] = $billing_snapshot['total_amount'] ?? 0;

// ------------------- Analytics Data (no filters) -------------------
$consumers_per_year = [];
$consumption_per_year = [];
$barangay_consumption = [];
$status_per_year = [];
$status_labels = [];

$billing_query = "SELECT b.BillingDate, b.BillingPeriod, b.Consumption, b.PaymentStatus, c.Address
                  FROM billingstatement b
                  JOIN meter m ON b.MeterID = m.MeterID
                  JOIN consumer c ON m.ConsumerID = c.ConsumerID";
$billing_result = $conn->query($billing_query);

if ($billing_result) {
    while ($row = $billing_result->fetch_assoc()) {
        $billing_year = null;
        if (!empty($row['BillingDate'])) {
            $billing_year = date('Y', strtotime($row['BillingDate']));
        } elseif (!empty($row['BillingPeriod'])) {
            $timestamp = strtotime($row['BillingPeriod']);
            if ($timestamp !== false) {
                $billing_year = date('Y', $timestamp);
            }
        }

        if ($billing_year === null) {
            continue;
        }

        if (!isset($consumers_per_year[$billing_year])) {
            $consumers_per_year[$billing_year] = 0;
        }
        $consumers_per_year[$billing_year]++;

        if (!isset($consumption_per_year[$billing_year])) {
            $consumption_per_year[$billing_year] = 0;
        }
        $consumption_per_year[$billing_year] += $row['Consumption'];

        $barangay = detectBarangayDashboard($row['Address'], $barangay_map);
        if (!isset($barangay_consumption[$barangay])) {
            $barangay_consumption[$barangay] = 0;
        }
        $barangay_consumption[$barangay] += $row['Consumption'];

        $status = !empty($row['PaymentStatus']) ? ucwords(strtolower($row['PaymentStatus'])) : 'Unspecified';
        $status_labels[$status] = true;
        if (!isset($status_per_year[$billing_year])) {
            $status_per_year[$billing_year] = [];
        }
        if (!isset($status_per_year[$billing_year][$status])) {
            $status_per_year[$billing_year][$status] = 0;
        }
        $status_per_year[$billing_year][$status]++;
    }
}

$all_years = array_keys($consumers_per_year);
if (!empty($all_years)) {
    $min_year = min($all_years);
    $max_year = max($all_years);
} else {
    $min_year = $max_year = intval(date('Y'));
}

for ($y = $min_year; $y <= $max_year; $y++) {
    if (!isset($consumers_per_year[$y])) {
        $consumers_per_year[$y] = 0;
    }
    if (!isset($consumption_per_year[$y])) {
        $consumption_per_year[$y] = 0;
    }
    if (!isset($status_per_year[$y])) {
        $status_per_year[$y] = [];
    }
}

ksort($consumers_per_year);
ksort($consumption_per_year);
ksort($status_per_year);

$status_label_list = array_keys($status_labels);
sort($status_label_list);
foreach ($status_per_year as $year_key => $statuses) {
    foreach ($status_label_list as $label) {
        if (!isset($status_per_year[$year_key][$label])) {
            $status_per_year[$year_key][$label] = 0;
        }
    }
    ksort($status_per_year[$year_key]);
}

$graph_data = [
    'consumers_per_year' => $consumers_per_year,
    'consumption_per_year' => $consumption_per_year,
    'barangay_consumption' => $barangay_consumption,
    'status_per_year' => $status_per_year,
    'status_labels' => $status_label_list,
    'selected_years' => array_keys($consumers_per_year)
];

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

            <!-- Billing Statements Card -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-warning shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Billing Statements</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($counts['billing_statements']); ?></div>
                                <small class="text-muted">Total Amount: ₱<?= number_format($counts['billing_total_amount'], 2); ?></small>
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

        <!-- Analytics Dashboard -->
        <div class="card mb-4">
            <div class="card-header"><strong>Analytics Dashboard</strong></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3 analytics-chart-wrapper">
                        <h6 class="text-center">Consumers Per Year</h6>
                        <canvas id="dashConsumersPerYearChart" height="200"></canvas>
                    </div>
                    <div class="col-md-6 mb-3 analytics-chart-wrapper">
                        <h6 class="text-center">Total Consumption Per Year</h6>
                        <canvas id="dashConsumptionPerYearChart" height="200"></canvas>
                    </div>
                    <div class="col-md-6 mb-3 analytics-chart-wrapper">
                        <h6 class="text-center">Barangay Distribution (Top 10)</h6>
                        <canvas id="dashBarangayPieChart" height="200"></canvas>
                    </div>
                    <div class="col-md-6 mb-3 analytics-chart-wrapper">
                        <h6 class="text-center">Payment Status Per Year</h6>
                        <canvas id="dashStatusPerYearChart" height="200"></canvas>
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

<style>
.analytics-chart-wrapper {
    min-height: 280px;
    display: flex;
    flex-direction: column;
    justify-content: flex-start;
}

.analytics-chart-wrapper canvas {
    width: 100% !important;
    height: 240px !important;
}
</style>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', initDashboardCharts);

function initDashboardCharts() {
    const graphData = <?= json_encode($graph_data) ?>;
    const palette = [
        '#4BC0C0', '#36A2EB', '#FF6384', '#FF9F40',
        '#9966FF', '#00A36C', '#C9CBCF', '#FFCD56'
    ];

    const sortedYears = graphData.selected_years || Object.keys(graphData.consumers_per_year || {}).sort();

    const chartConfigs = [
        {
            ctxId: 'dashConsumersPerYearChart',
            type: 'line',
            data: {
                labels: sortedYears,
                datasets: [{
                    label: 'Number of Consumers',
                    data: sortedYears.map(year => graphData.consumers_per_year[year] || 0),
                    borderColor: palette[0],
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    tension: 0.1
                }]
            },
            title: 'Consumers Per Year'
        },
        {
            ctxId: 'dashConsumptionPerYearChart',
            type: 'line',
            data: {
                labels: sortedYears,
                datasets: [{
                    label: 'Total Consumption (m³)',
                    data: sortedYears.map(year => graphData.consumption_per_year[year] || 0),
                    borderColor: palette[1],
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    tension: 0.1
                }]
            },
            title: 'Total Consumption Per Year'
        },
        buildBarangayChartConfig(graphData, palette),
        buildStatusChartConfig(graphData, palette)
    ];

    chartConfigs.forEach(config => renderChart(config));
}

function buildBarangayChartConfig(graphData, palette) {
    const sortedBarangays = Object.entries(graphData.barangay_consumption || {})
        .sort((a, b) => b[1] - a[1])
        .slice(0, 10);

    return {
        ctxId: 'dashBarangayPieChart',
        type: 'pie',
        data: {
            labels: sortedBarangays.map(item => item[0]),
            datasets: [{
                data: sortedBarangays.map(item => item[1]),
                backgroundColor: sortedBarangays.map((_, idx) => palette[idx % palette.length]),
                borderWidth: 1
            }]
        },
        title: 'Barangay Distribution (Top 10)',
        legendBottom: true
    };
}

function buildStatusChartConfig(graphData, palette) {
    const years = Object.keys(graphData.status_per_year || {}).sort();
    const statusLabels = graphData.status_labels || [];
    const datasets = statusLabels.map((label, idx) => ({
        label,
        data: years.map(year => (graphData.status_per_year[year] || {})[label] || 0),
        ...getStatusColors(label, palette[idx % palette.length]),
        tension: 0.1
    }));

    return {
        ctxId: 'dashStatusPerYearChart',
        type: 'line',
        data: {
            labels: years,
            datasets
        },
        title: 'Payment Status Per Year',
        legendBottom: true
    };
}

const pieValuePlugin = {
    id: 'pieValuePlugin',
    afterDatasetsDraw(chart) {
        const { ctx } = chart;
        chart.data.datasets.forEach(dataset => {
            const meta = chart.getDatasetMeta(chart.data.datasets.indexOf(dataset));
            meta.data.forEach((element, index) => {
                const value = dataset.data[index];
                if (value === undefined || value === null) return;
                const position = element.tooltipPosition();
                ctx.save();
                ctx.fillStyle = '#fff';
                ctx.font = 'bold 12px sans-serif';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.fillText(formatNumber(value), position.x, position.y);
                ctx.restore();
            });
        });
    }
};

function formatNumber(value) {
    const number = Number(value) || 0;
    if (number >= 1000) {
        return number.toLocaleString();
    }
    return number % 1 === 0 ? number.toString() : number.toFixed(2);
}

function getStatusColors(label, fallback) {
    const normalized = (label || '').toLowerCase();
    const colors = {
        paid: '#4CAF50',
        unpaid: '#FFEB3B',
        overdue: '#F44336'
    };

    const baseColor = colors[normalized] || fallback;
    return {
        borderColor: baseColor,
        backgroundColor: hexToRgba(baseColor, 0.25)
    };
}

function hexToRgba(hex, alpha) {
    const bigint = parseInt(hex.slice(1), 16);
    const r = (bigint >> 16) & 255;
    const g = (bigint >> 8) & 255;
    const b = bigint & 255;
    return `rgba(${r}, ${g}, ${b}, ${alpha})`;
}

function renderChart({ ctxId, type, data, title, legendBottom }) {
    const ctx = document.getElementById(ctxId);
    if (!ctx) return;

    new Chart(ctx, {
        type,
        data,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: title || ''
                },
                legend: legendBottom ? { position: 'bottom' } : {}
            },
            scales: type === 'pie' ? {} : {
                y: {
                    beginAtZero: true
                }
            }
        },
        plugins: type === 'pie' ? [pieValuePlugin] : []
    });
}
</script>

<?php include '../includes/footer.php'; ?>
