<?php
require_once '../config/db.php';

$page_title = 'Download Mobile App';

// Check if user just registered
session_start();
$showDownload = $_SESSION['show_app_download'] ?? false;
$registeredEmail = $_SESSION['registered_email'] ?? '';

// Clear the session variables
unset($_SESSION['show_app_download']);
unset($_SESSION['registered_email']);

// If not coming from registration, redirect to home
/*
if (!$showDownload) {
    header('Location: index.php');
    exit();
}*/

include '../includes/header.php';
?>

<!-- QR Code Library -->
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-8 text-center">
            <div class="card shadow-lg">
                <div class="card-header bg-primary text-white py-4">
                    <h1 class="h3 mb-0"><i class="fas fa-mobile-alt me-2"></i>Download Our Mobile App</h1>
                </div>
                <div class="card-body p-5">
                    <div class="mb-5">
                        <img src="/utility_billing_system/assets/images/app-icon.png" alt="App Icon" class="img-fluid mb-4" style="max-width: 150px;">
                        <h2 class="mb-3">Thank You for Registering!</h2>
                        <p class="lead">Your account is pending approval. Once approved, you can use the mobile app to:</p>
                    </div>
                    
                    <div class="row text-start mb-5">
                        <div class="col-md-6 mb-4">
                            <div class="d-flex">
                                <div class="me-3 text-primary">
                                    <i class="fas fa-tachometer-alt fa-2x"></i>
                                </div>
                                <div>
                                    <h5>View Meter Readings</h5>
                                    <p class="text-muted mb-0">Check your current and historical meter readings in real-time.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-4">
                            <div class="d-flex">
                                <div class="me-3 text-primary">
                                    <i class="fas fa-file-invoice-dollar fa-2x"></i>
                                </div>
                                <div>
                                    <h5>Pay Bills</h5>
                                    <p class="text-muted mb-0">View and pay your bills securely through the app.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-4">
                            <div class="d-flex">
                                <div class="me-3 text-primary">
                                    <i class="fas fa-bell fa-2x"></i>
                                </div>
                                <div>
                                    <h5>Get Notifications</h5>
                                    <p class="text-muted mb-0">Receive important updates about your account and services.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-4">
                            <div class="d-flex">
                                <div class="me-3 text-primary">
                                    <i class="fas fa-headset fa-2x"></i>
                                </div>
                                <div>
                                    <h5>24/7 Support</h5>
                                    <p class="text-muted mb-0">Get help anytime through our in-app support.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Download Section -->
                    <div class="bg-light p-4 rounded-3 mb-4">
                        <h4 class="mb-3 text-center">Download the App Now</h4>
                        
                        <div class="row align-items-center">
                            <div class="col-md-6 text-center mb-4 mb-md-0">
                                <h5 class="mb-3">Scan QR Code</h5>
                                <div class="d-inline-block p-3 bg-white rounded shadow-sm">
                                    <div id="qrcode" class="mb-2"></div>
                                    <small class="text-muted">Scan with your phone camera</small>
                                </div>
                            </div>
                            <div class="col-md-6 text-center">
                                <h5 class="mb-3">Or Download Directly</h5>
                                <a href="https://expo.dev/artifacts/eas/duDcCYCjPjBqJ6QZV4xCYr.apk" class="btn btn-success btn-lg mb-3 d-block" id="downloadBtn">
                                    <i class="fab fa-android me-2"></i> Download APK
                                </a>
                                <button onclick="shareApp()" class="btn btn-outline-primary btn-sm mb-2" id="shareBtn">
                                    <i class="fas fa-share-alt me-2"></i> Share App
                                </button>
                                <br>
                                <small class="text-muted">File size: ~15MB</small>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5">
                        <h4>Need Help?</h4>
                        <p>If you have any questions or need assistance, please contact our support team.</p>
                        <a href="contact.php" class="btn btn-outline-primary">
                            <i class="fas fa-headset me-2"></i> Contact Support
                        </a>
                    </div>
                </div>
                <div class="card-footer bg-light text-center py-3">
                    <p class="mb-0">
                        Already have the app? 
                        <a href="login.php" class="fw-bold">Sign In</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- APK Installation Instructions Modal -->
<div class="modal fade" id="appInstallInstructions" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">How to Install the APK</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6><i class="fas fa-qrcode me-2"></i>Option 1: Scan QR Code</h6>
                        <ol>
                            <li>Open your phone's camera app.</li>
                            <li>Point it at the QR code on this page.</li>
                            <li>Tap the link that appears on your screen.</li>
                            <li>The download will start automatically.</li>
                        </ol>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="fab fa-android me-2"></i>Option 2: Direct Download</h6>
                        <ol>
                            <li>Tap "Download APK" on this page.</li>
                            <li>Open the downloaded APK file.</li>
                            <li>If prompted, allow installation from unknown sources.</li>
                            <li>Tap "Install" and wait for completion.</li>
                            <li>Open the app and sign in with your credentials.</li>
                        </ol>
                    </div>
                </div>
                <div class="alert alert-info mt-3">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Tip:</strong> For easier installation, you can also scan this QR code from another device!
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Got it!</button>
            </div>
        </div>
    </div>
</div>

<script>
// APK download URL
const APK_URL = 'https://expo.dev/artifacts/eas/duDcCYCjPjBqJ6QZV4xCYr.apk';

// Generate QR Code when page loads
window.addEventListener('DOMContentLoaded', function() {
    // Generate QR Code
    const qrcode = new QRCode(document.getElementById("qrcode"), {
        text: APK_URL,
        width: 200,
        height: 200,
        colorDark: "#000000",
        colorLight: "#ffffff",
        correctLevel: QRCode.CorrectLevel.H
    });
    
    // Show APK installation instructions modal
    const appInstallModal = new bootstrap.Modal(document.getElementById('appInstallInstructions'));
    appInstallModal.show();
    
    // Track download analytics
    document.getElementById('downloadBtn').addEventListener('click', function(e) {
        // Log download attempt (you can send this to your analytics)
        console.log('APK download initiated');
        
        // Optional: Show download started message
        const btn = this;
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Starting Download...';
        btn.disabled = true;
        
        setTimeout(() => {
            btn.innerHTML = originalText;
            btn.disabled = false;
        }, 3000);
    });
    
    // Check if user is on mobile device
    if (isMobileDevice()) {
        console.log('Mobile device detected - showing mobile-specific instructions');
        // You can show mobile-specific instructions here
    }
});

// Function to detect mobile devices
function isMobileDevice() {
    return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
}

// Function to share the app (Web Share API)
async function shareApp() {
    if (navigator.share) {
        try {
            await navigator.share({
                title: 'Utility Billing System App',
                text: 'Download our mobile app to manage your utility bills!',
                url: APK_URL
            });
        } catch (err) {
            console.log('Error sharing:', err);
        }
    }
}
</script>

<?php include '../includes/footer.php'; ?>
