<?php
// utils/sms_helper.php
// Minimal SMS helper stub. Replace provider-specific code here.

function send_sms_via_provider($to, $message) {
    // For production, integrate with your SMS gateway (e.g., Twilio, Nexmo, local SMS provider).
    // This stub logs to a file and returns true for success.
    $logDir = __DIR__ . '/../../logs';
    if (!is_dir($logDir)) mkdir($logDir, 0755, true);
    $log = sprintf("[%s] SMS to %s: %s\n", date('Y-m-d H:i:s'), $to, $message);
    file_put_contents($logDir . '/sms.log', $log, FILE_APPEND);
    return true;
}
?>
