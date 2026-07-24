<?php
require_once '../config/db.php';
require_once '../includes/common_functions.php';

$page_title = 'Customer Registration';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Validate and sanitize input
    $accountNo = trim($_POST['account_no'] ?? '');
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $contactNumber = trim($_POST['contact_number'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $billingPreference = in_array($_POST['billing_preference'] ?? '', ['paper', 'mobile_app'])
        ? $_POST['billing_preference']
        : 'paper';

    // Validate input
    if (
        empty($accountNo) || empty($firstName) || empty($lastName) ||
        empty($email) || empty($contactNumber) || empty($address) ||
        empty($password) || empty($confirmPassword)
    ) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } else {

        // Check if email already exists
        $stmt = $conn->prepare("SELECT ConsumerID FROM consumer WHERE Email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error = 'This email is already registered.';
        } else {

            // ======================================================
            // 🚀 IMAGE UPLOAD HANDLER FOR PreviousBillImage
            // ======================================================
            $previousBillImagePath = null;

            if (!empty($_FILES['previous_bill_image']['name'])) {

                $targetDir = "../uploads/previous_bills/";
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0777, true);
                }

                $fileName = time() . "_" . basename($_FILES['previous_bill_image']['name']);
                $targetFile = $targetDir . $fileName;

                $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
                $allowedTypes = ['jpg', 'jpeg', 'png'];

                // Validate type
                if (!in_array($fileType, $allowedTypes)) {
                    $error = "Only JPG and PNG images are allowed.";
                }

                // Validate size (max 5MB)
                if ($_FILES['previous_bill_image']['size'] > (5 * 1024 * 1024)) {
                    $error = "Image file is too large. Maximum size is 5MB.";
                }

                // Upload if no errors
                if (empty($error)) {
                    if (move_uploaded_file($_FILES['previous_bill_image']['tmp_name'], $targetFile)) {
                        $previousBillImagePath = "uploads/previous_bills/" . $fileName;
                    } else {
                        $error = "Failed to upload image.";
                    }
                }

            } else {
                $error = "Please upload an image of your previous billing statement.";
            }
            // ======================================================


            if (empty($error)) {
                // Hash password
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                // INSERT QUERY updated for PreviousBillImage
                $stmt = $conn->prepare("
                    INSERT INTO consumer 
                    (AccountNo, PreviousBillImage, FirstName, LastName, Email, ContactNumber, Address, Password, BillingPreference, Status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
                ");

                $stmt->bind_param(
                    'sssssssss',
                    $accountNo,
                    $previousBillImagePath,
                    $firstName,
                    $lastName,
                    $email,
                    $contactNumber,
                    $address,
                    $hashedPassword,
                    $billingPreference
                );

                if ($stmt->execute()) {

                    // If mobile app selected → redirect
                    if ($billingPreference === 'mobile_app') {
                        session_start();
                        $_SESSION['show_app_download'] = true;
                        $_SESSION['registered_email'] = $email;
                        header('Location: app_download.php');
                        exit();
                    } else {
                        $success = "Registration successful! Please wait for account approval.";
                        $_POST = [];
                    }

                } else {
                    $error = "Registration failed. Please try again.";
                }
            }
        }

        $stmt->close();
    }
}


include '../includes/header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Customer Registration</h4>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                    <?php else: ?>
                       <form method="post" action="" id="registrationForm" enctype="multipart/form-data">

                            <!-- Account No -->
                            <div class="mb-3">
                                <label for="account_no" class="form-label">Account No <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="account_no" name="account_no"
                                       value="<?php echo htmlspecialchars($_POST['account_no'] ?? ''); ?>" required>
                            </div>

                            <!-- NEW FIELD: Upload Previous Billing Statement -->
                            <div class="mb-3">
                                <label for="previous_bill_image" class="form-label">Previous Billing Statement (Upload Image) <span class="text-danger">*</span></label>
                                <input type="file" class="form-control" id="previous_bill_image" name="previous_bill_image" accept="image/*"                            required>
                                <small class="text-muted">Accepted formats: JPG, PNG. Max size: 5MB.</small>
                            </div>


                            <!-- First & Last Name -->
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" 
                                           value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" 
                                           value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>" required>
                                </div>
                            </div>

                            <!-- (rest of your form stays the same) -->

                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="contact_number" class="form-label">Contact Number <span class="text-danger">*</span></label>
                                <input type="tel" class="form-control" id="contact_number" name="contact_number" 
                                       value="<?php echo htmlspecialchars($_POST['contact_number'] ?? ''); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="address" class="form-label">Complete Address <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="address" name="address" rows="3" required><?php 
                                    echo htmlspecialchars($_POST['address'] ?? ''); 
                                ?></textarea>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                    <div class="form-text">At least 8 characters</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="confirm_password" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label">Billing Preference <span class="text-danger">*</span></label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="billing_preference" 
                                           id="billing_paper" value="paper" 
                                           <?php echo ($_POST['billing_preference'] ?? 'paper') === 'paper' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="billing_paper">
                                        <i class="fas fa-envelope me-2"></i>Paper Billing
                                        <small class="d-block text-muted">Receive paper bills by house delivery</small>
                                    </label>
                                </div>
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="radio" name="billing_preference" 
                                           id="billing_mobile" value="mobile_app"
                                           <?php echo ($_POST['billing_preference'] ?? '') === 'mobile_app' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="billing_mobile">
                                        <i class="fas fa-mobile-alt me-2"></i>Mobile App
                                        <small class="d-block text-muted">Receive digital bills and notifications</small>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-user-plus me-2"></i>Register
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="mt-3 text-center">
                <a href="/utility_billing_system/public/index.php" class="text-decoration-none">
                    <i class="fas fa-arrow-left me-1"></i> Back to Home
                </a>
            </div>
        </div>
    </div>
</div>

<script>
// Client-side form validation
document.getElementById('registrationForm').addEventListener('submit', function(e) {
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    
    if (password.length < 8) {
        e.preventDefault();
        alert('Password must be at least 8 characters long.');
        return false;
    }
    
    if (password !== confirmPassword) {
        e.preventDefault();
        alert('Passwords do not match.');
        return false;
    }
    
    return true;
});
</script>

<?php include '../includes/footer.php'; ?>
