<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Utility Billing System - Portal Selection</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/custom.css" rel="stylesheet">
</head>
<body>
    <div class="role-selection">
        <div class="container">
            <div class="text-center mb-5">
                <h1 class="text-white display-4 mb-3">Utility Billing System</h1>
                <p class="text-white lead">Select your portal to continue</p>
            </div>
            
            <div class="row g-4 justify-content-center">
                <!-- Utility Reader Portal -->
                <div class="col-md-5 col-lg-4">
                    <a href="reader_portal/index.php" class="text-decoration-none">
                        <div class="role-card">
                            <div class="role-icon">👷‍♂️</div>
                            <h3>Utility Reader</h3>
                            <p>Record meter readings and manage assigned meters</p>
                        </div>
                    </a>
                </div>
                
                <!-- Consumer Portal -->
                <div class="col-md-5 col-lg-4">
                    <a href="consumer_portal/index.php" class="text-decoration-none">
                        <div class="role-card">
                            <div class="role-icon">👩‍🔧</div>
                            <h3>Consumer</h3>
                            <p>View your bills, usage history, and payment status</p>
                        </div>
                    </a>
                </div>
            </div>
            
            <div class="text-center mt-5">
                <a href="public/index.php" class="btn btn-outline-light">← Back to Homepage</a>
            </div>
            
            <div class="text-center mt-3">
                <p class="text-white-50">&copy; 2025 Utility Billing System. All rights reserved.</p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
