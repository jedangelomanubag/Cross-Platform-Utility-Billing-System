<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/db.php';
require_once '../functions/admin_functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if already logged in
if (isAdminLoggedIn()) {
    header("Location: dashboard.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Log the login attempt
    error_log('Login attempt from IP: ' . $_SERVER['REMOTE_ADDR'] . ' for email: ' . $_POST['email']);
    
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    
    // Basic validation
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password';
        error_log('Login failed: Empty email or password');
    } else {
        // Prepare the query to prevent SQL injection
        $query = "SELECT AdminID, Email, Password, FirstName, LastName, Status FROM admin WHERE Email = ? LIMIT 1";
        $stmt = mysqli_prepare($conn, $query);
        
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 's', $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if ($result && mysqli_num_rows($result) > 0) {
                $admin = mysqli_fetch_assoc($result);
                
                // Check if account is active
                if ($admin['Status'] !== 'active') {
                    $error = 'This account is inactive. Please contact support.';
                    error_log('Login failed: Inactive account for ' . $email);
                } 
                // Verify the password
                elseif (password_verify($password, $admin['Password'])) {
                    // Password is correct, start a new session
                    if (session_status() === PHP_SESSION_NONE) {
                        session_start();
                    }
                    
                    $_SESSION['admin_id'] = $admin['AdminID'];
                    $_SESSION['admin_email'] = $admin['Email'];
                    $_SESSION['admin_name'] = trim($admin['FirstName'] . ' ' . $admin['LastName']);
                    
                    // Update last login time
                    $updateQuery = "UPDATE admin SET LastLogin = NOW() WHERE AdminID = ?";
                    $updateStmt = mysqli_prepare($conn, $updateQuery);
                    if ($updateStmt) {
                        mysqli_stmt_bind_param($updateStmt, 'i', $admin['AdminID']);
                        mysqli_stmt_execute($updateStmt);
                        mysqli_stmt_close($updateStmt);
                    }
                    
                    // Log the successful login
                    logAdminAction($admin['AdminID'], 'Admin logged in');
                    error_log('Login successful for admin ID: ' . $admin['AdminID']);
                    
                    // Redirect to dashboard
                    header("Location: dashboard.php");
                    exit();
                } else {
                    $error = 'Invalid email or password';
                    error_log('Login failed: Invalid password for ' . $email);
                }
                
                mysqli_free_result($result);
            } else {
                $error = 'Invalid email or password';
                error_log('Login failed: No user found with email ' . $email);
            }
            
            mysqli_stmt_close($stmt);
        } else {
            $error = 'Database error. Please try again later.';
            error_log('Database error in login: ' . mysqli_error($conn));
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Utility Billing System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #0d6efd;
            --secondary-color: #6c757d;
        }
        
        body {
            background-color: #f8f9fa;
            height: 100vh;
            display: flex;
            align-items: center;
        }
        
        .login-container {
            max-width: 420px;
            width: 100%;
            margin: 0 auto;
            padding: 2.5rem;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(0, 0, 0, 0.1);
        }
        
        .login-logo {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .login-logo i {
            font-size: 3rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        
        .login-logo h2 {
            color: #333;
            font-weight: 600;
            margin: 0;
        }
        
        .login-logo p {
            color: var(--secondary-color);
            margin: 0.5rem 0 0;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }
        
        .btn-login {
            background-color: var(--primary-color);
            border: none;
            padding: 0.75rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s;
        }
        
        .btn-login:hover {
            background-color: #0b5ed7;
            transform: translateY(-1px);
        }
        
        .form-floating > label {
            padding: 1rem 0.75rem;
        }
        
        .form-floating > .form-control:focus ~ label,
        .form-floating > .form-control:not(:placeholder-shown) ~ label {
            transform: scale(0.85) translateY(-0.5rem) translateX(0.15rem);
            opacity: 0.8;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="login-container">
                    <div class="login-logo">
                        <h2>Administrator Login</h2>
                    </div>
                    
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <?php echo htmlspecialchars($error); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="" id="loginForm" novalidate>
                        <div class="form-floating mb-3">
                            <input type="email" class="form-control" id="email" name="email" 
                                   placeholder="name@example.com" required 
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                            <label for="email"><i class="fas fa-envelope me-2"></i>Email address</label>
                            <div class="invalid-feedback">
                                Please enter a valid email address.
                            </div>
                        </div>
                        
                        <div class="form-floating mb-4">
                            <input type="password" class="form-control" id="password" 
                                   name="password" placeholder="Password" required>
                            <label for="password"><i class="fas fa-lock me-2"></i>Password</label>
                            <div class="invalid-feedback">
                                Please enter your password.
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-login">
                                <i class="fas fa-sign-in-alt me-2"></i>Sign In
                            </button>
                        </div>
                    </form>
                </div>
                
                <div class="text-center mt-4 text-muted">
                    <small>&copy; <?php echo date('Y'); ?> Utility Billing System. All rights reserved.</small>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validation
        (function () {
            'use strict'
            
            // Fetch the form we want to apply custom Bootstrap validation styles to
            var form = document.getElementById('loginForm')
            
            // Add validation on form submission
            form.addEventListener('submit', function (event) {
                if (!form.checkValidity()) {
                    event.preventDefault()
                    event.stopPropagation()
                }
                
                form.classList.add('was-validated')
            }, false)
        })()
    </script>
