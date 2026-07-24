<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdminLogin();

$page_title = 'Add New Meter';

$errors = [];
$meter = [
    'ConsumerID' => '',
    'Area' => '',
    'Classification' => '',
    'SerialNo' => '',
    'InstallationDate' => date('Y-m-d'),
    'Status' => 'active',
    'LastReading' => 0
];

/* -----------------------------------------------------
   GET ACTIVE CONSUMERS WITH PreviousReading FROM consumer TABLE
----------------------------------------------------- */
$consumers = [];

$query = "
    SELECT 
        ConsumerID,
        FirstName,
        LastName,
        Address,
        IFNULL(PreviousBillImage, 0) AS PreviousBillImage
    FROM consumer
    WHERE Status = 'active'
    ORDER BY LastName, FirstName
";

$result = mysqli_query($conn, $query);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $row['FullName'] = $row['FirstName'] . ' ' . $row['LastName'];
        $consumers[] = $row;
    }
}

/* -----------------------------------------------------
   BARANGAY AREA OPTIONS
----------------------------------------------------- */
$areas = [
    'Abuno', 'Acmac', 'Bagong Silang', 'Bunawan', 'Buru-un', 'Dalipuga', 'Del Carmen',
    'Digkilaan', 'Ditucalan', 'Hinaplanon', 'Hindang', 'Kabacsanan', 'Kalilangan', 'Kiwalan',
    'Lanipao', 'Luinab', 'Mahayahay', 'Mainit', 'Mandulog', 'Maria Cristina', 'Pala-o',
    'Poblacion', 'Rogongon', 'San Miguel', 'San Roque', 'Santa Elena', 'Santa Filomena',
    'Santiago', 'Saray', 'Suarez', 'Tambacan', 'Tibanga', 'Tipanoy', 'Tomas Cabili', 'Tominobo Proper',
    'Tominobo Upper', 'Tubod', 'Ubaldo Laya', 'Upper Hinaplanon', 'Villa Verde'
];
sort($areas);

/* -----------------------------------------------------
   PROCESS FORM SUBMISSION
----------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $meter['ConsumerID'] = $_POST['consumer_id'] ?? '';
    $meter['Area'] = trim($_POST['area'] ?? '');
    $meter['Classification'] = trim($_POST['classification'] ?? '');
    $meter['SerialNo'] = trim($_POST['serial_no'] ?? '');
    $meter['InstallationDate'] = $_POST['installation_date'] ?? date('Y-m-d');
    $meter['Status'] = $_POST['status'] === 'active' ? 'active' : 'inactive';
    $meter['LastReading'] = isset($_POST['previous_reading']) ? (float)$_POST['previous_reading'] : 0;

    // Validate required fields
    if (empty($meter['ConsumerID'])) $errors[] = 'Consumer is required';
    if (empty($meter['Classification'])) $errors[] = 'Classification is required';
    if (empty($meter['SerialNo'])) $errors[] = 'Serial number is required';

    if (empty($errors)) {
        mysqli_begin_transaction($conn);

        try {
            $stmt = mysqli_prepare($conn, "
                INSERT INTO meter 
                (ConsumerID, Area, Classification, SerialNo, InstallationDate, Status, LastReading, LastReadingDate)
                VALUES (?, ?, ?, ?, ?, ?, ?, NULL)
            ");

            mysqli_stmt_bind_param(
                $stmt, 
                'isssssd',
                $meter['ConsumerID'],
                $meter['Area'],
                $meter['Classification'],
                $meter['SerialNo'],
                $meter['InstallationDate'],
                $meter['Status'],
                $meter['LastReading']
            );

            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception('Failed to insert meter: ' . mysqli_error($conn));
            }

            mysqli_commit($conn);
            setFlashMessage('success', 'Meter added successfully');
            header('Location: meters.php');
            exit();

        } catch (Exception $e) {
            mysqli_rollback($conn);
            $errors[] = $e->getMessage();
        }
    }
}

include_once __DIR__ . '/../includes/header.php';
include_once __DIR__ . '/../includes/sidebar.php';
?>

<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<style>
/* Make all form inputs uniform height */
.form-control, .form-select {
    min-height: 38px !important;
    height: 38px !important;
    box-sizing: border-box !important;
    padding: 6px 12px !important;
    font-size: 1rem !important;
    line-height: 1.5 !important;
}

/* Override Select2 to match Bootstrap form control height exactly */
.select2-container--default {
    height: 38px !important;
}

.select2-container--default .select2-selection--single {
    height: 38px !important;
    border: 1px solid #ced4da !important;
    border-radius: 0.375rem !important;
    padding: 0 !important;
    display: flex !important;
    align-items: center !important;
}

.select2-container--default .select2-selection__rendered {
    line-height: 38px !important;
    padding-left: 12px !important;
    padding-right: 30px !important;
    height: 38px !important;
    font-size: 1rem !important;
}

.select2-container--default .select2-selection__arrow {
    height: 36px !important;
    top: 1px !important;
    right: 1px !important;
    width: 30px !important;
}

/* Ensure Select2 dropdown matches exactly */
.select2-container {
    width: 100% !important;
}

/* Image enlargement modal */
.image-modal {
    display: none;
    position: fixed;
    z-index: 9999;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.9);
    cursor: pointer;
}

.image-modal img {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    max-width: 90%;
    max-height: 90%;
    border: 2px solid white;
    border-radius: 8px;
    box-shadow: 0 0 20px rgba(0,0,0,0.5);
}

.image-modal .close-btn {
    position: absolute;
    top: 20px;
    right: 35px;
    color: #f1f1f1;
    font-size: 40px;
    font-weight: bold;
    cursor: pointer;
    z-index: 10000;
}

.image-modal .close-btn:hover {
    color: #bbb;
}

/* Make image clickable */
#previousBillImage {
    cursor: pointer;
    transition: transform 0.2s ease;
}

#previousBillImage:hover {
    transform: scale(1.02);
}
</style>

<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Add New Meter</h1>
        <a href="meters.php" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Back
        </a>
    </div>

    <?php if ($errors): ?>
        <div class="alert alert-danger">
            <ul><?php foreach ($errors as $error) echo "<li>" . htmlspecialchars($error) . "</li>"; ?></ul>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="POST">

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Serial Number *</label>
                        <input type="text" name="serial_no" class="form-control" required value="<?= htmlspecialchars($meter['SerialNo']) ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Classification *</label>
                        <select name="classification" class="form-select classification-select" required>
                            <option value="">Select classification</option>
                            <option value="Residential" <?= $meter['Classification'] == 'Residential' ? 'selected' : '' ?>>Residential</option>
                            <option value="Commercial" <?= $meter['Classification'] == 'Commercial' ? 'selected' : '' ?>>Commercial</option>
                            <option value="Industrial" <?= $meter['Classification'] == 'Industrial' ? 'selected' : '' ?>>Industrial</option>
                        </select>
                    </div>
                </div>

                <div class="row mb-3">

                    <!-- Consumer Dropdown -->
                    <div class="col-md-6">
                        <label class="form-label">Consumer *</label>
                        <select name="consumer_id" class="form-select consumer-select" id="consumerSelect" required>
                            <option value="">-- Select Consumer --</option>
                            <?php foreach ($consumers as $c): ?>
                                <option value="<?= $c['ConsumerID'] ?>" data-previous-bill="<?= $c['PreviousBillImage'] ?>" <?= $meter['ConsumerID'] == $c['ConsumerID'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($c['FullName'] . ' (' . $c['Address'] . ')') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Previous Reading -->
                    <div class="col-md-6">
                        <label class="form-label">Previous Reading</label>
                        <input type="number" step="0.01" name="previous_reading" class="form-control" value="<?= htmlspecialchars($meter['LastReading']) ?>">
                    </div>

                </div>

                <!-- Previous Bill Image Display -->
                <div class="row mb-3" id="previousBillImageRow">
                    <div class="col-12">
                        <label class="form-label">Previous Bill Image</label>
                        <div class="card">
                            <div class="card-body text-center">
                                <img id="previousBillImage" src="" alt="Previous Bill" style="max-width: 100%; max-height: 400px; border: 1px solid #ddd; border-radius: 4px;">
                                <div class="mt-2">
                                    <small class="text-muted" id="imageStatusText">No consumer selected</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Image Enlargement Modal -->
                <div id="imageModal" class="image-modal">
                    <span class="close-btn">&times;</span>
                    <img id="modalImage" src="" alt="Enlarged Previous Bill">
                </div>

                <div class="row mb-3">

                    <div class="col-md-6">
                        <label class="form-label">Area (Barangay)</label>
                        <select name="area" class="form-select area-select">
                            <option value="">-- Select Barangay --</option>
                            <?php foreach ($areas as $a): ?>
                                <option value="<?= htmlspecialchars($a) ?>" <?= $meter['Area'] == $a ? 'selected' : '' ?>><?= htmlspecialchars($a) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Installation Date *</label>
                        <input type="date" name="installation_date" class="form-control" required value="<?= htmlspecialchars($meter['InstallationDate']) ?>">
                    </div>

                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Status</label><br>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="status" value="active" <?= $meter['Status'] == 'active' ? 'checked' : '' ?>> Active
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="status" value="inactive" <?= $meter['Status'] == 'inactive' ? 'checked' : '' ?>> Inactive
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between">
                    <button type="button" id="testButton" class="btn btn-info btn-sm">
                        <i class="fas fa-bug me-1"></i> Debug Consumer Data
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Save Meter
                    </button>
                </div>

            </form>
        </div>
    </div>
</main>


<script>
$(document).ready(function() {
    // Wait a bit longer for Select2 to be fully loaded
    setTimeout(function() {
        // Check if Select2 is loaded
        if (typeof $.fn.select2 === 'undefined') {
            console.error('Select2 is not loaded!');
            // Try to load it manually
            $.getScript('https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js')
                .done(function() {
                    console.log('Select2 loaded manually, initializing...');
                    initializeSelect2();
                })
                .fail(function() {
                    console.error('Failed to load Select2 manually');
                });
        } else {
            console.log('Select2 is loaded, initializing...');
            initializeSelect2();
        }
    }, 100);
    
    function initializeSelect2() {
        // Initialize Select2 for all dropdowns
        $('.classification-select').select2({
            placeholder: "Select classification",
            allowClear: true
        });

        $('.area-select').select2({
            placeholder: "-- Select Barangay --",
            allowClear: true
        });

        $('.consumer-select').select2({
            placeholder: "-- Select Consumer --",
            allowClear: true
        });

        console.log('Select2 initialized successfully');

        // Function to show no image placeholder
        function showNoImage(message) {
            const imageElement = $('#previousBillImage');
            const statusText = $('#imageStatusText');
            
            imageElement.attr('src', 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjE1MCIgdmlld0JveD0iMCAwIDIwMCAxNTAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSIyMDAiIGhlaWdodD0iMTUwIiBmaWxsPSIjRjVGNUY1IiBzdHJva2U9IiNEREREREQiIHN0cm9rZS13aWR0aD0iMSIvPgo8c3ZnIHdpZHRoPSI2MCIgaGVpZ2h0PSI2MCIgdmlld0JveD0iMCAwIDI0IDI0IiBmaWxsPSJub25lIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHg9IjcwIiB5PSI0NSI+CjxwYXRoIGQ9Ik0yMSAxOVYxOEMyMSAxNi45IDIwLjEgMTYgMTkgMTZIOEM1LjkgMTYgNSAxNi45IDUgMThWMTlDNSAyMC4xIDUuOSAyMSA3IDIxSDE4QzIwLjEgMjEgMjEgMjAuMSAyMSAxOVoiIGZpbGw9IiM5OTk5OTkiLz4KPHBhdGggZD0iTTggMTJDOCAxMC45IDguOSAxMCAxMCAxMEgxNEMxNS4xIDEwIDE2IDEwLjkgMTYgMTJDMTYgMTMuMSAxNS4xIDE0IDE0IDE0SDEwQzguOSAxNCA4IDEzLjEgOCAxMloiIGZpbGw9IiM5OTk5OTkiLz4KPC9zdmc+Cjx0ZXh0IHg9IjEwMCIgeT0iMTIwIiBmb250LWZhbWlseT0iQXJpYWwiIGZvbnQtc2l6ZT0iMTQiIGZpbGw9IiM5OTk5OTkiIHRleHQtYW5jaG9yPSJtaWRkbGUiPk5vIEltYWdlIEF2YWlsYWJsZTwvdGV4dD4KPC9zdmc+');
            statusText.text(message);
        }

        // Function to update image based on consumer selection
        function updateConsumerImage(consumerId) {
            const imageElement = $('#previousBillImage');
            const statusText = $('#imageStatusText');

            console.log('=== UPDATE CONSUMER IMAGE ===');
            console.log('Consumer ID:', consumerId);

            if (!consumerId) {
                // No consumer selected
                console.log('No consumer selected - showing default');
                showNoImage('No consumer selected');
                return;
            }

            // Find the selected option to get the previous bill image
            const selectedOption = $('#consumerSelect option[value="' + consumerId + '"]');
            console.log('Selected option found:', selectedOption.length > 0 ? 'YES' : 'NO');
            
            if (selectedOption.length === 0) {
                console.log('ERROR: Selected option not found!');
                showNoImage('Consumer data not found');
                return;
            }

            const previousBillImage = selectedOption.data('previous-bill');
            console.log('Previous Bill Image (raw):', previousBillImage);
            console.log('Previous Bill Image (type):', typeof previousBillImage);
            console.log('Previous Bill Image (length):', previousBillImage ? previousBillImage.length : 'N/A');

            if (previousBillImage && previousBillImage !== '0' && previousBillImage !== '' && previousBillImage !== '0') {
                // Consumer has previous bill image
                // Check if the path already includes uploads/previous_bills
                let imagePath;
                if (previousBillImage.startsWith('uploads/previous_bills/')) {
                    imagePath = '../' + previousBillImage;
                } else {
                    imagePath = '../uploads/previous_bills/' + previousBillImage;
                }
                console.log('Image Path:', imagePath);
                
                // Show loading state
                statusText.text('Loading image...');
                
                imageElement.attr('src', imagePath);
                
                // Handle image loading
                imageElement.off('error').on('error', function() {
                    console.log('ERROR: Image failed to load from path:', imagePath);
                    showNoImage('Previous bill image not available');
                });
                
                // Handle successful image load
                imageElement.off('load').on('load', function() {
                    console.log('SUCCESS: Image loaded successfully');
                    statusText.text('Previous bill image for selected consumer');
                });
            } else {
                // Consumer selected but no previous bill image
                console.log('No previous bill image for this consumer');
                showNoImage('No previous bill image for this consumer');
            }
            console.log('=== END UPDATE CONSUMER IMAGE ===');
        }

        // Handle consumer selection change (both Select2 and native change)
        $('#consumerSelect').on('change', function() {
            updateConsumerImage($(this).val());
        });

        // Handle Select2 select event for immediate response
        $('.consumer-select').on('select2:select', function(e) {
            console.log('Select2 select event triggered');
            console.log('Selected data:', e.params.data);
            updateConsumerImage(e.params.data.id);
        });

        // Handle Select2 clear event
        $('.consumer-select').on('select2:clear', function() {
            console.log('Select2 clear event triggered');
            showNoImage('No consumer selected');
        });

        // Also add a test button to check data
        $('#testButton').on('click', function() {
            console.log('=== DEBUG CONSUMER DATA ===');
            console.log('Total consumers found:', $('#consumerSelect option').length - 1); // -1 for the placeholder
            
            $('#consumerSelect option').each(function(index) {
                const option = $(this);
                if (option.val() !== '') { // Skip placeholder
                    console.log('Consumer #' + index + ':');
                    console.log('  Value:', option.val());
                    console.log('  Text:', option.text().trim());
                    console.log('  Data-previous-bill:', option.data('previous-bill'));
                    console.log('  Data attributes all:', option.data());
                    console.log('  ---');
                }
            });
            
            // Check current selection
            const currentVal = $('#consumerSelect').val();
            console.log('Currently selected:', currentVal);
            if (currentVal) {
                const currentOption = $('#consumerSelect option[value="' + currentVal + '"]');
                console.log('Current option data:', currentOption.data());
            }
            console.log('=== END DEBUG CONSUMER DATA ===');
        });

        // Initialize on page load
        updateConsumerImage($('#consumerSelect').val());
    }
    
    // Image enlargement functionality (outside initializeSelect2)
    $('#previousBillImage').on('click', function() {
        const imgSrc = $(this).attr('src');
        console.log('Image clicked, src:', imgSrc);
        if (imgSrc && imgSrc !== '' && !imgSrc.includes('data:image/svg+xml')) {
            console.log('Opening modal with image:', imgSrc);
            $('#modalImage').attr('src', imgSrc);
            $('#imageModal').css('display', 'block');
        } else {
            console.log('Not opening modal - no valid image');
        }
    });
    
    // Close modal when clicking the close button
    $('.close-btn').on('click', function(e) {
        e.stopPropagation();
        console.log('Close button clicked');
        $('#imageModal').css('display', 'none');
    });
    
    // Close modal when clicking outside the image
    $('#imageModal').on('click', function(e) {
        if (e.target === this) {
            console.log('Modal background clicked');
            $('#imageModal').css('display', 'none');
        }
    });
    
    // Close modal with Escape key
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape') {
            console.log('Escape key pressed');
            $('#imageModal').css('display', 'none');
        }
    });
});
</script>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
