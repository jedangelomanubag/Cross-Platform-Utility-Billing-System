<?php
require_once __DIR__ . '/../includes/config.php';

// Debug: Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Debug: Print session data
echo "<!-- Debug - Session Data: ";
print_r($_SESSION);
echo " -->\n";

require_once __DIR__ . '/../includes/auth.php';

// Debug: Check admin status before requiring login
echo "<!-- Debug - Before requireAdminLogin() -->\n";

requireAdminLogin();

echo "<!-- Debug - After requireAdminLogin() -->\n";

// Set page title
$page_title = 'Add New Utility Reader';

// Initialize variables
$errors = [];
$reader = [
    'FirstName' => '',
    'LastName' => '',
    'Username' => '',
    'Email' => '',
    'ContactNumber' => '',
    'Area' => '',
    'Status' => 'active',
    'Password' => ''
];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate input
    $reader['FirstName'] = trim($_POST['first_name'] ?? '');
    $reader['LastName'] = trim($_POST['last_name'] ?? '');
    $reader['Username'] = trim($_POST['username'] ?? '');
    $reader['Email'] = trim($_POST['email'] ?? '');
    $reader['ContactNumber'] = trim($_POST['contact_number'] ?? '');
    $reader['Area'] = trim($_POST['area'] ?? '');
    $reader['Status'] = $_POST['status'] === 'active' ? 'active' : 'inactive';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validate required fields
    if (empty($reader['FirstName'])) {
        $errors[] = 'First name is required';
    }
    if (empty($reader['LastName'])) {
        $errors[] = 'Last name is required';
    }
    if (empty($reader['Email'])) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($reader['Email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    }
    if (empty($password)) {
        $errors[] = 'Password is required';
    } elseif (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long';
    } elseif ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match';
    }

    // Check if email already exists
    $email_check = mysqli_prepare($conn, "SELECT ReaderID FROM utilityreader WHERE Email = ?");
    mysqli_stmt_bind_param($email_check, 's', $reader['Email']);
    mysqli_stmt_execute($email_check);
    mysqli_stmt_store_result($email_check);
    if (mysqli_stmt_num_rows($email_check) > 0) {
        $errors[] = 'Email already exists';
    }
    mysqli_stmt_close($email_check);
    
    // Check if username already exists
    if (!empty($reader['Username'])) {
        $username_check = mysqli_prepare($conn, "SELECT ReaderID FROM utilityreader WHERE Username = ?");
        mysqli_stmt_bind_param($username_check, 's', $reader['Username']);
        mysqli_stmt_execute($username_check);
        mysqli_stmt_store_result($username_check);
        if (mysqli_stmt_num_rows($username_check) > 0) {
            $errors[] = 'Username already exists';
        }
        mysqli_stmt_close($username_check);
    }

    // If no errors, insert the new reader
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Generate username if not provided
        if (empty($reader['Username'])) {
            $baseUsername = strtolower(substr($reader['FirstName'], 0, 1) . $reader['LastName']);
            $username = $baseUsername;
            $counter = 1;
            
            // Ensure username is unique
            do {
                $checkStmt = mysqli_prepare($conn, "SELECT ReaderID FROM utilityreader WHERE Username = ?");
                mysqli_stmt_bind_param($checkStmt, 's', $username);
                mysqli_stmt_execute($checkStmt);
                mysqli_stmt_store_result($checkStmt);
                
                if (mysqli_stmt_num_rows($checkStmt) > 0) {
                    $username = $baseUsername . $counter++;
                } else {
                    break;
                }
                
                mysqli_stmt_close($checkStmt);
            } while (true);
            
            $reader['Username'] = $username;
        }
        
        // Generate API token
        $apiToken = bin2hex(random_bytes(32));
        
        $sql = "
            INSERT INTO utilityreader 
            (FirstName, LastName, Username, Email, ContactNumber, Area, Password, Status, CreatedAt) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ";
        
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, 'ssssssss', 
            $reader['FirstName'],
            $reader['LastName'],
            $reader['Username'],
            $reader['Email'],
            $reader['ContactNumber'],
            $reader['Area'],
            $hashed_password,
            $reader['Status']
        );

        if (mysqli_stmt_execute($stmt)) {
            $reader_id = mysqli_insert_id($conn);
            
            // Get the created reader data for the success message
            $selectStmt = mysqli_prepare($conn, "
                SELECT ReaderID, FirstName, LastName, Username, Email, ContactNumber, Area, Status, CreatedAt 
                FROM utilityreader 
                WHERE ReaderID = ?
            ");
            mysqli_stmt_bind_param($selectStmt, 'i', $reader_id);
            mysqli_stmt_execute($selectStmt);
            $result = mysqli_stmt_get_result($selectStmt);
            $createdReader = mysqli_fetch_assoc($result);
            
            $_SESSION['success_message'] = 'Utility reader added successfully';
            
            // If this is an API request, return JSON response
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Utility reader added successfully',
                    'data' => [
                        'reader' => $createdReader,
                        'token' => $apiToken  // Only returned on creation
                    ]
                ]);
                exit();
            }
            
            // Regular form submission
            header('Location: utility_readers.php');
            exit();
        } else {
            $errors[] = 'Failed to add utility reader: ' . mysqli_error($conn);
        }
        
        mysqli_stmt_close($stmt);
    }
}

// Include header and sidebar
include_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Add New Utility Reader</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="utility_readers.php" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Readers
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
            <form method="POST" action="add_reader.php">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="first_name" name="first_name" 
                               value="<?php echo htmlspecialchars($reader['FirstName']); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="last_name" name="last_name" 
                               value="<?php echo htmlspecialchars($reader['LastName']); ?>" required>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?php echo htmlspecialchars($reader['Email']); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="contact_number" class="form-label">Contact Number <span class="text-danger">*</span></label>
                        <input type="tel" class="form-control" id="contact_number" name="contact_number" 
                               value="<?php echo htmlspecialchars($reader['ContactNumber']); ?>" required>
                    </div>
                </div>

<div class="row mb-3">
                    <div class="col-md-6">
                        <label for="username" class="form-label">Username</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="username" name="username" 
                                   value="<?php echo htmlspecialchars($reader['Username']); ?>"
                                   placeholder="Leave blank to generate">
                            <button class="btn btn-outline-secondary" type="button" id="generateUsername">
                                Generate
                            </button>
                        </div>
                        <small class="form-text text-muted">Leave blank to generate automatically</small>
                    </div>
                    <div class="col-md-6">
                        <label for="area" class="form-label">Assigned Area <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="area" name="area" 
                               value="<?php echo htmlspecialchars($reader['Area']); ?>" required>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <small class="form-text text-muted">Minimum 8 characters</small>
                    </div>
                    <div class="col-md-6">
                        <label for="confirm_password" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="status" name="status" value="active" 
                                   <?php echo $reader['Status'] === 'active' ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="status">
                                Active
                            </label>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between mt-4">
                    <a href="utility_readers.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Back to List
                    </a>
                    <div>
                        <button type="reset" class="btn btn-outline-secondary me-2">
                            <i class="fas fa-undo me-1"></i> Reset
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Save Reader
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
        </main>
    </div>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>

<script>
// Generate username from first and last name
document.getElementById('generateUsername').addEventListener('click', function() {
    const firstName = document.getElementById('first_name').value.trim();
    const lastName = document.getElementById('last_name').value.trim();
    
    if (firstName && lastName) {
        // Generate username as first letter of first name + last name (lowercase)
        const username = (firstName.charAt(0) + lastName).toLowerCase()
            .replace(/[^a-z0-9]/g, ''); // Remove special characters
        document.getElementById('username').value = username;
    } else {
        alert('Please enter both first and last name to generate username');
    }
});

// Form validation
document.querySelector('form').addEventListener('submit', function(e) {
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirm_password');
    
    // Check password length
    if (password.value.length < 8) {
        e.preventDefault();
        alert('Password must be at least 8 characters long');
        return false;
    }
    
    // Check if passwords match
    if (password.value !== confirmPassword.value) {
        e.preventDefault();
        alert('Passwords do not match');
        return false;
    }
    
    return true;
});
</script>
