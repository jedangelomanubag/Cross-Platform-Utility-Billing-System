<?php
// auth/logout.php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils/response.php';

$headers = function_exists('getallheaders') ? getallheaders() : [];
if (empty($headers['Authorization']) && !empty($headers['authorization'])) {
    $headers['Authorization'] = $headers['authorization'];
}
if (empty($headers['Authorization'])) {
    res_error('Missing Authorization header', 401);
}
$auth = $headers['Authorization'];
if (stripos($auth, 'Bearer ') === 0) {
    $token = trim(substr($auth, 7));
} else {
    res_error('Invalid Authorization header', 401);
}

// Clear token for utilityreader
$stmt = $mysqli->prepare('UPDATE utilityreader SET AuthToken = NULL WHERE AuthToken = ?');
if (!$stmt) res_error('Prepare failed: ' . $mysqli->error, 500);
$stmt->bind_param('s', $token);
$stmt->execute();
$affected = $stmt->affected_rows;
$stmt->close();

if ($affected > 0) {
    res_ok([], 'Logged out');
} else {
    res_error('Invalid token or already logged out', 400);
}
?>
