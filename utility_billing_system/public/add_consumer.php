<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

// Ensure admin is logged in
requireAdminLogin();

// Set page title
$page_title = 'Add New Consumer';

// Initialize variables
$errors = [];
$consumer = [
    'FirstName' => '',
    'LastName' => '',
    'Email' => '',
    'ContactNumber' => '',
    'Address' => '',
    'BillingPreference' => 'paper',
    'Status' => 'active'
];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate input
    $consumer['FirstName'] = trim($_POST['first_name'] ?? '');
    $consumer['LastName'] = trim($_POST['last_name'] ?? '');
    $consumer['Email'] = trim($_POST['email'] ?? '');
    $consumer['ContactNumber'] = trim($_POST['contact_number'] ?? '');
    $consumer['Address'] = trim($_POST['address'] ?? '');
    $consumer['BillingPreference'] = $_POST['billing_preference'] === 'email' ? 'email' : 'paper';
    $consumer['Status'] = $_POST['status'] === 'active' ? 'active' : 'inactive';
    
    // Validate required fields
    if (empty($consumer['FirstName'])) {
        $errors[] = 'First name is required';
    }
    
    if (empty($consumer['LastName'])) {
        $errors[] = 'Last name is required';
    }
    
    if (empty($consumer['Email'])) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($consumer['Email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address';
    }
    
    if (empty($consumer['ContactNumber'])) {
        $errors[] = 'Contact number is required';
    }
    
    if (empty($consumer['Address'])) {
        $errors[] = 'Address is required';
    }
    
    // Check if email already exists
    $check_email = "SELECT ConsumerID FROM Consumer WHERE Email = ?";
    $stmt = mysqli_prepare($conn, $check_email);
    mysqli_stmt_bind_param($stmt, 's', $consumer['Email']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        $errors[] = 'Email already exists';
    }
    
    // If no errors, insert into database
    if (empty($errors)) {
        // Generate a random password
        $password = bin2hex(random_bytes(8));
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $query = "INSERT INTO Consumer (FirstName, LastName, Email, ContactNumber, Address, Status, Password, BillingPreference) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, 'ssssssss', 
            $consumer['FirstName'],
            $consumer['LastName'],
            $consumer['Email'],
            $consumer['ContactNumber'],
            $consumer['Address'],
            $consumer['Status'],
            $hashedPassword,
            $consumer['BillingPreference']
        );
        
        if (mysqli_stmt_execute($stmt)) {
            $consumerId = mysqli_insert_id($conn);
            logAdminAction($_SESSION['admin_id'], "Added new consumer: {$consumer['FirstName']} {$consumer['LastName']} (ID: $consumerId)");
            
            // Set success message and redirect
            $_SESSION['success'] = "Consumer added successfully. Temporary password: $password";
            header('Location: consumers.php');
            exit();
        } else {
            $errors[] = 'Failed to add consumer: ' . mysqli_error($conn);
        }
        
        mysqli_stmt_close($stmt);
    }
}

// Include header and sidebar
include_once __DIR__ . '/../includes/header.php';
include_once __DIR__ . '/../includes/sidebar.php';
?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Add New Consumer</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="consumers.php" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Back to Consumers
            </a>
        </div>
    </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="first_name" name="first_name" 
                                       value="<?php echo htmlspecialchars($consumer['FirstName']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="last_name" name="last_name" 
                                       value="<?php echo htmlspecialchars($consumer['LastName']); ?>" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($consumer['Email']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="contact_number" class="form-label">Contact Number <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="contact_number" name="contact_number" 
                                       value="<?php echo htmlspecialchars($consumer['ContactNumber']); ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="address" class="form-label">Address <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="address" name="address" rows="3" required><?php 
                                echo htmlspecialchars($consumer['Address']); 
                            ?></textarea>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Billing Preference</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="billing_preference" id="billing_paper" 
                                           value="paper" <?php echo $consumer['BillingPreference'] === 'paper' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="billing_paper">
                                        Paper Bill
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="billing_preference" id="billing_email" 
                                           value="email" <?php echo $consumer['BillingPreference'] === 'email' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="billing_email">
                                        Email Bill
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="status" name="status" 
                                           value="active" <?php echo $consumer['Status'] === 'active' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="status">Active</label>
                                </div>
                                <small class="text-muted">Inactive consumers cannot log in to the system.</small>
                            </div>
                        </div>

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> A random password will be generated for the consumer upon successful submission.
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="consumers.php" class="btn btn-secondary me-md-2">Cancel</a>
                            <button type="submit" class="btn btn-primary">Add Consumer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
