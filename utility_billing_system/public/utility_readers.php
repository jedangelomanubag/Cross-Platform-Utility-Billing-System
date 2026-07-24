<?php
require_once '../config/db.php';
require_once '../functions/admin_functions.php';

// Ensure admin is logged in
requireAdminLogin();

// Set page title
$page_title = 'Utility Readers';

// Get all utility readers
$readers = [];
$result = mysqli_query($conn, "SELECT * FROM utilityreader ORDER BY FirstName, LastName");
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $readers[] = $row;
    }
}

// Include header and sidebar
include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Utility Readers</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="add_reader.php" class="btn btn-sm btn-primary">
                <i class="fas fa-plus"></i> Add New Reader
            </a>
        </div>
    </div>

    <!-- Search Bar -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <label for="searchInput" class="form-label">Search Utility Readers</label>
                    <input type="text" class="form-control" id="searchInput" placeholder="Search by name, email, contact, username, or area...">
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
                <small class="text-muted">Found: <span id="resultCount"><?php echo count($readers); ?></span> reader(s)</small>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-striped table-hover" id="readersTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Contact</th>
                    <th>Username</th>
                    <th>Area</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($readers)): ?>
                    <?php foreach ($readers as $reader): ?>
                        <tr data-name="<?php echo strtolower(htmlspecialchars($reader['FirstName'] . ' ' . $reader['LastName'])); ?>"
                            data-email="<?php echo strtolower(htmlspecialchars($reader['Email'])); ?>"
                            data-contact="<?php echo strtolower(htmlspecialchars($reader['ContactNumber'])); ?>"
                            data-username="<?php echo strtolower(htmlspecialchars($reader['Username'])); ?>"
                            data-area="<?php echo strtolower(htmlspecialchars($reader['Area'] ?? '')); ?>"
                            data-status="<?php echo strtolower($reader['Status']); ?>">
                            <td><?php echo htmlspecialchars($reader['ReaderID']); ?></td>
                            <td><?php echo htmlspecialchars($reader['FirstName'] . ' ' . $reader['LastName']); ?></td>
                            <td><?php echo htmlspecialchars($reader['Email']); ?></td>
                            <td><?php echo htmlspecialchars($reader['ContactNumber']); ?></td>
                            <td><?php echo htmlspecialchars($reader['Username']); ?></td>
                            <td><?php echo htmlspecialchars($reader['Area'] ?? '-'); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $reader['Status'] === 'active' ? 'success' : 'secondary'; ?>">
                                    <?php echo ucfirst($reader['Status']); ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-primary edit-reader" 
                                        data-id="<?php echo $reader['ReaderID']; ?>"
                                        data-firstname="<?php echo htmlspecialchars($reader['FirstName']); ?>"
                                        data-lastname="<?php echo htmlspecialchars($reader['LastName']); ?>"
                                        data-username="<?php echo htmlspecialchars($reader['Username']); ?>"
                                        data-email="<?php echo htmlspecialchars($reader['Email']); ?>"
                                        data-contact="<?php echo htmlspecialchars($reader['ContactNumber']); ?>"
                                        data-area="<?php echo htmlspecialchars($reader['Area'] ?? ''); ?>"
                                        data-status="<?php echo $reader['Status']; ?>"
                                        title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-<?php echo $reader['Status'] === 'active' ? 'warning' : 'success'; ?> toggle-status" 
                                        data-id="<?php echo $reader['ReaderID']; ?>" 
                                        data-status="<?php echo $reader['Status']; ?>"
                                        title="<?php echo $reader['Status'] === 'active' ? 'Deactivate' : 'Activate'; ?>">
                                    <i class="fas fa-<?php echo $reader['Status'] === 'active' ? 'times' : 'check'; ?>"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr id="noResultsRow">
                        <td colspan="8" class="text-center">No utility readers found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

<!-- Edit Reader Modal -->
<div class="modal fade" id="editReaderModal" tabindex="-1" aria-labelledby="editReaderModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="editReaderForm" method="POST" action="ajax/update_reader.php">
                <input type="hidden" name="reader_id" id="edit_reader_id">
                <div class="modal-header">
                    <h5 class="modal-title" id="editReaderModalLabel">Edit Reader</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="edit_first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_first_name" name="first_name" required>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_last_name" name="last_name" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="edit_email" class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="edit_email" name="email" required>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_contact" class="form-label">Contact Number</label>
                            <input type="tel" class="form-control" id="edit_contact" name="contact_number">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="edit_username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="edit_username" name="username" required>
                        </div>
                        <div class="col-md-6">
                            <label for="edit_area" class="form-label">Assigned Area</label>
                            <input type="text" class="form-control" id="edit_area" name="area">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-12">
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" id="edit_status" name="status" value="active">
                                <label class="form-check-label" for="edit_status">Active</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

</main>

<!-- Success Toast -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
    <div id="successToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header bg-success text-white">
            <strong class="me-auto">Success</strong>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body"></div>
    </div>
</div>

<script>
// Wait for the document to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Search and Filter Function
    function filterTable() {
        const searchTerm = document.getElementById('searchInput').value.toLowerCase();
        const statusFilter = document.getElementById('statusFilter').value.toLowerCase();
        let visibleCount = 0;

        const rows = document.querySelectorAll('#readersTable tbody tr');
        
        rows.forEach(function(row) {
            if (row.id === 'noResultsRow') return;

            const name = row.dataset.name || '';
            const email = row.dataset.email || '';
            const contact = row.dataset.contact || '';
            const username = row.dataset.username || '';
            const area = row.dataset.area || '';
            const status = row.dataset.status || '';

            // Check if search term matches any field
            const matchesSearch = searchTerm === '' || 
                name.includes(searchTerm) ||
                email.includes(searchTerm) ||
                contact.includes(searchTerm) ||
                username.includes(searchTerm) ||
                area.includes(searchTerm);

            // Check if status matches filter
            const matchesStatus = statusFilter === '' || status === statusFilter;

            if (matchesSearch && matchesStatus) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        document.getElementById('resultCount').textContent = visibleCount;

        // Handle "no results" message
        let noResultsRow = document.getElementById('noResultsRow');
        if (visibleCount === 0 && !noResultsRow) {
            const tbody = document.querySelector('#readersTable tbody');
            noResultsRow = document.createElement('tr');
            noResultsRow.id = 'noResultsRow';
            noResultsRow.innerHTML = '<td colspan="8" class="text-center text-muted">No readers match your search.</td>';
            tbody.appendChild(noResultsRow);
        } else if (visibleCount > 0 && noResultsRow && noResultsRow.id !== 'noResultsRow') {
            noResultsRow.remove();
        }
    }

    // Bind search and filter events
    document.getElementById('searchInput').addEventListener('keyup', filterTable);
    document.getElementById('statusFilter').addEventListener('change', filterTable);

    // Initialize modal variable
    let editReaderModal = null;
    
    // Initialize the modal
    const modalElement = document.getElementById('editReaderModal');
    if (modalElement) {
        editReaderModal = new bootstrap.Modal(modalElement);
    } else {
        console.error('Could not find editReaderModal element');
    }
    
    // Handle edit button click using event delegation
    document.addEventListener('click', function(e) {
        if (e.target.closest('.edit-reader')) {
            e.preventDefault();
            const button = e.target.closest('.edit-reader');
            
            // Populate form fields
            document.getElementById('edit_reader_id').value = button.dataset.id;
            document.getElementById('edit_first_name').value = button.dataset.firstname || '';
            document.getElementById('edit_last_name').value = button.dataset.lastname || '';
            document.getElementById('edit_username').value = button.dataset.username || '';
            document.getElementById('edit_email').value = button.dataset.email || '';
            document.getElementById('edit_contact').value = button.dataset.contact || '';
            document.getElementById('edit_area').value = button.dataset.area || '';
            document.getElementById('edit_status').checked = button.dataset.status === 'active';
            
            // Show the modal
            if (editReaderModal) {
                editReaderModal.show();
            } else {
                console.error('Modal instance not found');
            }
        }
    });

    // Handle form submission
    const editForm = document.getElementById('editReaderForm');
    if (editForm) {
        editForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            // Disable the submit button to prevent multiple submissions
            const submitButton = this.querySelector('button[type="submit"]');
            const originalButtonText = submitButton.innerHTML;
            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...';
            
            try {
                // Create form data with all required fields
                const formData = new FormData();
                formData.append('reader_id', document.getElementById('edit_reader_id').value);
                formData.append('first_name', document.getElementById('edit_first_name').value);
                formData.append('last_name', document.getElementById('edit_last_name').value);
                formData.append('username', document.getElementById('edit_username').value);
                formData.append('email', document.getElementById('edit_email').value);
                formData.append('contact_number', document.getElementById('edit_contact').value);
                formData.append('area', document.getElementById('edit_area').value);
                formData.append('status', document.getElementById('edit_status').checked ? 'active' : 'inactive');
                
                console.log('Sending form data:', Object.fromEntries(formData));
                
                const response = await fetch('ajax/update_reader.php', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                
                if (!response.ok) {
                    const errorData = await response.json().catch(() => ({}));
                    throw new Error(errorData.message || 'Failed to update reader');
                }
                
                const data = await response.json();
                
                if (data.success) {
                    // Show success message
                    const toastElement = document.getElementById('successToast');
                    const toastBody = toastElement.querySelector('.toast-body');
                    toastBody.textContent = data.message || 'Reader updated successfully';
                    
                    const toast = new bootstrap.Toast(toastElement);
                    toast.show();
                    
                    // Close the modal
                    if (editReaderModal) {
                        editReaderModal.hide();
                    }
                    
                    // Reload the page after a short delay
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    throw new Error(data.message || 'Failed to update reader');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error: ' + (error.message || 'Failed to update reader. Please check the console for details.'));
            } finally {
                // Re-enable the submit button
                submitButton.disabled = false;
                submitButton.innerHTML = originalButtonText;
            }
        });
    }
    
    // Reset form when modal is hidden
    if (modalElement) {
        modalElement.addEventListener('hidden.bs.modal', function() {
            if (editForm) {
                editForm.reset();
            }
        });
    }
    
    // Handle toggle status button clicks
    document.addEventListener('click', function(e) {
        if (e.target.closest('.toggle-status')) {
            e.preventDefault();
            const button = e.target.closest('.toggle-status');
            const readerId = button.dataset.id;
            const currentStatus = button.dataset.status;
            const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
            
            if (confirm(`Are you sure you want to ${newStatus === 'active' ? 'activate' : 'deactivate'} this reader?`)) {
                const formData = new FormData();
                formData.append('id', readerId);
                formData.append('status', newStatus);
                
                fetch('ajax/update_reader_status.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.reload();
                    } else {
                        alert('Error updating reader status: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error communicating with the server');
                });
            }
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>