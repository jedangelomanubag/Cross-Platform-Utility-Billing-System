<?php if (session_status() === PHP_SESSION_ACTIVE): ?>
<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block sidebar collapse">
    <div class="position-sticky pt-3">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" 
                   href="/utility_billing_system/public/dashboard.php">
                    <i class="fas fa-tachometer-alt me-2"></i>
                    Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'consumers.php' ? 'active' : ''; ?>" 
                   href="/utility_billing_system/public/consumers.php">
                    <i class="fas fa-users me-2"></i>
                    Consumers
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'utility_readers.php' ? 'active' : ''; ?>" 
                   href="/utility_billing_system/public/utility_readers.php">
                    <i class="fas fa-user-tie me-2"></i>
                    Utility Readers
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'meters.php' ? 'active' : ''; ?>" 
                   href="/utility_billing_system/public/meters.php">
                    <i class="fas fa-tachometer-alt me-2"></i>
                    Meters
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'meter_readings.php' ? 'active' : ''; ?>" 
                   href="/utility_billing_system/public/meter_readings.php">
                    <i class="fas fa-file-invoice me-2"></i>
                    Meter Readings
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'billing_statements.php' ? 'active' : ''; ?>" 
                   href="/utility_billing_system/public/billing_statements.php">
                    <i class="fas fa-file-invoice-dollar me-2"></i>
                    Billing Statements
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'Reports.php' ? 'active' : ''; ?>" 
                   href="/utility_billing_system/public/reports.php">
                    <i class="fas fa-file-invoice me-2"></i>
                    Reports
                </a>
            </li>
        </ul>
    </div>
</nav>
<?php endif; ?>
