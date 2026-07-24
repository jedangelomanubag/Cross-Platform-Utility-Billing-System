<?php
// auth/login.php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils/response.php';

$input = json_input();
$username = isset($input['username']) ? $input['username'] : '';
$password = isset($input['password']) ? $input['password'] : '';

if (!$username || !$password) {
    res_error('username and password required', 400);
}

// Try utilityreader (username)
$stmt = $mysqli->prepare('SELECT ReaderID, Password, AuthToken FROM utilityreader WHERE Username = ? LIMIT 1');
if (!$stmt) res_error('Prepare failed: ' . $mysqli->error, 500);
$stmt->bind_param('s', $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if ($user && password_verify($password, $user['Password'])) {
    // ensure there is an AuthToken
    if (empty($user['AuthToken'])) {
        $token = bin2hex(random_bytes(32));
        $u_stmt = $mysqli->prepare('UPDATE utilityreader SET AuthToken = ? WHERE ReaderID = ?');
        $u_stmt->bind_param('si', $token, $user['ReaderID']);
        $u_stmt->execute();
        $u_stmt->close();
    } else {
        $token = $user['AuthToken'];
    }
    res_ok(['token' => $token, 'role' => 'reader'], 'Login successful');
}

// Try admin (email)
$stmt = $mysqli->prepare('SELECT AdminID, Password FROM admin WHERE Email = ? LIMIT 1');
if (!$stmt) res_error('Prepare failed: ' . $mysqli->error, 500);
$stmt->bind_param('s', $username);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();
$stmt->close();

if ($admin && password_verify($password, $admin['Password'])) {
    res_ok(['admin_id' => $admin['AdminID'], 'role' => 'admin'], 'Admin login successful');
}

res_error('Invalid credentials', 401);
?>
