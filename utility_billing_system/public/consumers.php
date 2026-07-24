<?php
require_once '../config/db.php';
require_once '../functions/admin_functions.php';

// Ensure admin is logged in
requireAdminLogin();

// Set page title
$page_title = 'Manage Consumers';

// Helper: build image src from DB value
function buildImageSrc($imgValue) {
    // imgValue might be NULL, filename only, or contain 'uploads/previous_bills/...'
    if (empty($imgValue)) return '';
    // Normalize
    $img = $imgValue;
    // if it already contains uploads path, ensure relative prefix
    if (strpos($img, 'uploads/previous_bills') !== false) {
        // ensure path starts with ../ so it loads from admin folder
        if (strpos($img, '../') === 0) return $img;
        return '../' . ltrim($img, '/');
    }
    // otherwise treat as filename and prefix folder
    return '../uploads/previous_bills/' . ltrim($img, '/');
}

// Handle actions (approve, deny, add, edit, deactivate)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // If AJAX request, prevent any output before JSON
    if (isset($_POST['ajax']) && $_POST['ajax'] == 1) {
        ob_start(); // Start output buffering
    }
    
    $response = ['success' => false, 'message' => 'Unknown error'];
    switch ($_POST['action']) {

        case 'approve_consumer':
            $consumerId = (int)$_POST['consumer_id'];
            $query = "UPDATE consumer SET Status = 'active' WHERE ConsumerID = $consumerId";
            if (mysqli_query($conn, $query)) {
                logAdminAction($_SESSION['admin_id'], "Approved consumer (ID: $consumerId)");
                $response = ['success' => true, 'message' => 'Consumer approved successfully.'];
            } else {
                $response['message'] = "Error approving consumer: " . mysqli_error($conn);
            }
            break;

        case 'deny_consumer':
            $consumerId = (int)$_POST['consumer_id'];
            $query = "DELETE FROM consumer WHERE ConsumerID = $consumerId AND Status = 'pending'";
            if (mysqli_query($conn, $query)) {
                logAdminAction($_SESSION['admin_id'], "Denied and removed consumer (ID: $consumerId)");
                $response = ['success' => true, 'message' => 'Consumer denied and removed successfully.'];
            } else {
                $response['message'] = "Error denying consumer: " . mysqli_error($conn);
            }
            break;

        case 'add_consumer':
            // Note: admin add form likely elsewhere. Keep existing behavior.
            $firstName = sanitizeInput($_POST['first_name']);
            $lastName = sanitizeInput($_POST['last_name']);
            $email = sanitizeInput($_POST['email']);
            $contactNumber = sanitizeInput($_POST['contact_number']);
            $address = sanitizeInput($_POST['address']);
            $billingPreference = sanitizeInput($_POST['billing_preference'] ?? 'paper');
            $status = 'active';
            $accountNo = generateAccountNumber($conn);

            $password = bin2hex(random_bytes(8));
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $query = "INSERT INTO consumer (AccountNo, FirstName, LastName, Email, ContactNumber, Address, Password, Status, BillingPreference)
                      VALUES ('$accountNo', '$firstName', '$lastName', '$email', '$contactNumber', '$address', '$hashedPassword', '$status', '$billingPreference')";
            if (mysqli_query($conn, $query)) {
                $consumerId = mysqli_insert_id($conn);
                logAdminAction($_SESSION['admin_id'], "Added consumer: $firstName $lastName (ID: $consumerId)");
                $response = ['success' => true, 'message' => "Consumer added successfully. Temporary password: $password"];
            } else {
                $response['message'] = "Error adding consumer: " . mysqli_error($conn);
            }
            break;

        case 'update_consumer':
            // Sanitize inputs
            $consumerId = (int)$_POST['consumer_id'];
            $firstName = mysqli_real_escape_string($conn, sanitizeInput($_POST['first_name']));
            $lastName = mysqli_real_escape_string($conn, sanitizeInput($_POST['last_name']));
            $email = mysqli_real_escape_string($conn, sanitizeInput($_POST['email']));
            $contactNumber = mysqli_real_escape_string($conn, sanitizeInput($_POST['contact_number']));
            $address = mysqli_real_escape_string($conn, sanitizeInput($_POST['address']));
            $status = mysqli_real_escape_string($conn, sanitizeInput($_POST['status']));
            $billingPreference = mysqli_real_escape_string($conn, sanitizeInput($_POST['billing_preference'] ?? 'paper'));

            // Build UPDATE query (no image replacement)
            $query = "UPDATE consumer SET 
                FirstName = '$firstName',
                LastName = '$lastName',
                Email = '$email',
                ContactNumber = '$contactNumber',
                Address = '$address',
                Status = '$status',
                BillingPreference = '$billingPreference'
                WHERE ConsumerID = $consumerId";

            if (mysqli_query($conn, $query)) {
                logAdminAction($_SESSION['admin_id'], "Updated consumer: $firstName $lastName (ID: $consumerId)");
                $response = ['success' => true, 'message' => 'Consumer updated successfully.'];
            } else {
                $response['message'] = "Error updating consumer: " . mysqli_error($conn);
            }
            break;

        case 'delete_consumer':
            $consumerId = (int)$_POST['consumer_id'];
            $query = "UPDATE consumer SET Status = 'inactive' WHERE ConsumerID = $consumerId";
            if (mysqli_query($conn, $query)) {
                logAdminAction($_SESSION['admin_id'], "Deactivated consumer (ID: $consumerId)");
                $response = ['success' => true, 'message' => 'Consumer deactivated successfully.'];
            } else {
                $response['message'] = "Error deactivating consumer: " . mysqli_error($conn);
            }
            break;
    }

    // If AJAX, return JSON
    if (isset($_POST['ajax']) && $_POST['ajax'] == 1) {
        ob_end_clean(); // Clear any buffered output
        header('Content-Type: application/json');
        echo json_encode($response);
        exit();
    } else {
        $_SESSION[$response['success'] ? 'success' : 'error'] = $response['message'];
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Function to auto-generate Account Number
function generateAccountNumber($conn) {
    $prefix = "ICWS-";
    $last = mysqli_fetch_assoc(mysqli_query($conn, "SELECT MAX(ConsumerID) AS last_id FROM consumer"));
    $next = str_pad(($last['last_id'] ?? 0) + 1, 5, '0', STR_PAD_LEFT);
    return $prefix . $next;
}

// Search filters
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$status = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';

// Fetch pending consumers
$pendingQuery = "SELECT * FROM consumer WHERE Status = 'pending'";
if (!empty($search)) {
    $pendingQuery .= " AND (FirstName LIKE '%$search%' OR LastName LIKE '%$search%' OR Email LIKE '%$search%' OR ContactNumber LIKE '%$search%' OR AccountNo LIKE '%$search%')";
}
$pendingResult = mysqli_query($conn, $pendingQuery);
$pendingConsumers = mysqli_fetch_all($pendingResult, MYSQLI_ASSOC);

// Fetch existing consumers
$query = "SELECT * FROM consumer WHERE Status != 'pending'";
if (!empty($search)) {
    $query .= " AND (FirstName LIKE '%$search%' OR LastName LIKE '%$search%' OR Email LIKE '%$search%' OR ContactNumber LIKE '%$search%' OR AccountNo LIKE '%$search%')";
}
if (!empty($status)) {
    $query .= " AND Status = '$status'";
}
$query .= " ORDER BY LastName, FirstName";

$result = mysqli_query($conn, $query);
$consumers = mysqli_fetch_all($result, MYSQLI_ASSOC);
?>

<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar.php'; ?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
    <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Manage Consumers</h1>
        <a href="add_consumer.php" class="btn btn-sm btn-primary"><i class="fas fa-plus"></i> Add New Consumer</a>
    </div>

    <!-- Alerts -->
    <div id="alertContainer">
        <?php
        if (!empty($_SESSION['success'])) {
            echo '<div class="alert alert-success">'.htmlspecialchars($_SESSION['success']).'</div>';
            unset($_SESSION['success']);
        }
        if (!empty($_SESSION['error'])) {
            echo '<div class="alert alert-danger">'.htmlspecialchars($_SESSION['error']).'</div>';
            unset($_SESSION['error']);
        }
        ?>
    </div>

    <!-- Search and Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-6">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" class="form-control" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by name, email, or Account No...">
                </div>
                <div class="col-md-4">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">All Status</option>
                        <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Pending Consumers Table -->
    <div class="card mb-5 border-warning">
        <div class="card-header bg-warning text-dark fw-bold">Pending Consumer Registrations</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-warning">
                        <tr>
                            <th>ID</th>
                            <th>Account No</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Contact</th>
                            <th>Address</th>
                            <th>Prev. Bill</th>
                            <th>Registration Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($pendingConsumers)): ?>
                            <tr><td colspan="9" class="text-center text-muted">No pending consumers</td></tr>
                        <?php else: ?>
                            <?php foreach ($pendingConsumers as $p): 
                                $imgSrc = buildImageSrc($p['PreviousBillImage']);
                                $thumbHtml = $imgSrc ? '<a href="#" class="open-image" data-img="'.htmlspecialchars($imgSrc).'"><img src="'.htmlspecialchars($imgSrc).'" alt="bill" style="max-height:60px; max-width:60px;"></a>' : '<span class="text-muted">No image</span>';
                            ?>
                                <tr>
                                    <td><?php echo $p['ConsumerID']; ?></td>
                                    <td><?php echo htmlspecialchars($p['AccountNo']); ?></td>
                                    <td><?php echo htmlspecialchars($p['FirstName'] . ' ' . $p['LastName']); ?></td>
                                    <td><?php echo htmlspecialchars($p['Email']); ?></td>
                                    <td><?php echo htmlspecialchars($p['ContactNumber']); ?></td>
                                    <td><?php echo htmlspecialchars($p['Address']); ?></td>
                                    <td class="align-middle"><?php echo $thumbHtml; ?></td>
                                    <td><?php echo htmlspecialchars($p['RegistrationDate']); ?></td>
                                    <td>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="action" value="approve_consumer">
                                            <input type="hidden" name="consumer_id" value="<?php echo $p['ConsumerID']; ?>">
                                            <button class="btn btn-sm btn-success">Approve</button>
                                        </form>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="action" value="deny_consumer">
                                            <input type="hidden" name="consumer_id" value="<?php echo $p['ConsumerID']; ?>">
                                            <button class="btn btn-sm btn-danger">Deny</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Existing Consumers Table -->
    <div class="card">
        <div class="card-header bg-primary text-white fw-bold">Existing Consumers</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="table-primary">
                        <tr>
                            <th>ID</th>
                            <th>Account No</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Contact</th>
                            <th>Address</th>
                            <th>Prev. Bill</th>
                            <th>Billing</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($consumers)): ?>
                            <tr><td colspan="10" class="text-center text-muted">No consumers found</td></tr>
                        <?php else: ?>
                            <?php foreach ($consumers as $c): 
                                $imgSrc = buildImageSrc($c['PreviousBillImage']);
                                $thumbHtml = $imgSrc ? '<a href="#" class="open-image" data-img="'.htmlspecialchars($imgSrc).'"><img src="'.htmlspecialchars($imgSrc).'" alt="bill" style="max-height:60px; max-width:60px;"></a>' : '<span class="text-muted">No image</span>';
                            ?>
                                <tr id="consumerRow-<?php echo $c['ConsumerID']; ?>">
                                    <td><?php echo $c['ConsumerID']; ?></td>
                                    <td><?php echo htmlspecialchars($c['AccountNo']); ?></td>
                                    <td><?php echo htmlspecialchars($c['FirstName'] . ' ' . $c['LastName']); ?></td>
                                    <td><?php echo htmlspecialchars($c['Email']); ?></td>
                                    <td><?php echo htmlspecialchars($c['ContactNumber']); ?></td>
                                    <td><?php echo htmlspecialchars($c['Address']); ?></td>
                                    <td class="align-middle"><?php echo $thumbHtml; ?></td>
                                    <td><span class="badge bg-<?php echo $c['BillingPreference'] === 'mobile_app' ? 'info' : 'secondary'; ?>"><?php echo ucfirst(str_replace('_',' ', $c['BillingPreference'])); ?></span></td>
                                    <td><span class="badge bg-<?php echo $c['Status'] === 'active' ? 'success' : 'danger'; ?>"><?php echo ucfirst($c['Status']); ?></span></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary edit-consumer" 
                                            data-id="<?php echo $c['ConsumerID']; ?>"
                                            data-firstname="<?php echo htmlspecialchars($c['FirstName']); ?>"
                                            data-lastname="<?php echo htmlspecialchars($c['LastName']); ?>"
                                            data-email="<?php echo htmlspecialchars($c['Email']); ?>"
                                            data-contact="<?php echo htmlspecialchars($c['ContactNumber']); ?>"
                                            data-address="<?php echo htmlspecialchars($c['Address']); ?>"
                                            data-status="<?php echo $c['Status']; ?>"
                                            data-billing-preference="<?php echo $c['BillingPreference']; ?>"
                                            data-billing-img="<?php echo htmlspecialchars(basename($c['PreviousBillImage'])); ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger delete-consumer" 
                                            data-id="<?php echo $c['ConsumerID']; ?>"
                                            data-name="<?php echo htmlspecialchars($c['FirstName'] . ' ' . $c['LastName']); ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<!-- Edit Consumer Modal (Horizontal Layout) -->
<div class="modal fade" id="editConsumerModal" tabindex="-1" aria-labelledby="editConsumerLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <form method="POST" id="editConsumerForm">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="editConsumerLabel">Edit Consumer</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="action" value="update_consumer">
          <input type="hidden" name="consumer_id" id="editConsumerId">
          <input type="hidden" name="ajax" value="1">

          <div class="row g-3">
            <div class="col-md-6">
              <label for="editFirstName" class="form-label">First Name</label>
              <input type="text" class="form-control" id="editFirstName" name="first_name" required>
            </div>
            <div class="col-md-6">
              <label for="editLastName" class="form-label">Last Name</label>
              <input type="text" class="form-control" id="editLastName" name="last_name" required>
            </div>

            <div class="col-md-6">
              <label for="editEmail" class="form-label">Email</label>
              <input type="email" class="form-control" id="editEmail" name="email" required>
            </div>
            <div class="col-md-6">
              <label for="editContact" class="form-label">Contact Number</label>
              <input type="text" class="form-control" id="editContact" name="contact_number" required>
            </div>

            <div class="col-md-6">
              <label for="editStatus" class="form-label">Status</label>
              <select class="form-select" id="editStatus" name="status" required>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
              </select>
            </div>
            <div class="col-md-6">
              <label for="editBillingPreference" class="form-label">Billing Preference</label>
              <select class="form-select" id="editBillingPreference" name="billing_preference">
                <option value="paper">Paper</option>
                <option value="mobile_app">Mobile App</option>
              </select>
            </div>

            <div class="col-12">
              <label for="editAddress" class="form-label">Address</label>
              <input type="text" class="form-control" id="editAddress" name="address" required>
            </div>

            <!-- Billing Image Preview (View Only) -->
            <div class="col-12 mt-3">
              <label class="form-label">Previous Billing Statement</label>
              <div id="billingImageContainer" class="border rounded p-2 text-center">
                <a href="#" id="billingImageLink" target="_blank" style="display:none;">
                  <img id="billingImagePreview" src="" 
                       alt="Billing Statement" 
                       class="img-fluid rounded" 
                       style="max-height: 250px;">
                </a>
                <div id="noBillingImage" class="text-muted">No image uploaded</div>
              </div>
            </div>

          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Update Consumer</button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Full Image Modal -->
<div class="modal fade" id="fullImageModal" tabindex="-1" aria-labelledby="fullImageLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-xl">
    <div class="modal-content">
      <div class="modal-body text-center p-0">
        <img id="fullImage" src="" alt="Full Billing Statement" class="img-fluid w-100" style="object-fit:contain; max-height:90vh;">
      </div>
      <div class="modal-footer">
        <a id="downloadImageLink" href="#" class="btn btn-outline-primary" download>Download</a>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Modal instances
    const fullModalEl = document.getElementById('fullImageModal');
    const fullModal = new bootstrap.Modal(fullModalEl);
    const fullImg = document.getElementById('fullImage');
    const downloadLink = document.getElementById('downloadImageLink');

    const editButtons = document.querySelectorAll('.edit-consumer');
    const editModalEl = document.getElementById('editConsumerModal');
    const editModal = new bootstrap.Modal(editModalEl);
    const editForm = document.getElementById('editConsumerForm');
    const alertContainer = document.getElementById('alertContainer');

    const billingImagePreview = document.getElementById('billingImagePreview');
    const billingImageLink = document.getElementById('billingImageLink');
    const noBillingImage = document.getElementById('noBillingImage');

    // Function to open full image modal
    function openFullImage(imgSrc) {
        fullImg.src = imgSrc;
        downloadLink.href = imgSrc;
        fullModal.show();
    }

    // Handle all thumbnail clicks (existing + dynamically added)
    document.body.addEventListener('click', function(e){
        const el = e.target.closest && e.target.closest('.open-image');
        if (el) {
            e.preventDefault();
            const img = el.dataset.img;
            if (img) openFullImage(img);
        }
    });

    // Billing image preview click
    billingImageLink.addEventListener('click', function(e){
        e.preventDefault();
        if (this.href && this.href !== '#') openFullImage(this.href);
    });

    // Open edit modal and populate fields
    editButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById('editConsumerId').value = btn.dataset.id || '';
            document.getElementById('editFirstName').value = btn.dataset.firstname || '';
            document.getElementById('editLastName').value = btn.dataset.lastname || '';
            document.getElementById('editEmail').value = btn.dataset.email || '';
            document.getElementById('editContact').value = btn.dataset.contact || '';
            document.getElementById('editAddress').value = btn.dataset.address || '';
            document.getElementById('editStatus').value = btn.dataset.status || 'active';
            document.getElementById('editBillingPreference').value = btn.dataset.billingPreference || 'paper';

            const billingImgBasename = btn.dataset.billingImg || '';
            if (billingImgBasename) {
                const src = '../uploads/previous_bills/' + billingImgBasename;
                billingImagePreview.src = src;
                billingImageLink.href = src;
                billingImageLink.style.display = 'inline-block';
                billingImagePreview.style.display = 'inline-block';
                noBillingImage.style.display = 'none';
            } else {
                billingImagePreview.src = '';
                billingImageLink.href = '#';
                billingImageLink.style.display = 'none';
                billingImagePreview.style.display = 'none';
                noBillingImage.style.display = 'block';
            }

            editModal.show();
        });
    });

    // Handle AJAX edit form submission
    editForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(editForm);

        fetch(window.location.href, { method: "POST", body: formData })
            .then(res => {
                // Check if response is ok
                if (!res.ok) {
                    throw new Error('Network response was not ok');
                }
                return res.text(); // Get as text first
            })
            .then(text => {
                // Try to parse as JSON
                let data;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    console.error('Response was not JSON:', text);
                    throw new Error('Server returned invalid JSON');
                }
                
                alertContainer.innerHTML = `<div class="alert alert-${data.success ? 'success' : 'danger'}">${data.message}</div>`;
                
                if (data.success) {
                    const consumerId = formData.get('consumer_id');
                    const row = document.getElementById('consumerRow-' + consumerId);
                    if (row) {
                        // Update name
                        row.querySelector('td:nth-child(3)').innerText = formData.get('first_name') + ' ' + formData.get('last_name');
                        // Update email
                        row.querySelector('td:nth-child(4)').innerText = formData.get('email');
                        // Update contact
                        row.querySelector('td:nth-child(5)').innerText = formData.get('contact_number');
                        // Update address
                        row.querySelector('td:nth-child(6)').innerText = formData.get('address');

                        // Update billing preference badge
                        const billingBadge = row.querySelector('td:nth-child(8) .badge');
                        if (billingBadge) {
                            const billingPref = formData.get('billing_preference');
                            billingBadge.innerText = billingPref === 'mobile_app' ? 'Mobile App' : 'Paper';
                            billingBadge.className = 'badge bg-' + (billingPref === 'mobile_app' ? 'info' : 'secondary');
                        }

                        // Update status badge
                        const statusBadge = row.querySelector('td:nth-child(9) .badge');
                        if (statusBadge) {
                            const statusValue = formData.get('status');
                            statusBadge.innerText = statusValue.charAt(0).toUpperCase() + statusValue.slice(1);
                            statusBadge.className = 'badge bg-' + (statusValue === 'active' ? 'success' : 'danger');
                        }

                        // Update edit button data attributes
                        const editBtn = row.querySelector('.edit-consumer');
                        if (editBtn) {
                            editBtn.dataset.firstname = formData.get('first_name');
                            editBtn.dataset.lastname = formData.get('last_name');
                            editBtn.dataset.email = formData.get('email');
                            editBtn.dataset.contact = formData.get('contact_number');
                            editBtn.dataset.address = formData.get('address');
                            editBtn.dataset.status = formData.get('status');
                            editBtn.dataset.billingPreference = formData.get('billing_preference');
                        }
                    }
                    editModal.hide();
                    
                    // Scroll to alert
                    alertContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }
            })
            .catch(err => {
                console.error('Error:', err);
                alertContainer.innerHTML = `<div class="alert alert-danger">An error occurred: ${err.message}. Please try again.</div>`;
            });
    });
});
</script>