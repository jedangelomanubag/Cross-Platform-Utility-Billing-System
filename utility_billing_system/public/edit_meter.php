<?php
require_once '../config/db.php';
require_once '../functions/admin_functions.php';

// Ensure admin is logged in
requireAdminLogin();

// Set page title
$page_title = 'Edit Meter';

// Initialize variables
$errors = [];
$meter = [
    'MeterID' => '',
    'MeterNumber' => '',
    'ConsumerID' => null,
    'ReaderID' => null,
    'Area' => '',
    'InstallationDate' => date('Y-m-d'),
    'LastReading' => 0.00,
    'LastReadingDate' => null,
    'Status' => 'active'
];

// Get meter ID from URL
$meterId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$meterId) {
    header('Location: meters.php');
    exit;
}

// Fetch meter data
$query = "SELECT * FROM meter WHERE MeterID = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $meterId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: meters.php');
    exit;
}

$meter = $result->fetch_assoc();

// Get all active consumers
$consumers = [];
$consumerQuery = "SELECT ConsumerID, FirstName, LastName FROM consumer WHERE Status = 'active' ORDER BY LastName, FirstName";
$consumerResult = $conn->query($consumerQuery);
if ($consumerResult) {
    while ($row = $consumerResult->fetch_assoc()) {
        $row['FullName'] = $row['FirstName'] . ' ' . $row['LastName'];
        $consumers[] = $row;
    }
}

// Get all active readers
$readers = [];
$readerQuery = "SELECT ReaderID, FirstName, LastName, Area FROM utilityreader WHERE Status = 'active' ORDER BY LastName, FirstName";
$readerResult = $conn->query($readerQuery);
if ($readerResult) {
    while ($row = $readerResult->fetch_assoc()) {
        $row['FullName'] = $row['FirstName'] . ' ' . $row['LastName'];
        $readers[] = $row;
    }
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate input
    $meter['MeterNumber'] = trim($_POST['meter_number']);
    $meter['ConsumerID'] = !empty($_POST['consumer_id']) ? intval($_POST['consumer_id']) : null;
    $meter['ReaderID'] = !empty($_POST['reader_id']) ? intval($_POST['reader_id']) : null;
    $meter['Area'] = trim($_POST['area']);
    $meter['Status'] = isset($_POST['status']) && $_POST['status'] === 'active' ? 'active' : 'inactive';
    $meter['InstallationDate'] = $_POST['installation_date'];
    $meter['LastReading'] = floatval($_POST['last_reading']);
    $meter['LastReadingDate'] = !empty($_POST['last_reading_date']) ? $_POST['last_reading_date'] : null;

    // Validate required fields
    if (empty($meter['MeterNumber'])) {
        $errors[] = 'Meter number is required';
    }

    if (empty($errors)) {
        // Update meter in database
        $query = "UPDATE meter SET 
                 MeterNumber = ?,
                 ConsumerID = ?,
                 ReaderID = ?,
                 Area = ?,
                 Status = ?,
                 InstallationDate = ?,
                 LastReading = ?,
                 LastReadingDate = ?
                 WHERE MeterID = ?";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param('siissdsi',
            $meter['MeterNumber'],
            $meter['ConsumerID'],
            $meter['ReaderID'],
            $meter['Area'],
            $meter['InstallationDate'],
            $meter['LastReading'],
            $meter['LastReadingDate'],
            $meterId
        );
        
        if ($stmt->execute()) {
            // Log the action
            logAdminAction($_SESSION['admin_id'], "Updated meter: {$meter['MeterNumber']} (ID: $meterId)");
            
            // Redirect to meters page with success message
            $_SESSION['success_message'] = 'Meter updated successfully';
            header('Location: meters.php');
            exit;
        } else {
            $errors[] = 'Failed to update meter: ' . $conn->error;
        }
    }
}

// Include header and sidebar
include '../includes/header.php';
include '../includes/sidebar.php';
?>

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Edit Meter</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="meters.php" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Back to Meters
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
                        <label for="meter_number" class="form-label">Meter Number *</label>
                        <input type="text" class="form-control" id="meter_number" name="meter_number" 
                               value="<?php echo htmlspecialchars($meter['MeterNumber']); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="consumer_id" class="form-label">Consumer</label>
                        <select class="form-select" id="consumer_id" name="consumer_id">
                            <option value="">-- Select Consumer --</option>
                            <?php foreach ($consumers as $consumer): ?>
                                <option value="<?php echo $consumer['ConsumerID']; ?>"
                                    <?php echo ($meter['ConsumerID'] == $consumer['ConsumerID']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($consumer['FullName']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="reader_id" class="form-label">Assigned Reader</label>
                        <select class="form-select" id="reader_id" name="reader_id">
                            <option value="">-- Select Reader --</option>
                            <?php foreach ($readers as $reader): ?>
                                <option value="<?php echo $reader['ReaderID']; ?>"
                                    <?php echo ($meter['ReaderID'] == $reader['ReaderID']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($reader['FullName'] . ' (' . $reader['Area'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="area" class="form-label">Area</label>
                        <input type="text" class="form-control" id="area" name="area" 
                               value="<?php echo htmlspecialchars($meter['Area']); ?>">
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="installation_date" class="form-label">Installation Date</label>
                        <input type="date" class="form-control" id="installation_date" 
                               name="installation_date" value="<?php echo $meter['InstallationDate']; ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="last_reading" class="form-label">Last Reading</label>
                        <input type="number" step="0.01" class="form-control" id="last_reading" 
                               name="last_reading" value="<?php echo htmlspecialchars($meter['LastReading']); ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="last_reading_date" class="form-label">Last Reading Date</label>
                        <input type="date" class="form-control" id="last_reading_date" 
                               name="last_reading_date" value="<?php echo $meter['LastReadingDate']; ?>">
                    </div>
                </div>

                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="status" name="status" 
                               value="active" <?php echo $meter['Status'] === 'active' ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="status">Active</label>
                    </div>
                </div>

                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <a href="meters.php" class="btn btn-secondary me-md-2">Cancel</a>
                    <button type="submit" class="btn btn-primary">Update Meter</button>
                </div>
            </form>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>
