<?php
require_once '../config/db.php';
require_once '../functions/admin_functions.php';

// Ensure admin is logged in
requireAdminLogin();

$page_title = 'Meters';

// ==========================
// DIRECT UPDATE HANDLER
// ==========================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    // UPDATE meter
    if ($_POST['action'] === 'update') {
        $meter_id = mysqli_real_escape_string($conn, $_POST['meter_id']);
        $classification = mysqli_real_escape_string($conn, $_POST['classification']);
        $serial_no = mysqli_real_escape_string($conn, $_POST['serial_no']);
        $consumer_id = !empty($_POST['consumer_id']) ? mysqli_real_escape_string($conn, $_POST['consumer_id']) : 'NULL';
        $area = mysqli_real_escape_string($conn, $_POST['area']);
        $installation_date = !empty($_POST['installation_date']) ? "'" . mysqli_real_escape_string($conn, $_POST['installation_date']) . "'" : 'CURRENT_TIMESTAMP';
        $status = isset($_POST['status']) && $_POST['status'] === 'active' ? 'active' : 'inactive';

        $update = "
            UPDATE meter 
            SET 
                Classification = '$classification',
                SerialNo = '$serial_no',
                ConsumerID = $consumer_id,
                Area = '$area',
                InstallationDate = $installation_date,
                Status = '$status'
            WHERE MeterID = '$meter_id'
        ";

        if (mysqli_query($conn, $update)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
        }
        exit;
    }

    // TOGGLE STATUS
    if ($_POST['action'] === 'toggle_status') {
        $meter_id = mysqli_real_escape_string($conn, $_POST['meter_id']);
        $status = ($_POST['status'] === 'active') ? 'inactive' : 'active';
        $updateStatus = "UPDATE meter SET Status = '$status' WHERE MeterID = '$meter_id'";

        if (mysqli_query($conn, $updateStatus)) {
            echo json_encode(['success' => true, 'new_status' => $status]);
        } else {
            echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
        }
        exit;
    }
}

// ==========================
// FETCH DATA
// ==========================
$meters = [];
$query = "SELECT m.*, c.FirstName, c.LastName, c.Email 
          FROM meter m 
          LEFT JOIN consumer c ON m.ConsumerID = c.ConsumerID
          ORDER BY m.Area, m.Classification";
$result = mysqli_query($conn, $query);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $meters[] = $row;
    }
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Meters</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="add_meter.php" class="btn btn-sm btn-primary">
                <i class="fas fa-plus"></i> Add New Meter
            </a>
        </div>
    </div>

    <!-- Search Bar -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <label for="searchInput" class="form-label">Search Meters</label>
                    <input type="text" class="form-control" id="searchInput" placeholder="Search by classification, serial no, area, consumer name, or email...">
                </div>
                <div class="col-md-4">
                    <label for="statusFilter" class="form-label">Filter by Status</label>
                    <select class="form-select" id="statusFilter">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>
            <div class="mt-2">
                <small class="text-muted">Found: <span id="resultCount"><?= count($meters) ?></span> meter(s)</small>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-striped table-hover" id="metersTable">
            <thead>
                <tr>
                    <th>Classification</th>
                    <th>Serial No</th>
                    <th>Area</th>
                    <th>Consumer</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Installation Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($meters)): ?>
                    <?php foreach ($meters as $meter): ?>
                        <tr data-classification="<?= strtolower(htmlspecialchars($meter['Classification'] ?? '')) ?>"
                            data-serial="<?= strtolower(htmlspecialchars($meter['SerialNo'] ?? '')) ?>"
                            data-area="<?= strtolower(htmlspecialchars($meter['Area'] ?? '')) ?>"
                            data-consumer="<?= strtolower(htmlspecialchars(($meter['FirstName'] ?? '') . ' ' . ($meter['LastName'] ?? ''))) ?>"
                            data-email="<?= strtolower(htmlspecialchars($meter['Email'] ?? '')) ?>"
                            data-status="<?= strtolower($meter['Status'] ?? '') ?>">
                            <td><?= htmlspecialchars($meter['Classification'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($meter['SerialNo'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($meter['Area'] ?? '-') ?></td>
                            <td>
                                <?php if (!empty($meter['ConsumerID'])): ?>
                                    <?= htmlspecialchars(($meter['FirstName'] ?? '') . ' ' . ($meter['LastName'] ?? '')) ?>
                                <?php else: ?>
                                    <span class="text-muted">Unassigned</span>
                                <?php endif; ?>
                            </td>
                            <td><?= !empty($meter['Email']) ? htmlspecialchars($meter['Email']) : '<span class="text-muted">-</span>' ?></td>
                            <td>
                                <span class="badge bg-<?= ($meter['Status'] ?? '') === 'active' ? 'success' : 'secondary'; ?>">
                                    <?= ucfirst($meter['Status'] ?? 'Inactive'); ?>
                                </span>
                            </td>
                            <td><?= !empty($meter['InstallationDate']) ? date('M d, Y', strtotime($meter['InstallationDate'])) : '-' ?></td>
                            <td>
                                <button class="btn btn-sm btn-primary edit-meter"
                                        data-id="<?= $meter['MeterID'] ?>"
                                        data-classification="<?= htmlspecialchars($meter['Classification'] ?? '') ?>"
                                        data-serial="<?= htmlspecialchars($meter['SerialNo'] ?? '') ?>"
                                        data-consumer="<?= $meter['ConsumerID'] ?? '' ?>"
                                        data-area="<?= htmlspecialchars($meter['Area'] ?? '') ?>"
                                        data-installation-date="<?= $meter['InstallationDate'] ?? '' ?>"
                                        data-status="<?= $meter['Status'] ?? '' ?>"
                                        title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-<?= ($meter['Status'] ?? '') === 'active' ? 'warning' : 'success'; ?> toggle-status"
                                        data-id="<?= $meter['MeterID'] ?>"
                                        data-status="<?= $meter['Status'] ?? '' ?>"
                                        title="<?= ($meter['Status'] ?? '') === 'active' ? 'Deactivate' : 'Activate'; ?>">
                                    <i class="fas fa-<?= ($meter['Status'] ?? '') === 'active' ? 'times' : 'check'; ?>"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr id="noResultsRow"><td colspan="8" class="text-center">No meters found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

<!-- Edit Meter Modal -->
<div class="modal fade" id="editMeterModal" tabindex="-1" aria-labelledby="editMeterModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="editMeterForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="editMeterModalLabel">Edit Meter</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="editMeterAlert" class="alert d-none"></div>
                    <input type="hidden" id="edit_meter_id" name="meter_id">

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="edit_classification" class="form-label">Classification *</label>
                            <select class="form-select" id="edit_classification" name="classification" required>
                                <option value="">-- Select Classification --</option>
                                <option value="Residential">Residential</option>
                                <option value="Commercial">Commercial</option>
                                <option value="Industrial">Industrial</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_serial_no" class="form-label">Serial No *</label>
                            <input type="text" class="form-control" id="edit_serial_no" name="serial_no" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="edit_consumer_id" class="form-label">Consumer</label>
                            <select class="form-select" id="edit_consumer_id" name="consumer_id">
                                <option value="">-- Select Consumer --</option>
                                <?php 
                                $consumerQuery = "SELECT ConsumerID, FirstName, LastName FROM consumer WHERE Status = 'active' ORDER BY LastName, FirstName";
                                $consumerResult = $conn->query($consumerQuery);
                                if ($consumerResult) {
                                    while ($row = $consumerResult->fetch_assoc()) {
                                        echo '<option value="' . $row['ConsumerID'] . '">' . 
                                             htmlspecialchars($row['FirstName'] . ' ' . $row['LastName']) . '</option>';
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_area" class="form-label">Area</label>
                            <input type="text" class="form-control" id="edit_area" name="area">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="edit_installation_date" class="form-label">Installation Date</label>
                        <input type="date" class="form-control" id="edit_installation_date" name="installation_date">
                    </div>

                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="edit_status" name="status" value="active">
                        <label class="form-check-label" for="edit_status">Active</label>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Search and Filter Function
    function filterTable() {
        const searchTerm = $('#searchInput').val().toLowerCase();
        const statusFilter = $('#statusFilter').val().toLowerCase();
        let visibleCount = 0;

        $('#metersTable tbody tr').each(function() {
            if ($(this).attr('id') === 'noResultsRow') return;

            const classification = $(this).data('classification') || '';
            const serial = $(this).data('serial') || '';
            const area = $(this).data('area') || '';
            const consumer = $(this).data('consumer') || '';
            const email = $(this).data('email') || '';
            const status = $(this).data('status') || '';

            // Check if search term matches any field
            const matchesSearch = searchTerm === '' || 
                classification.includes(searchTerm) ||
                serial.includes(searchTerm) ||
                area.includes(searchTerm) ||
                consumer.includes(searchTerm) ||
                email.includes(searchTerm);

            // Check if status matches filter
            const matchesStatus = statusFilter === '' || status === statusFilter;

            if (matchesSearch && matchesStatus) {
                $(this).show();
                visibleCount++;
            } else {
                $(this).hide();
            }
        });

        $('#resultCount').text(visibleCount);

        // Show "no results" message if needed
        if (visibleCount === 0 && $('#noResultsRow').length === 0) {
            $('#metersTable tbody').append('<tr id="noResultsRow"><td colspan="8" class="text-center text-muted">No meters match your search.</td></tr>');
        } else if (visibleCount > 0) {
            $('#noResultsRow').remove();
        }
    }

    // Bind search and filter events
    $('#searchInput').on('keyup', filterTable);
    $('#statusFilter').on('change', filterTable);

    // Open Edit Modal
    $(document).on('click', '.edit-meter', function() {
        $('#edit_meter_id').val($(this).data('id'));
        $('#edit_classification').val($(this).data('classification'));
        $('#edit_serial_no').val($(this).data('serial'));
        $('#edit_consumer_id').val($(this).data('consumer'));
        $('#edit_area').val($(this).data('area'));
        $('#edit_installation_date').val($(this).data('installation-date'));
        $('#edit_status').prop('checked', $(this).data('status') === 'active');
        new bootstrap.Modal('#editMeterModal').show();
    });

    // Save Meter
    $('#editMeterForm').on('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'update');

        const alert = $('#editMeterAlert');
        alert.addClass('d-none').removeClass('alert-success alert-danger').text('');

        $.ajax({
            url: window.location.href,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert.addClass('alert-success').text('Meter updated successfully.').removeClass('d-none');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    alert.addClass('alert-danger').text(response.message || 'Error updating meter.').removeClass('d-none');
                }
            },
            error: () => {
                alert.addClass('alert-danger').text('An error occurred while updating the meter.').removeClass('d-none');
            }
        });
    });

    // Toggle Status
    $(document).on('click', '.toggle-status', function() {
        const meterId = $(this).data('id');
        const currentStatus = $(this).data('status');
        $.ajax({
            url: window.location.href,
            type: 'POST',
            data: { action: 'toggle_status', meter_id: meterId, status: currentStatus },
            dataType: 'json',
            success: function(response) {
                if (response.success) location.reload();
                else alert(response.message || 'Error toggling status.');
            },
            error: () => alert('An error occurred while toggling status.')
        });
    });
});
</script>

<?php include '../includes/footer.php'; ?>