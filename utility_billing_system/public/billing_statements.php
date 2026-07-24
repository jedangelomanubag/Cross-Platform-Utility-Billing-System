<?php
require_once '../config/db.php';
require_once '../functions/admin_functions.php';
requireAdminLogin();

$page_title = 'Billing Statements';

// Tiered calculation matching React Native APK
function calculateBill($classification, $consumption) {
    $classification = strtoupper($classification ?? 'RES');

    $rateTables = [
        'RES' => [
            ['max' => 10, 'rate' => 20],
            ['max' => 20, 'rate' => 2.3],
            ['max' => 30, 'rate' => 2.8],
            ['max' => 40, 'rate' => 3.4],
            ['max' => 50, 'rate' => 4.1],
            ['max' => INF, 'rate' => 5.0],
        ],
        'COM' => [
            ['max' => 10, 'rate' => 40],
            ['max' => 20, 'rate' => 4.6],
            ['max' => 30, 'rate' => 5.6],
            ['max' => 40, 'rate' => 6.8],
            ['max' => 50, 'rate' => 8.2],
            ['max' => INF, 'rate' => 10.0],
        ],
        'IND' => [
            ['max' => 10, 'rate' => 192],
            ['max' => 20, 'rate' => 6.9],
            ['max' => 30, 'rate' => 8.4],
            ['max' => 40, 'rate' => 10.2],
            ['max' => 50, 'rate' => 12.3],
            ['max' => INF, 'rate' => 15.0],
        ],
    ];

    $rates = $rateTables[$classification] ?? $rateTables['RES'];
    $remaining = $consumption;
    $amount = 0;

    foreach ($rates as $i => $tier) {
        $prevMax = $i === 0 ? 0 : $rates[$i - 1]['max'];
        $tierConsumption = min($remaining, $tier['max'] - $prevMax);

        // React Native first-tier adjustment
        if ($i === 0 && $consumption <= 10) $tierConsumption = $consumption;

        $amount += $tierConsumption * $tier['rate'];
        $remaining -= $tierConsumption;
        if ($remaining <= 0) break;
    }

    return round($amount, 2);
}

// Filters
$status = $_GET['status'] ?? 'all';
$month = $_GET['month'] ?? '';
$search = trim($_GET['search'] ?? '');

// Build query with LEFT JOINs to prevent missing rows
$query = "SELECT 
            bs.*,
            c.FirstName, c.LastName, c.Email, c.ContactNumber, c.Address,
            m.SerialNo AS MeterNumber, m.MeterID,
            CONCAT(c.FirstName, ' ', c.LastName) AS ConsumerName,
            m.Classification
          FROM billingstatement bs
          LEFT JOIN meter m ON bs.MeterID = m.MeterID
          LEFT JOIN consumer c ON m.ConsumerID = c.ConsumerID
          WHERE 1=1";

$params = [];

// Apply filters
if ($status !== 'all') {
    $query .= " AND LOWER(bs.PaymentStatus) = LOWER(?)";
    $params[] = $status;
}
if (!empty($month)) {
    $query .= " AND DATE_FORMAT(bs.BillingDate, '%Y-%m') = ?";
    $params[] = $month;
}
if (!empty($search)) {
    $query .= " AND (c.FirstName LIKE ? OR c.LastName LIKE ? OR c.Email LIKE ? OR c.Address LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
}

$query .= " ORDER BY bs.BillingDate DESC, bs.BillingID DESC";

// Prepare statement
$stmt = mysqli_prepare($conn, $query);
if (!$stmt) die("Query failed: " . mysqli_error($conn));

// Bind params if any
if (!empty($params)) {
    $types = str_repeat('s', count($params));
    $refs = [];
    foreach ($params as $key => $value) $refs[$key] = &$params[$key];
    mysqli_stmt_bind_param($stmt, $types, ...$refs);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$statements = [];
while ($row = mysqli_fetch_assoc($result)) {
    // Safely get previous reading
    $prevReading = 0;
    if (!empty($row['MeterID']) && !empty($row['BillingID'])) {
        $prevStmt = mysqli_prepare($conn, "
            SELECT CurrentReading 
            FROM billingstatement
            WHERE MeterID = ? AND BillingID < ?
            ORDER BY BillingDate DESC, BillingID DESC
            LIMIT 1
        ");
        if ($prevStmt) {
            mysqli_stmt_bind_param($prevStmt, "ii", $row['MeterID'], $row['BillingID']);
            mysqli_stmt_execute($prevStmt);
            $prevResult = mysqli_stmt_get_result($prevStmt);
            if ($prevRow = mysqli_fetch_assoc($prevResult)) {
                $prevReading = $prevRow['CurrentReading'];
            }
            mysqli_stmt_close($prevStmt);
        }
    }

    $row['PreviousReading'] = $prevReading;
    $row['Consumption'] = max(0, $row['CurrentReading'] - $row['PreviousReading']);
    $row['TotalAmount'] = calculateBill($row['Classification'] ?? 'RES', $row['Consumption']);

    $statements[] = $row;
}

// Summary
$summary = [
    'total' => count($statements),
    'paid' => 0,
    'unpaid' => 0,
    'overdue' => 0,
    'total_amount' => 0,
    'paid_amount' => 0,
    'unpaid_amount' => 0,
    'overdue_amount' => 0
];

foreach ($statements as $b) {
    $summary['total_amount'] += $b['TotalAmount'];
    switch (strtolower($b['PaymentStatus'])) {
        case 'paid':
            $summary['paid']++;
            $summary['paid_amount'] += $b['TotalAmount'];
            break;
        case 'unpaid':
            if (strtotime($b['DueDate']) < time()) {
                $summary['overdue']++;
                $summary['overdue_amount'] += $b['TotalAmount'];
            } else {
                $summary['unpaid']++;
                $summary['unpaid_amount'] += $b['TotalAmount'];
            }
            break;
    }
}

// Continue including your header, sidebar, HTML table, and JS as before
include '../includes/header.php';
include '../includes/sidebar.php';
?>


<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">

    <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Billing Statements</h1>
    </div>

    <!-- Summary Cards -->
    <div class="d-flex flex-wrap gap-3 mb-4">
        <?php
        $cards = [
            ['title' => 'Total Bills', 'count' => $summary['total'], 'amount' => $summary['total_amount'], 'color' => 'primary', 'icon' => 'fa-file-invoice'],
            ['title' => 'Paid', 'count' => $summary['paid'], 'amount' => $summary['paid_amount'], 'color' => 'success', 'icon' => 'fa-check-circle'],
            ['title' => 'Unpaid', 'count' => $summary['unpaid'], 'amount' => $summary['unpaid_amount'], 'color' => 'warning', 'icon' => 'fa-clock'],
            ['title' => 'Overdue', 'count' => $summary['overdue'], 'amount' => $summary['overdue_amount'], 'color' => 'danger', 'icon' => 'fa-exclamation-triangle']
        ];
        foreach ($cards as $card): ?>
            <div class="card border-start-<?php echo $card['color']; ?> border-3 flex-fill shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-xs fw-bold text-<?php echo $card['color']; ?> text-uppercase mb-1">
                                <?php echo $card['title']; ?>
                            </div>
                            <div class="h5 mb-0 fw-bold">
                                <?php echo number_format($card['count']); ?>
                                <small class="text-muted">(₱<?php echo number_format($card['amount'], 2); ?>)</small>
                            </div>
                        </div>
                        <div class="icon-circle bg-<?php echo $card['color']; ?> bg-opacity-10 text-<?php echo $card['color']; ?> p-2 rounded-circle">
                            <i class="fas <?php echo $card['icon']; ?>"></i>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <option value="all" <?= $status === 'all' ? 'selected' : ''; ?>>All</option>
                        <option value="paid" <?= $status === 'paid' ? 'selected' : ''; ?>>Paid</option>
                        <option value="unpaid" <?= $status === 'unpaid' ? 'selected' : ''; ?>>Unpaid</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Billing Month</label>
                    <input type="month" class="form-control" name="month" value="<?= htmlspecialchars($month); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Search</label>
                    <input type="text" class="form-control" name="search" placeholder="Search name, email, or address" value="<?= htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn btn-primary me-2" type="submit">Filter</button>
                    <a href="billing_statements.php" class="btn btn-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Table -->
    <?php if (!empty($statements)): ?>
        <div class="card shadow-sm">
            <div class="card-body table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-primary text-center">
                        <tr>
                            <th>#</th>
                            <th>Consumer Name</th>
                            <th>Serial No</th>
                            <th>Classification</th>
                            <th>Billing Period</th>
                            <th>Consumption</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Billing Date</th>
                            <th>Due Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($statements as $i => $b):
                            $rawStatus = strtolower($b['PaymentStatus'] ?? '');
                            $isOverdue = ($rawStatus === 'overdue') || ($rawStatus === 'unpaid' && strtotime($b['DueDate']) < time());
                            $statusKey = $isOverdue ? 'overdue' : ($rawStatus ?: 'unpaid');
                            $statusColorMap = [
                                'paid' => 'success',
                                'unpaid' => 'warning',
                                'overdue' => 'danger'
                            ];
                            $statusClass = $statusColorMap[$statusKey] ?? 'secondary';
                            $daysLeft = floor((strtotime($b['DueDate']) - time()) / (60 * 60 * 24));
                            $showWarningDot = $daysLeft >= 0 && $daysLeft <= 3;
                        ?>
                        <tr class="text-center">
                            <td><?= $i + 1; ?></td>
                            <td><?= htmlspecialchars($b['ConsumerName']); ?></td>
                            <td><?= htmlspecialchars($b['MeterNumber']); ?></td>
                             <td><?= htmlspecialchars($b['Classification']); ?></td>
                            <td><?= htmlspecialchars($b['BillingPeriod']); ?></td>
                            <td><?= number_format($b['Consumption'], 2); ?></td>
                            <td class="fw-bold">₱<?= number_format($b['TotalAmount'], 2); ?></td>
                            <td><span class="badge bg-<?= $statusClass; ?>"><?= ucfirst($statusKey); ?></span></td>
                            <td><?= date('M d, Y', strtotime($b['BillingDate'])); ?></td>
                            <td class="<?= $isOverdue ? 'text-danger fw-bold' : ''; ?>">
                                <?= date('M d, Y', strtotime($b['DueDate'])); ?>
                                <?php if ($showWarningDot): ?>
                                    <span class="ms-1 text-danger fw-bold" title="Due soon">●</span>
                                <?php endif; ?>
                            </td>
                           <td>
    <div class="d-flex gap-1 align-items-center">
        <button class="btn btn-sm btn-info send-sms" data-id="<?= $b['BillingID']; ?>">
            <i class="fas fa-sms"></i>
        </button>
        <button class="btn btn-sm btn-outline-primary view-bill" data-id="<?= $b['BillingID']; ?>">
            <i class="fas fa-eye"></i>
        </button>
        <button class="btn btn-sm btn-outline-secondary print-bill" data-id="<?= $b['BillingID']; ?>">
            <i class="fas fa-print"></i>
        </button>
        <div class="btn-group">
            <button class="btn btn-sm btn-success dropdown-toggle" data-bs-toggle="dropdown">
                <i class="fas fa-money-bill-wave"></i>
            </button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item update-status" href="#" data-id="<?= $b['BillingID']; ?>" data-status="paid">Mark as Paid</a></li>
                <li><a class="dropdown-item update-status" href="#" data-id="<?= $b['BillingID']; ?>" data-status="unpaid">Mark as Unpaid</a></li>
                <li><a class="dropdown-item update-status" href="#" data-id="<?= $b['BillingID']; ?>" data-status="overdue">Mark as Overdue</a></li>
            </ul>
        </div>
    </div>
</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>No billing statements found.</div>
    <?php endif; ?>
</main>

<!-- Modal -->
<div class="modal fade" id="viewBillingModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title">Water Billing Statement</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="billingDetails">
        <p class="text-center">Loading...</p>
      </div>
    </div>
  </div>
</div>

<script>
// JS for View / Print / Update Status
document.querySelectorAll('.view-bill').forEach(btn => {
    btn.addEventListener('click', function() {
        const billingID = this.dataset.id;
        fetch(`view_statement.php?id=${billingID}`)
            .then(res => res.text())
            .then(html => {
                document.getElementById('billingDetails').innerHTML = html;
                new bootstrap.Modal(document.getElementById('viewBillingModal')).show();
            })
            .catch(() => {
                document.getElementById('billingDetails').innerHTML = '<div class="alert alert-danger">Error loading billing details.</div>';
            });
    });
});

document.querySelectorAll('.print-bill').forEach(btn => {
    btn.addEventListener('click', function() {
        const billingID = this.dataset.id;
        fetch(`view_statement.php?id=${billingID}`)
            .then(res => res.text())
            .then(html => {
                const modalEl = document.getElementById('viewBillingModal');
                const modalBody = document.getElementById('billingDetails');
                const modal = new bootstrap.Modal(modalEl);
                modalBody.innerHTML = html;
                modal.show();
                setTimeout(() => {
                    let hasClosed = false;
                    const closeModal = () => {
                        if (!hasClosed) {
                            modal.hide();
                            hasClosed = true;
                            window.removeEventListener('focus', closeModal);
                            window.removeEventListener('afterprint', closeModal);
                        }
                    };
                    window.addEventListener('focus', closeModal, { once: true });
                    window.addEventListener('afterprint', closeModal, { once: true });
                    window.print();
                }, 600);
            })
            .catch(() => modalBody.innerHTML = '<div class="alert alert-danger">Error loading billing details.</div>');
    });
});

document.querySelectorAll('.update-status').forEach(link => {
    link.addEventListener('click', function (e) {
        e.preventDefault();
        const billingID = this.dataset.id;
        const newStatus = this.dataset.status;
        if (confirm(`Are you sure you want to mark this bill as ${newStatus.toUpperCase()}?`)) {
            fetch('update_status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `id=${billingID}&status=${newStatus}`
            })
            .then(res => res.text())
            .then(response => {
                alert(response);
                location.reload();
            })
            .catch(() => alert('Error updating status.'));
        }
    });
});

</script>

<script>
$(document).on('click', '.send-sms', function() {
    var billingId = $(this).data('id');

    $.ajax({
        url: 'sendSMSfromWeb.php', // adjust path if needed
        type: 'POST',
        data: { billing_id: billingId },
        dataType: 'json',
        success: function(response) {
            if(response.success){
                alert('✅ SMS sent successfully to ' + response.recipient);
            } else {
                alert('❌ Failed to send SMS: ' + response.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX error:', status, error);
            alert('❌ Error sending SMS. Check console for details.');
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>
