<?php
// personnel/send_sms.php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../utils/auth_guard.php';
require_once __DIR__ . '/../utils/sms_helper.php';

$reader = require_reader_auth();
$input = json_input();

$billingId = isset($input['BillingID']) ? (int)$input['BillingID'] : null;
$toNumber = isset($input['To']) ? $input['To'] : null;
$message = isset($input['Message']) ? $input['Message'] : null;

if (!$billingId || !$toNumber || !$message) res_error('BillingID, To, and Message required', 400);

$ok = send_sms_via_provider($toNumber, $message);
if ($ok) {
    res_ok([], 'SMS queued/sent');
} else {
    res_error('SMS sending failed', 500);
}
?>
