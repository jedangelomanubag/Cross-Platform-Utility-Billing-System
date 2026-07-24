<?php
require_once '../config/db.php';

$billingID = $_GET['id'] ?? 0;

$stmt = $conn->prepare("SELECT bs.*, c.FirstName, c.LastName, c.Address, m.SerialNo AS MeterNumber 
                        FROM billingstatement bs
                        JOIN meter m ON bs.MeterID = m.MeterID
                        JOIN consumer c ON m.ConsumerID = c.ConsumerID
                        WHERE bs.BillingID = ?");
$stmt->bind_param("i", $billingID);
$stmt->execute();
$result = $stmt->get_result();
$b = $result->fetch_assoc();

// Helper function for safe array access
function safe($array, $key, $default = '') {
    return isset($array[$key]) ? $array[$key] : $default;
}
?>

<div id="billingDetails" style="
    font-family: Arial, sans-serif; 
    max-width: 700px; 
    margin: auto; 
    border: 1px solid #ccc; 
    padding: 20px;
    background: #fff;
">
    <h3 class="text-center mb-3">💧 Water Bill Summary</h3>
    <hr>
    <p><strong>Name:</strong> <?= htmlspecialchars(safe($b, 'LastName').', '.safe($b, 'FirstName')); ?></p>
    <p><strong>Address:</strong> <?= htmlspecialchars(safe($b, 'Address')); ?></p>
    <p><strong>Serial No.:</strong> <?= htmlspecialchars(safe($b, 'MeterNumber')); ?></p>
    <p><strong>Billing Period:</strong> <?= htmlspecialchars(safe($b, 'BillingPeriod')); ?></p>

    <h5>📅 Meter Reading</h5>
    <table class="table table-sm table-bordered">
        <thead>
            <tr>
                <th>Description</th>
                <th>Date</th>
                <th>Reading</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Previous Reading</td>
                <td><?= safe($b, 'PreviousReadingDate') ? date('m/d/Y', strtotime($b['PreviousReadingDate'])) : '-'; ?></td>
                <td><?= number_format(safe($b, 'PreviousReading', 0), 2); ?></td>
            </tr>
            <tr>
                <td>Current Reading</td>
                <td><?= safe($b, 'BillingDate') ? date('m/d/Y', strtotime($b['BillingDate'])) : '-'; ?></td>
                <td><?= number_format(safe($b, 'CurrentReading', 0), 2); ?></td>
            </tr>
            <tr>
                <td>Consumption (cu. m.)</td>
                <td colspan="2"><?= number_format(safe($b, 'Consumption', 0), 2); ?> cu. m.</td>
            </tr>
        </tbody>
    </table>

    <h5>💰 Billing Details</h5>
    <table class="table table-sm table-bordered">
        <tbody>
            <tr><td>Current Charges</td><td>₱<?= number_format(safe($b, 'CurrentCharges', 0), 2); ?></td></tr>
            <tr><td>Credits</td><td>₱<?= number_format(safe($b, 'Credits', 0), 2); ?></td></tr>
            <tr><td>Previous Bill Not Yet Paid</td><td>₱<?= number_format(safe($b, 'PreviousBalance', 0), 2); ?></td></tr>
            <tr class="fw-bold"><td>Total Bill This Billing Period</td><td>₱<?= number_format(safe($b, 'TotalAmount', 0), 2); ?></td></tr>
            <tr><td>Surcharge (<?= safe($b, 'SurchargeRate', 0) ?>%)</td><td>₱<?= number_format(safe($b, 'SurchargeAmount', 0), 2); ?></td></tr>
            <tr class="fw-bold"><td>Total Billing with Arrears</td><td>₱<?= number_format(safe($b, 'TotalWithSurcharge', 0), 2); ?></td></tr>
        </tbody>
    </table>

    <p><strong>Discount:</strong> <?= safe($b, 'Discount', 0) ?>% if paid on or before due date</p>
    <p><strong>Due Date for Surcharge:</strong> <?= safe($b, 'DueDate') ? date('m/d/Y', strtotime($b['DueDate'])) : '-'; ?></p>
    <p><em>Note: “Not valid as an official receipt” — payment must be made at the City Treasurer’s Office, Iligan City.</em></p>
</div>

<!-- Modal Styles -->
<style>
.modal-dialog {
    display: flex;
    align-items: center;  /* vertical center */
    min-height: calc(100% - 1rem);
}

.modal-content {
    max-height: 90vh; /* prevent modal from exceeding screen height */
    overflow-y: auto; /* scroll inside modal if content is too tall */
}

@media print {
  body * {
    visibility: hidden !important;
  }
  #viewBillingModal, #viewBillingModal * {
    visibility: visible !important;
  }
  #viewBillingModal {
    position: absolute;
    left: 0;
    top: 0;
    width: 100%;
    background: white;
  }

  /* Hide modal header buttons */
  .modal-header, .btn-close {
    display: none !important;
  }

  /* Optional: clean spacing */
  .modal-content {
    border: none !important;
    box-shadow: none !important;
  }
}

</style>
