<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Utility Billing System</title>
    <!-- jQuery first -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="/utility_billing_system/assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php if (session_status() === PHP_SESSION_ACTIVE): ?>
    <header class="navbar navbar-dark static-top bg-primary flex-md-nowrap p-0 shadow-lg" style="min-height: 70px;">
        <a class="navbar-brand col-md-5 col-lg-5  px-4  fs-4 fw-bold" href="/utility_billing_system/public/dashboard.php">
            <i class="fas fa-tint me-2"></i>Utility Billing System
        </a>
        <button class="navbar-toggler position-absolute d-md-none collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="navbar-nav ms-auto">
            <div class="nav-item d-flex align-items-center me-3">
                <a class="nav-link px-4 py-3 text-white fw-medium d-flex align-items-center" href="/utility_billing_system/public/logout.php" style="transition: all 0.3s ease;">
                    <i class="fas fa-sign-out-alt me-2"></i>Sign out
                </a>
            </div>
        </div>
    </header>
    <?php endif; ?>
    <div class="container-fluid p-0">
        <div class="row g-0">
