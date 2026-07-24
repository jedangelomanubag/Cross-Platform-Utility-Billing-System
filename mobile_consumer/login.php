<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");

require_once "db.php";

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['email']) || !isset($data['password'])) {
    echo json_encode(["success" => false, "message" => "Missing email or password"]);
    exit;
}

$email = trim($data['email']);
$password = trim($data['password']);

$stmt = $conn->prepare("SELECT * FROM consumer WHERE Email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();

    // Handle both hashed and plain passwords
    $isPasswordValid = false;
    if (strpos($user['Password'], '$2y$') === 0) {
        // hashed password
        $isPasswordValid = password_verify($password, $user['Password']);
    } else {
        // plain text password
        $isPasswordValid = ($password === $user['Password']);
    }

    if ($isPasswordValid) {
        echo json_encode([
            "success" => true,
            "message" => "Login successful",
            "data" => [
                "consumer_id" => $user['ConsumerID'],
                "name" => $user['FirstName'] . ' ' . $user['LastName'],
                "email" => $user['Email']
            ]
        ]);
    } else {
        echo json_encode(["success" => false, "message" => "Invalid password"]);
    }
} else {
    echo json_encode(["success" => false, "message" => "User not found"]);
}

$stmt->close();
$conn->close();
