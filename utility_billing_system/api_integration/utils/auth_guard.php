<?php
// utils/auth_guard.php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/response.php';

function get_bearer_token() {
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    if (empty($headers['Authorization']) && !empty($headers['authorization'])) {
        $headers['Authorization'] = $headers['authorization'];
    }
    if (empty($headers['Authorization'])) return null;
    $auth = $headers['Authorization'];
    if (stripos($auth, 'Bearer ') === 0) {
        return trim(substr($auth, 7));
    }
    return null;
}

function require_reader_auth() {
    global $mysqli;
    $token = get_bearer_token();

    if (!$token) {
        sendResponse(false, 'Missing Authorization header', null, 401);
        exit;
    }

    $stmt = $mysqli->prepare('SELECT ReaderID, FirstName, LastName, Status FROM utilityreader WHERE AuthToken = ? LIMIT 1');
    if (!$stmt) {
        sendResponse(false, 'Auth prepare failed: ' . $mysqli->error, null, 500);
        exit;
    }

    $stmt->bind_param('s', $token);
    $stmt->execute();
    $result = $stmt->get_result();
    $reader = $result->fetch_assoc();
    $stmt->close();

    if (!$reader) {
        sendResponse(false, 'Invalid or expired token', null, 401);
        exit;
    }

    if ($reader['Status'] !== 'active') {
        sendResponse(false, 'Reader account is not active', null, 403);
        exit;
    }

    return $reader;
}
?>
