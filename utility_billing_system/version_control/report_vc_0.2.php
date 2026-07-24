
<?php
// reports.php
require_once '../config/db.php';
require_once '../functions/admin_functions.php';
requireAdminLogin();

$page_title = 'Billing Reports';

// === Hardcoded Barangay list for Iligan City ===
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

// Improved detectBarangay function
function detectBarangay($address, $map) {
    if (empty($address)) return 'Unassigned';

    $addr = strtolower($address);

    // First, check if any keyword matches the **end of address**
    foreach ($map as $barangay => $keywords) {
        foreach ($keywords as $kw) {
            $kw = strtolower($kw);
            if (substr($addr, -strlen($kw)) === $kw) {
                return $barangay;
            }
        }
    }

    // Fallback: check if any keyword exists anywhere
    foreach ($map as $barangay => $keywords) {
        foreach ($keywords as $kw) {
            if (stripos($addr, $kw) !== false) {
                return $barangay;
            }
        }
    }

    return 'Unassigned';
}

// Helper function to format period display
function formatPeriodDisplay($month, $year, $start_year, $end_year) {
    if ($year === 'range' && !empty($start_year) && !empty($end_year)) {
        $year_range = ($start_year == $end_year) ? $start_year : "$start_year-$end_year";
        return ($month === 'all') ? "All Months $year_range" : date('F', mktime(0,0,0,$month,1)) . " $year_range";
    } else {
        return ($month === 'all') ? "All Months $year" : date('F', mktime(0,0,0,$month,1)) . " $year";
    }
}

// === Fetch filters from request ===
$month          = $_GET['month'] ?? date('m');
$year           = $_GET['year'] ?? date('Y');
$start_year     = $_GET['start_year'] ?? '';
$end_year       = $_GET['end_year'] ?? '';
$status_filter  = $_GET['status'] ?? '';
$barangay_filter = $_GET['barangay'] ?? '';
$selected_years = [];

if ($year === 'range' && !empty($start_year) && !empty($end_year)) {
    $range_start = intval($start_year);
    $range_end   = intval($end_year);
    if ($range_start > $range_end) {
        [$range_start, $range_end] = [$range_end, $range_start];
    }
    for ($y = $range_start; $y <= $range_end; $y++) {
        $selected_years[] = $y;
    }
} else {
    $selected_years[] = intval($year);
}

if (empty($selected_years)) {
    $selected_years[] = intval(date('Y'));
}

// Build date pattern
if ($year === 'range' && !empty($start_year) && !empty($end_year)) {
    // Year range selected
    if ($month === 'all') {
        // All months for year range
        $sql_conditions = [];
        for ($y = intval($start_year); $y <= intval($end_year); $y++) {
            $sql_conditions[] = "b.BillingDate LIKE '$y-%'";
        }
        $date_condition = "(" . implode(" OR ", $sql_conditions) . ")";
    } else {
        // Specific month for year range
        $sql_conditions = [];
        for ($y = intval($start_year); $y <= intval($end_year); $y++) {
            $sql_conditions[] = "b.BillingDate LIKE '" . sprintf("%04d-%02d-%%", $y, intval($month)) . "'";
        }
        $date_condition = "(" . implode(" OR ", $sql_conditions) . ")";
    }
} else {
    // Single year selected
    if ($month === 'all') {
        $date_condition = "b.BillingDate LIKE '" . sprintf("%04d-%%", intval($year)) . "'";
    } else {
        $date_condition = "b.BillingDate LIKE '" . sprintf("%04d-%02d-%%", intval($year), intval($month)) . "'";
    }
}

// === Billing + consumer query with server-side barangay filter ===
$sql = "SELECT b.BillingID,
               c.FirstName, c.LastName, c.Address,
               b.BillingPeriod,
               b.BillingDate,
               b.Consumption,
               b.TotalAmount,
               b.PaymentStatus
        FROM billingstatement b
        JOIN meter m ON b.MeterID = m.MeterID
        JOIN consumer c ON m.ConsumerID = c.ConsumerID
        WHERE $date_condition";

// Status filter
$params = [];
$types = "";

if (!empty($status_filter)) {
    $sql .= " AND b.PaymentStatus = ?";
    $params[] = $status_filter;
    $types .= "s";
}

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$billingResult = $stmt->get_result();

// === Prepare summary totals from filtered rows ===
$total_bills = 0;
$total_consumption = 0;
$total_revenue = 0;
$filtered_rows = [];

while ($row = $billingResult->fetch_assoc()) {
    $detectedBarangay = detectBarangay($row['Address'], $barangay_map);
    // Apply server-side barangay filter
    if (!empty($barangay_filter) && strtolower($detectedBarangay) !== strtolower($barangay_filter)) {
        continue;
    }
    $row['DetectedBarangay'] = $detectedBarangay;
    $filtered_rows[] = $row;

    $total_bills++;
    $total_consumption += $row['Consumption'];
    $total_revenue += $row['TotalAmount'];
}

// === Prepare data for graphs ===
$consumers_per_year = [];
$consumption_per_year = [];
$barangay_consumption = [];
$status_per_year = [];
$status_labels = [];

// Process filtered data for graphs
foreach ($filtered_rows as $row) {
    // Extract year from billing date first, fallback to billing period
    $billing_year = null;
    if (!empty($row['BillingDate'])) {
        $billing_year = date('Y', strtotime($row['BillingDate']));
    } elseif (!empty($row['BillingPeriod'])) {
        $period_timestamp = strtotime($row['BillingPeriod']);
        if ($period_timestamp !== false) {
            $billing_year = date('Y', $period_timestamp);
        }
    }

    if ($billing_year === null) {
        continue;
    }
    
    // Consumers per year
    if (!isset($consumers_per_year[$billing_year])) {
        $consumers_per_year[$billing_year] = 0;
    }
    $consumers_per_year[$billing_year]++;
    
    // Consumption per year
    if (!isset($consumption_per_year[$billing_year])) {
        $consumption_per_year[$billing_year] = 0;
    }
    $consumption_per_year[$billing_year] += $row['Consumption'];
    
    // Barangay consumption
    $barangay = $row['DetectedBarangay'];
    if (!isset($barangay_consumption[$barangay])) {
        $barangay_consumption[$barangay] = 0;
    }
    $barangay_consumption[$barangay] += $row['Consumption'];
    
    // Status per year
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

// Ensure arrays include all selected years even if zero data
foreach ($selected_years as $yr) {
    if (!isset($consumers_per_year[$yr])) {
        $consumers_per_year[$yr] = 0;
    }
    if (!isset($consumption_per_year[$yr])) {
        $consumption_per_year[$yr] = 0;
    }
    if (!isset($status_per_year[$yr])) {
        $status_per_year[$yr] = [];
    }
}

// Sort years for consistent display
ksort($consumers_per_year);
ksort($consumption_per_year);
ksort($status_per_year);

// Ensure each year's status bucket includes all detected statuses
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

// Prepare data for JavaScript
$graph_data = [
    'consumers_per_year' => $consumers_per_year,
    'consumption_per_year' => $consumption_per_year,
    'barangay_consumption' => $barangay_consumption,
    'status_per_year' => $status_per_year,
    'status_labels' => $status_label_list,
    'selected_years' => array_values($consumers_per_year ? array_keys($consumers_per_year) : $selected_years)
];

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
  <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?= $page_title ?></h1>
  </div>

  <!-- Filter Form -->
  <div class="card mb-4">
    <div class="card-body row g-3">
      <div class="col-md-2">
        <label for="monthSelect" class="form-label">Month</label>
        <select class="form-select" id="monthSelect">
          <option value="all" <?= ($month == 'all') ? 'selected' : '' ?>>All</option>
          <?php for ($m=1; $m<=12; $m++): ?>
            <option value="<?= $m ?>" <?= ($m == $month) ? 'selected' : '' ?>>
              <?= date('F', mktime(0,0,0,$m,1)) ?>
            </option>
          <?php endfor; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label for="yearSelect" class="form-label">Year</label>
        <select class="form-select" id="yearSelect">
          <option value="range" <?= ($year === 'range') ? 'selected' : '' ?>>Year Range</option>
          <?php for ($y=2020; $y<=date('Y'); $y++): ?>
            <option value="<?= $y ?>" <?= ($y == $year && $year !== 'range') ? 'selected' : '' ?>>
              <?= $y ?>
            </option>
          <?php endfor; ?>
        </select>
      </div>
      <div class="col-md-3" id="yearRangeContainer" style="display: none;">
        <label class="form-label">Year Range</label>
        <div class="input-group">
          <select class="form-select" id="startYear">
            <?php for ($y=2020; $y<=date('Y'); $y++): ?>
              <option value="<?= $y ?>" <?= (!empty($start_year) && $y == $start_year) ? 'selected' : '' ?>><?= $y ?></option>
            <?php endfor; ?>
          </select>
          <span class="input-group-text">to</span>
          <select class="form-select" id="endYear">
            <?php for ($y=2020; $y<=date('Y'); $y++): ?>
              <option value="<?= $y ?>" <?= (!empty($end_year) && $y == $end_year) ? 'selected' : ($y == date('Y') ? 'selected' : '') ?>><?= $y ?></option>
            <?php endfor; ?>
          </select>
        </div>
      </div>
      <div class="col-md-2">
        <label for="statusSelect" class="form-label">Payment Status</label>
        <select class="form-select" id="statusSelect">
          <option value="">All</option>
          <option value="Paid"    <?= ($status_filter == 'Paid') ? 'selected' : '' ?>>Paid</option>
          <option value="Unpaid"  <?= ($status_filter == 'Unpaid') ? 'selected' : '' ?>>Unpaid</option>
          <option value="Overdue" <?= ($status_filter == 'Overdue') ? 'selected' : '' ?>>Overdue</option>
        </select>
      </div>
      <div class="col-md-3">
        <label for="barangaySelect" class="form-label">Barangay</label>
        <select class="form-select" id="barangaySelect">
          <option value="">All Barangays</option>
          <?php foreach (array_keys($barangay_map) as $brgy): ?>
            <option value="<?= htmlspecialchars($brgy) ?>" <?= ($barangay_filter == $brgy) ? 'selected' : '' ?>>
              <?= htmlspecialchars($brgy) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3 d-flex align-items-end">
        <button class="btn btn-primary w-100" id="filterBtn">Filter</button>
      </div>
    </div>
  </div>

  <!-- Print & CSV Buttons -->
  <div class="mb-3">
    <button class="btn btn-success me-2" onclick="window.print()">
      <i class="fas fa-print"></i> Print Report
    </button>
    <button class="btn btn-info" id="exportExcelBtn">
      <i class="fas fa-file-excel"></i> Export Excel
    </button>
  </div>

  <!-- Summary -->
  <div class="card mb-4">
    <div class="card-header">
      <strong><?= formatPeriodDisplay($month, $year, $start_year, $end_year) ?> Summary</strong>
    </div>
    <div class="card-body row text-center">
      <div class="col-md-4">
        <h5>Total Bills</h5>
        <p><?= $total_bills ?></p>
      </div>
      <div class="col-md-4">
        <h5>Total Consumption (m³)</h5>
        <p><?= $total_consumption ?></p>
      </div>
      <div class="col-md-4">
        <h5>Total Revenue (₱)</h5>
        <p><?= number_format($total_revenue, 2) ?></p>
      </div>
    </div>
  </div>

  <!-- Analytics Graphs -->
  <div class="card mb-4">
    <div class="card-header"><strong>Analytics Dashboard</strong></div>
    <div class="card-body">
      <div class="row">
        <div class="col-md-6 mb-3 analytics-chart-wrapper">
          <h6 class="text-center">Consumers Per Year</h6>
          <canvas id="screenConsumersPerYearChart" height="200"></canvas>
        </div>
        <div class="col-md-6 mb-3 analytics-chart-wrapper">
          <h6 class="text-center">Total Consumption Per Year</h6>
          <canvas id="screenConsumptionPerYearChart" height="200"></canvas>
        </div>
        <div class="col-md-6 mb-3 analytics-chart-wrapper">
          <h6 class="text-center">Barangay Distribution (Top 10)</h6>
          <canvas id="screenBarangayPieChart" height="200"></canvas>
        </div>
        <div class="col-md-6 mb-3 analytics-chart-wrapper">
          <h6 class="text-center">Payment Status Per Year</h6>
          <canvas id="screenStatusPerYearChart" height="200"></canvas>
        </div>
      </div>
    </div>
  </div>

  <!-- Billing Table -->
  <div class="card">
    <div class="card-header"><strong>Billing Details</strong></div>
    <div class="card-body table-responsive">
      <input type="text" id="searchInput" class="form-control mb-2" placeholder="Search by consumer name or billing period">
      <table class="table table-striped table-hover" id="billingTable">
        <thead>
          <tr>
            <th>BillingID</th>
            <th>Consumer</th>
            <th>Period</th>
            <th>Consumption (m³)</th>
            <th>Total Amount (₱)</th>
            <th>Status</th>
            <th>Barangay</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($filtered_rows)): ?>
            <?php foreach ($filtered_rows as $row): 
              $status = strtolower($row['PaymentStatus']);
              $badge  = 'secondary';
              if ($status === 'paid')    $badge = 'success';
              elseif ($status === 'unpaid')  $badge = 'warning';
              elseif ($status === 'overdue') $badge = 'danger';
            ?>
              <tr data-name="<?= strtolower($row['FirstName'].' '.$row['LastName']) ?>"
                  data-period="<?= strtolower($row['BillingPeriod']) ?>"
                  data-status="<?= $status ?>"
                  data-barangay="<?= strtolower($row['DetectedBarangay']) ?>">
                <td><?= htmlspecialchars($row['BillingID']) ?></td>
                <td><?= htmlspecialchars($row['FirstName'].' '.$row['LastName']) ?></td>
                <td><?= htmlspecialchars($row['BillingPeriod']) ?></td>
                <td><?= htmlspecialchars($row['Consumption']) ?></td>
                <td><?= number_format($row['TotalAmount'],2) ?></td>
                <td><span class="badge bg-<?= $badge ?>"><?= htmlspecialchars($row['PaymentStatus']) ?></span></td>
                <td><?= htmlspecialchars($row['DetectedBarangay']) ?></td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr><td colspan="7" class="text-center text-muted">No billing statements found.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Official Print Layout (Hidden by default, shown only when printing) -->
  <div class="print-only" id="officialReport">
    <table class="main-report-table" border="2" cellpadding="0" cellspacing="0" width="100%">
      <tr>
        <td colspan="3" class="header-section">
          <table class="header-content-table" border="1" cellpadding="5" cellspacing="0" width="100%">
            <tr>
              <td rowspan="2" class="logo-cell" width="15%" align="center" valign="middle">
                <img src="../assets/images/ilwd-logo.jpg" alt="ILWD Logo" class="logo-img" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';" width="60" height="60">
                <div style="display:none; width: 60px; height: 60px; border: 2px solid #0056b3; border-radius: 8px; background-color: white; align-items: center; justify-content: center; font-weight: bold; color: #0056b3; font-size: 14px; margin: 0 auto; font-family: Arial;">
                  ILWD
                </div>
              </td>
              <td class="title-cell" width="55%" align="center" valign="middle">
                <div style="font-size: 14pt; font-weight: bold; text-transform: uppercase;">ILIGAN CITY WATERWORKS SYSTEM</div>
                <div style="font-size: 12pt; font-weight: bold; text-transform: uppercase; margin-top: 5px;">MONTHLY BILLING AND COLLECTION REPORT</div>
              </td>
              <td class="info-cell" width="30%" valign="top">
                <table class="info-details-table" border="1" cellpadding="3" cellspacing="0" width="100%">
                  <tr>
                    <td width="40%"><strong>Period:</strong></td>
                    <td width="60%"><?= formatPeriodDisplay($month, $year, $start_year, $end_year) ?></td>
                  </tr>
                  <tr>
                    <td><strong>Barangay:</strong></td>
                    <td><?= $barangay_filter ?: 'All Barangays' ?></td>
                  </tr>
                  <tr>
                    <td><strong>Generated:</strong></td>
                    <td><?= date('Y-m-d H:i:s') ?></td>
                  </tr>
                </table>
              </td>
            </tr>
          </table>
        </td>
      </tr>
      
      <tr>
        <td colspan="3" class="section-header" align="center" style="background-color: #000; color: #fff; font-weight: bold; padding: 8px;">
          SUMMARY INFORMATION
        </td>
      </tr>
      <tr>
        <td colspan="3">
          <table class="summary-info-table" border="1" cellpadding="6" cellspacing="0" width="100%">
            <tr>
              <td width="20%"><strong>Total Bills:</strong> <?= number_format($total_bills) ?></td>
              <td width="25%"><strong>Total Consumption (m³):</strong> <?= number_format($total_consumption) ?></td>
              <td width="25%"><strong>Total Revenue (₱):</strong> <?= number_format($total_revenue, 2) ?></td>
              <td width="30%"><strong>Date Generated:</strong> <?= date('F j, Y - g:i A') ?></td>
            </tr>
          </table>
        </td>
      </tr>

      <tr>
        <td colspan="3" class="section-header" align="center" style="background-color: #000; color: #fff; font-weight: bold; padding: 8px;">
          ANALYTICAL GRAPHS
        </td>
      </tr>
      <tr>
        <td colspan="3">
          <table class="graphs-table" border="1" cellpadding="10" cellspacing="0" width="100%">
            <tr>
              <td width="50%" align="center" valign="top">
                <div style="margin-bottom: 10px;">
                  <strong>Consumers Per Year</strong>
                </div>
                <img id="printConsumersChart" alt="Consumers Per Year" style="max-width:100%; border:1px solid #ccc;">
              </td>
              <td width="50%" align="center" valign="top">
                <div style="margin-bottom: 10px;">
                  <strong>Total Consumption Per Year</strong>
                </div>
                <img id="printConsumptionChart" alt="Total Consumption Per Year" style="max-width:100%; border:1px solid #ccc;">
              </td>
            </tr>
            <tr>
              <td width="50%" align="center" valign="top">
                <div style="margin-bottom: 10px;">
                  <strong>Barangay Distribution</strong>
                </div>
                <img id="printBarangayChart" alt="Barangay Distribution" style="max-width:100%; border:1px solid #ccc;">
              </td>
              <td width="50%" align="center" valign="top">
                <div style="margin-bottom: 10px;">
                  <strong>Payment Status Per Year</strong>
                </div>
                <img id="printStatusChart" alt="Payment Status Per Year" style="max-width:100%; border:1px solid #ccc;">
              </td>
            </tr>
          </table>
        </td>
      </tr>

      <tr>
        <td colspan="3" class="section-header" align="center" style="background-color: #000; color: #fff; font-weight: bold; padding: 8px;">
          BILLING RECORDS
        </td>
      </tr>
      <tr>
        <td colspan="3">
          <table class="billing-records-table" border="1" cellpadding="4" cellspacing="0" width="100%">
            <thead>
              <tr style="background-color: #000; color: #fff; font-weight: bold;">
                <th width="10%" align="center">Billing ID</th>
                <th width="25%" align="center">Consumer Name</th>
                <th width="15%" align="center">Billing Period</th>
                <th width="12%" align="center">Consumption (m³)</th>
                <th width="15%" align="center">Total Amount</th>
                <th width="13%" align="center">Payment Status</th>
                <th width="10%" align="center">Barangay</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($filtered_rows)): ?>
                <?php foreach ($filtered_rows as $row): ?>
                  <tr>
                    <td align="center"><?= htmlspecialchars($row['BillingID']) ?></td>
                    <td><?= htmlspecialchars($row['FirstName'].' '.$row['LastName']) ?></td>
                    <td align="center"><?= htmlspecialchars($row['BillingPeriod']) ?></td>
                    <td align="center"><?= number_format($row['Consumption']) ?></td>
                    <td align="right"><?= number_format($row['TotalAmount'], 2) ?></td>
                    <td align="center"><?= htmlspecialchars($row['PaymentStatus']) ?></td>
                    <td><?= htmlspecialchars($row['DetectedBarangay']) ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="7" align="center" style="font-style: italic; background-color: #f9f9f9;">No billing records found for the selected criteria.</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </td>
      </tr>

      <tr>
        <td colspan="3" class="section-header" align="center" style="background-color: #000; color: #fff; font-weight: bold; padding: 8px;">
          CERTIFICATION
        </td>
      </tr>
      <tr>
        <td colspan="3">
          <table class="signature-table" border="1" cellpadding="10" cellspacing="0" width="100%">
            <tr>
              <td width="33%" align="center" valign="top">
                <div style="margin-bottom: 5px;">
                  <div style="border-bottom: 1px solid #000; height: 40px; margin-bottom: 8px;"></div>
                  <p style="margin: 4px 0; font-size: 9pt;"><strong>Prepared By:</strong></p>
                  <p style="margin: 2px 0; font-size: 9pt;">Name & Signature</p>
                  <p style="margin: 2px 0; font-size: 9pt;">Date: _________________</p>
                </div>
              </td>
              <td width="33%" align="center" valign="top">
                <div style="margin-bottom: 5px;">
                  <div style="border-bottom: 1px solid #000; height: 40px; margin-bottom: 8px;"></div>
                  <p style="margin: 4px 0; font-size: 9pt;"><strong>Reviewed By:</strong></p>
                  <p style="margin: 2px 0; font-size: 9pt;">Name & Signature</p>
                  <p style="margin: 2px 0; font-size: 9pt;">Date: _________________</p>
                </div>
              </td>
              <td width="33%" align="center" valign="top">
                <div style="margin-bottom: 5px;">
                  <div style="border-bottom: 1px solid #000; height: 40px; margin-bottom: 8px;"></div>
                  <p style="margin: 4px 0; font-size: 9pt;"><strong>Approved By:</strong></p>
                  <p style="margin: 2px 0; font-size: 9pt;">Name & Signature</p>
                  <p style="margin: 2px 0; font-size: 9pt;">Date: _________________</p>
                </div>
              </td>
            </tr>
          </table>
        </td>
      </tr>
    </table>
  </div>

</main>

<!-- Chart.js Library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// Initialize year range visibility on page load
document.addEventListener('DOMContentLoaded', function(){
  const yearSelect = document.getElementById('yearSelect');
  const yearRangeContainer = document.getElementById('yearRangeContainer');
  
  if (yearSelect.value === 'range') {
    yearRangeContainer.style.display = 'block';
  }
  
  // Generate graphs
  generateGraphs();
});

// === Graph Generation Functions ===
function generateGraphs() {
  const graphData = <?= json_encode($graph_data) ?>;
  const palette = [
    '#4BC0C0', '#36A2EB', '#FF6384', '#FF9F40',
    '#9966FF', '#00A36C', '#C9CBCF', '#FFCD56'
  ];

  const sortedYears = Object.keys(graphData.consumers_per_year).sort();

  const chartConfigs = [
    {
      ctxId: 'screenConsumersPerYearChart',
      printImgId: 'printConsumersChart',
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
      ctxId: 'screenConsumptionPerYearChart',
      printImgId: 'printConsumptionChart',
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
    {
      ctxId: 'screenBarangayPieChart',
      printImgId: 'printBarangayChart',
      type: 'pie',
      data: (() => {
        const sortedBarangays = Object.entries(graphData.barangay_consumption)
          .sort((a, b) => b[1] - a[1])
          .slice(0, 10);
        return {
          labels: sortedBarangays.map(item => item[0]),
          datasets: [{
            data: sortedBarangays.map(item => item[1]),
            backgroundColor: sortedBarangays.map((_, idx) => palette[idx % palette.length]),
            borderWidth: 1
          }]
        };
      })(),
      title: 'Barangay Distribution (Top 10)',
      legendBottom: true
    }
  ];

  // Status chart config constructed separately due to multiple datasets
  const statusConfig = buildStatusChartConfig(graphData, palette);
  chartConfigs.push(statusConfig);

  chartConfigs.forEach(config => renderChart(config));
}

function buildStatusChartConfig(graphData, palette) {
  const years = Object.keys(graphData.status_per_year).sort();
  const statusLabels = graphData.status_labels || [];
  const datasets = statusLabels.map((label, idx) => ({
    label,
    data: years.map(year => (graphData.status_per_year[year] || {})[label] || 0),
    ...getStatusColors(label, palette[idx % palette.length]),
    tension: 0.1
  }));

  return {
    ctxId: 'screenStatusPerYearChart',
    printImgId: 'printStatusChart',
    type: 'line',
    data: {
      labels: years,
      datasets
    },
    title: 'Payment Status Per Year'
  };
}

function hexToRgba(hex, alpha) {
  const bigint = parseInt(hex.slice(1), 16);
  const r = (bigint >> 16) & 255;
  const g = (bigint >> 8) & 255;
  const b = bigint & 255;
  return `rgba(${r}, ${g}, ${b}, ${alpha})`;
}

function getStatusColors(label, fallback) {
  const normalized = label.toLowerCase();
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

function renderChart({ ctxId, printImgId, type, data, title, legendBottom }) {
  const ctx = document.getElementById(ctxId);
  if (!ctx) return;

  const chart = new Chart(ctx, {
    type,
    data,
    options: {
      responsive: true,
      maintainAspectRatio: false,
      animation: false,
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
    }
  });

  if (printImgId) {
    const imgEl = document.getElementById(printImgId);
    if (imgEl) {
      requestAnimationFrame(() => {
        imgEl.src = chart.toBase64Image('image/png', 1);
      });
    }
  }
}

// Filter and search + barangay + status
function filterBillingTable() {
  const search = document.getElementById('searchInput').value.toLowerCase();
  const statusFilter = document.getElementById('statusSelect').value.toLowerCase();
  const barangayFilter = document.getElementById('barangaySelect').value.toLowerCase();

  document.querySelectorAll('#billingTable tbody tr').forEach(row => {
    const name = row.dataset.name || '';
    const period = row.dataset.period || '';
    const status = row.dataset.status || '';
    const barangay = row.dataset.barangay || '';

    const matchesSearch = search === '' || name.includes(search) || period.includes(search);
    const matchesStatus = statusFilter === '' || status === statusFilter;
    const matchesBarangay = barangayFilter === '' || barangay === barangayFilter;

    row.style.display = (matchesSearch && matchesStatus && matchesBarangay) ? '' : 'none';
  });
}

// Bind events
document.getElementById('searchInput').addEventListener('keyup', filterBillingTable);
document.getElementById('statusSelect').addEventListener('change', filterBillingTable);
document.getElementById('barangaySelect').addEventListener('change', filterBillingTable);

// Year range toggle functionality
document.getElementById('yearSelect').addEventListener('change', function(){
  const yearRangeContainer = document.getElementById('yearRangeContainer');
  if (this.value === 'range') {
    yearRangeContainer.style.display = 'block';
  } else {
    yearRangeContainer.style.display = 'none';
  }
});

// Filter button (server reload)
document.getElementById('filterBtn').addEventListener('click', function(){
  const month = document.getElementById('monthSelect').value;
  const year  = document.getElementById('yearSelect').value;
  const status = document.getElementById('statusSelect').value;
  const barangay = document.getElementById('barangaySelect').value;
  let url = `?month=${month}&year=${year}`;
  
  // Add year range parameters if range is selected
  if (year === 'range') {
    const startYear = document.getElementById('startYear').value;
    const endYear = document.getElementById('endYear').value;
    url += `&start_year=${startYear}&end_year=${endYear}`;
  }
  
  if(status)   url += `&status=${status}`;
  if(barangay) url += `&barangay=${encodeURIComponent(barangay)}`;
  window.location.href = url;
});

// Excel Export using HTML Format with Image Support
document.getElementById('exportExcelBtn').addEventListener('click', function () {
  try {
    console.log('Excel export button clicked');
    
    // Create HTML Excel structure with image
    let htmlContent = `
    <html>
    <head>
      <meta charset="utf-8">
      <style>
        table { border-collapse: collapse; font-family: Arial, sans-serif; font-size: 11px; width: 100%; table-layout: auto; }
        td { border: 1px solid #000; padding: 5px; vertical-align: top; }
        .header { font-weight: bold; text-align: center; background-color: #f2f2f2; }
        .center { text-align: center; }
        .right { text-align: right; }
        .left { text-align: left; }
        .col-a { width: 40px; max-width: 60px; min-width: 30px; } /* Auto-size with limits for numbers */
        .col-b { width: auto; min-width: 200px; max-width: 500px; } /* Auto-size for consumer names */
        .col-c { width: auto; min-width: 100px; max-width: 300px; } /* Auto-size for addresses */
        .col-d { width: auto; min-width: 80px; max-width: 150px; } /* Auto-size for periods */
        .col-e { width: auto; min-width: 60px; max-width: 100px; } /* Auto-size for consumption */
        .col-f { width: auto; min-width: 80px; max-width: 150px; } /* Auto-size for amounts */
      </style>
    </head>
    <body>
      <table>
        <!-- Header Section with Logo -->
        <tr>
          <td rowspan="2" style="width: 80px; text-align: center; vertical-align: middle; border: 1px solid #000;">
            <img src="../assets/images/ilwd-logo.jpg" alt="ILWD Logo" style="width: 60px; height: 60px; object-fit: contain;" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
            <div style="display:none; width: 60px; height: 60px; border: 2px solid #0056b3; border-radius: 8px; background-color: white; display: flex; align-items: center; justify-content: center; font-weight: bold; color: #0056b3; font-size: 14px; margin: 0 auto;">
              ILWD
            </div>
          </td>
          <td colspan="5" class="header" style="font-size: 14px; border: 1px solid #000;">ILIGAN CITY WATERWORKS SYSTEM</td>
        </tr>
        <tr>
          <td colspan="5" class="header" style="font-size: 12px; border: 1px solid #000;">WATER BILLING REPORT</td>
        </tr>
        <tr><td colspan="6">&nbsp;</td></tr>
        
        <!-- Report Information -->
        <tr>
          <td class="left">Report No:</td><td colspan="2">WR-<?= $year ?>-<?= sprintf('%04d', rand(1, 9999)) ?></td>
          <td class="left">Date:</td><td colspan="2"><?= date('F j, Y') ?></td>
        </tr>
        <tr>
          <td class="left">Period:</td><td colspan="2"><?= formatPeriodDisplay($month, $year, $start_year, $end_year) ?></td>
          <td class="left">Barangay:</td><td colspan="2"><?= $barangay_filter ?: 'All Barangays' ?></td>
        </tr>
        <tr>
          <td class="left">Status:</td><td colspan="2">All Records</td>
          <td class="left">Time:</td><td colspan="2"><?= date('h:i A') ?></td>
        </tr>
        <tr><td colspan="6">&nbsp;</td></tr>
        
        <!-- Summary Section -->
        <tr><td colspan="6" class="header">SUMMARY</td></tr>
        <tr>
          <td class="left">Total Connections:</td><td><?= number_format($total_bills) ?></td>
          <td class="left">Total Consumption:</td><td><?= number_format($total_consumption) ?> m³</td>
          <td class="left">Total Revenue:</td><td>₱<?= number_format($total_revenue, 2) ?></td>
        </tr>
        <tr>
          <td class="left">Average Consumption:</td><td><?= $total_bills > 0 ? number_format($total_consumption / $total_bills, 2) : '0.00' ?> m³</td>
          <td class="left">Average Bill:</td><td>₱<?= $total_bills > 0 ? number_format($total_revenue / $total_bills, 2) : '0.00' ?></td>
          <td class="left">Collection Rate:</td><td>100.00%</td>
        </tr>
        <tr><td colspan="6">&nbsp;</td></tr>
        
        <!-- Billing Details Header -->
        <tr><td colspan="6" class="header">BILLING DETAILS</td></tr>
        <tr class="header">
          <td class="col-a center">#</td>
          <td class="col-b">Consumer Name</td>
          <td class="col-c">Address/Barangay</td>
          <td class="col-d center">Period</td>
          <td class="col-e right">Consumption</td>
          <td class="col-f right">Amount</td>
        </tr>
    `;
    
    // Add data rows
    const rows = document.querySelectorAll('#billingTable tbody tr');
    let recordNumber = 1;
    
    rows.forEach(row => {
      if (row.style.display === 'none') return;
      
      const cols = row.querySelectorAll('td');
      
      if (cols.length >= 5) {
        htmlContent += `
        <tr>
          <td class="col-a center">${recordNumber}</td>
          <td class="col-b">${cols[1] ? cols[1].innerText.trim() : 'N/A'}</td>
          <td class="col-c">${cols[6] ? cols[6].innerText.trim() : 'N/A'}</td>
          <td class="col-d center">${cols[2] ? cols[2].innerText.trim() : 'N/A'}</td>
          <td class="col-e right">${cols[3] ? cols[3].innerText.trim() : '0'}</td>
          <td class="col-f right">₱${cols[4] ? cols[4].innerText.trim() : '0'}</td>
        </tr>`;
        recordNumber++;
      }
    });
    
    // Fill empty rows
    for (let i = recordNumber; i <= 15; i++) {
      htmlContent += `
        <tr>
          <td class="col-a center">${i}</td>
          <td class="col-b">&nbsp;</td>
          <td class="col-c">&nbsp;</td>
          <td class="col-d center">&nbsp;</td>
          <td class="col-e right">&nbsp;</td>
          <td class="col-f right">&nbsp;</td>
        </tr>`;
    }
    
    // Certification Section
    htmlContent += `
        <tr><td colspan="6">&nbsp;</td></tr>
        <tr><td colspan="6" class="header">REPORT CERTIFICATION</td></tr>
        <tr>
          <td colspan="6" style="text-align: center; padding: 10px;">
            This report certifies that the above billing information is accurate and complete as of the reporting date.
          </td>
        </tr>
        <tr><td colspan="6">&nbsp;</td></tr>
        <tr>
          <td colspan="2" class="center">
            <div style="margin-bottom: 30px;">_________________________</div>
            <div>Prepared By:</div>
            <div style="font-size: 10px;">Name & Signature</div>
            <div style="font-size: 10px;">Position</div>
          </td>
          <td colspan="2" class="center">
            <div style="margin-bottom: 30px;">_________________________</div>
            <div>Verified By:</div>
            <div style="font-size: 10px;">Name & Signature</div>
            <div style="font-size: 10px;">Position</div>
          </td>
          <td colspan="2" class="center">
            <div style="margin-bottom: 30px;">_________________________</div>
            <div>Approved By:</div>
            <div style="font-size: 10px;">Name & Signature</div>
            <div style="font-size: 10px;">Position</div>
          </td>
        </tr>
      </table>
    </body>
    </html>`;
    
    // Create and download Excel file
    const blob = new Blob([htmlContent], { 
      type: 'application/vnd.ms-excel;charset=utf-8;' 
    });
    const downloadLink = document.createElement('a');
    downloadLink.href = URL.createObjectURL(blob);
    downloadLink.download = 'ICWS_Water_Billing_Report_<?= $year . '_' . sprintf("%02d",$month) ?>.xls';
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
    URL.revokeObjectURL(downloadLink.href);
    
    console.log('Excel export completed');
    
  } catch (error) {
    console.error('Excel export error:', error);
    alert('Error exporting to Excel. Please try again.');
  }
});

</script>

<style>
/* Print Layout Styles */
.print-only {
  display: none;
  font-family: 'Times New Roman', serif;
  font-size: 11pt;
  color: #000;
}

/* Main Report Table - School Form 7 Style */
.main-report-table {
  width: 100%;
  border-collapse: collapse;
  border: 2px solid #000;
  font-family: 'Times New Roman', serif;
}

.main-report-table td {
  padding: 0;
  vertical-align: top;
  border: 2px solid #000;
}

/* Header Section */
.header-section {
  padding: 0;
}

.header-content-table {
  width: 100%;
  border-collapse: collapse;
  border: none;
}

.header-content-table td {
  border: 2px solid #000;
  padding: 8px;
  vertical-align: top;
}

.logo-cell {
  text-align: center;
  vertical-align: middle;
  padding: 10px !important;
}

.logo-img {
  max-width: 80px;
  max-height: 80px;
  border: 1px solid #000;
}

.title-cell {
  text-align: center;
  padding: 15px 8px !important;
}

.organization-name {
  font-size: 14pt;
  font-weight: bold;
  text-transform: uppercase;
  margin-bottom: 8px;
}

.form-title {
  font-size: 12pt;
  font-weight: bold;
  text-transform: uppercase;
}

.info-cell {
  padding: 5px !important;
}

.info-details-table {
  width: 100%;
  border-collapse: collapse;
  border: none;
}

.info-details-table td {
  border: 1px solid #000;
  padding: 4px 6px;
  font-size: 9pt;
  vertical-align: top;
}

/* Section Headers */
.section-header {
  background-color: #000;
  color: #fff;
  text-align: center;
  font-weight: bold;
  font-size: 11pt;
  text-transform: uppercase;
  padding: 8px !important;
}

/* Summary Information Table */
.summary-info-table {
  border-collapse: collapse;
  border: none;
}

.summary-info-table td {
  border: 1px solid #000;
  padding: 6px 8px;
  font-size: 10pt;
  vertical-align: top;
  background-color: #fff;
}

/* Billing Records Table */
.billing-records-table {
  border-collapse: collapse;
  border: none;
}

.billing-records-table th {
  background-color: #000;
  color: #fff;
  border: 1px solid #000;
  padding: 6px 4px;
  font-size: 9pt;
  font-weight: bold;
  text-align: center;
  text-transform: uppercase;
  vertical-align: middle;
}

.billing-records-table td {
  border: 1px solid #000;
  padding: 4px 3px;
  font-size: 9pt;
  text-align: center;
  vertical-align: middle;
  background-color: #fff;
}

.no-data {
  text-align: center !important;
  font-style: italic;
  background-color: #f9f9f9 !important;
}

/* Signature Section */
.signature-table {
  border-collapse: collapse;
  border: none;
}

.signature-table td {
  border: 1px solid #000;
  padding: 15px 8px;
  vertical-align: top;
  text-align: center;
  background-color: #fff;
}

.signature-box {
  min-height: 100px;
}

.signature-line {
  border-bottom: 1px solid #000;
  height: 40px;
  margin-bottom: 8px;
  background-color: #fff;
}

.signature-box p {
  margin: 4px 0;
  font-size: 9pt;
  line-height: 1.2;
}

/* Graphs Table */
.graphs-table {
  border-collapse: collapse;
  border: none;
}

.graphs-table td {
  border: 1px solid #000;
  padding: 10px;
  vertical-align: top;
  text-align: center;
  background-color: #fff;
}

.graphs-table canvas {
  max-width: 100%;
  height: auto;
}

/* Analytics dashboard (screen) */
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

/* Print Media Rules */
@media print {
  /* Hide screen elements */
  body * {
    visibility: hidden;
  }

  #officialReport,
  #officialReport * {
    visibility: visible;
  }

  #officialReport {
    position: absolute;
    left: 0;
    top: 0;
    width: 100%;
    display: block !important;
    font-family: 'Times New Roman', 'Liberation Serif', serif !important;
    color: #000 !important;
    background: #fff !important;
  }
  
  /* Page setup */
  @page {
    size: A4;
    margin: 0.7cm; /* Slightly larger margin for LibreOffice */
  }
  
  body {
    font-family: 'Times New Roman', 'Liberation Serif', serif !important;
    font-size: 11pt !important;
    line-height: 1.2 !important; /* Slightly increased for LibreOffice */
    color: #000 !important;
    background: #fff !important;
    margin: 0 !important;
    padding: 0 !important;
  }
  
  main {
    width: 100% !important;
    margin: 0 !important;
    padding: 0 !important;
  }
  
  /* Force table borders and styling - LibreOffice compatible */
  .main-report-table,
  .main-report-table td,
  .header-content-table,
  .header-content-table td,
  .info-details-table,
  .info-details-table td,
  .summary-info-table,
  .summary-info-table td,
  .billing-records-table,
  .billing-records-table th,
  .billing-records-table td,
  .signature-table,
  .signature-table td,
  .graphs-table,
  .graphs-table td {
    border: 1px solid #000 !important;
    border-collapse: collapse !important;
    -webkit-print-color-adjust: exact !important;
    color-adjust: exact !important;
    print-color-adjust: exact !important; /* LibreOffice support */
  }
  
  /* Force thicker borders for main structure */
  .main-report-table {
    border: 2px solid #000 !important;
  }
  
  /* Force header backgrounds */
  .section-header,
  .billing-records-table th {
    background-color: #000 !important;
    color: #fff !important;
    -webkit-print-color-adjust: exact !important;
    color-adjust: exact !important;
    print-color-adjust: exact !important; /* LibreOffice support */
  }
  
  /* Force white backgrounds for cells */
  .summary-info-table td,
  .billing-records-table td,
  .signature-table td,
  .info-details-table td,
  .graphs-table td {
    background-color: #fff !important;
    -webkit-print-color-adjust: exact !important;
    color-adjust: exact !important;
    print-color-adjust: exact !important; /* LibreOffice support */
  }
  
  /* Adjust font sizes for print - LibreOffice optimized */
  .organization-name {
    font-size: 14pt !important;
  }
  
  .form-title {
    font-size: 12pt !important;
  }
  
  .section-header {
    font-size: 11pt !important;
  }
  
  .billing-records-table th,
  .billing-records-table td {
    font-size: 9pt !important;
  }
  
  /* Ensure proper table display */
  .print-only table,
  .print-only tr,
  .print-only td,
  .print-only th {
    display: table-cell !important;
    visibility: visible !important;
  }
  
  .print-only tr {
    display: table-row !important;
  }
  
  .print-only table {
    display: table !important;
  }
  
  /* Prevent page breaks - LibreOffice friendly */
  .main-report-table {
    page-break-inside: avoid;
  }
  
  /* Additional LibreOffice optimizations */
  table {
    border-spacing: 0 !important;
    empty-cells: show !important;
  }
  
  td, th {
    empty-cells: show !important;
  }
}
</style>

<?php include '../includes/footer.php'; ?>
