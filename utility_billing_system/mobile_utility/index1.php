<?php
header("Content-Type: application/json");
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/MeterController.php';
require_once __DIR__ . '/controllers/ConsumerController.php';

$method = $_SERVER['REQUEST_METHOD'];

// Handle POST requests
if ($method === "POST") {
    $data = json_decode(file_get_contents("php://input"), true);
    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'login_reader':
            if (isset($data['username']) && isset($data['password'])) {
                loginReader($data['username'], $data['password']);
            } else {
                echo json_encode(["success" => false, "message" => "Username and password required"]);
            }
            break;

        case 'login_consumer':
            if (isset($data['email']) && isset($data['password'])) {
                loginConsumer($data['email'], $data['password']);
            } else {
                echo json_encode(["success" => false, "message" => "Email and password required"]);
            }
            break;

        case 'submit_reading':
            if (isset($data['meter_id']) && isset($data['reader_id']) && isset($data['current_reading'])) {
                $notes = $data['notes'] ?? '';
                submitReading($data['meter_id'], $data['reader_id'], $data['current_reading'], $notes);
            } else {
                echo json_encode(["success" => false, "message" => "meter_id, reader_id, and current_reading required"]);
            }
            break;

        default:
            echo json_encode(["success" => false, "message" => "Invalid POST action"]);
    }
}

// Handle GET requests
if ($method === "GET") {
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'meters':
            $reader_id = $_GET['reader_id'] ?? null;
            getMeters($reader_id);
            break;

        case 'meter_details':
            if (isset($_GET['meter_id'])) {
                getMeterDetails($_GET['meter_id']);
            } else {
                echo json_encode(["success" => false, "message" => "meter_id required"]);
            }
            break;

        case 'reading_history':
            if (isset($_GET['meter_id'])) {
                $limit = $_GET['limit'] ?? 10;
                getReadingHistory($_GET['meter_id'], $limit);
            } else {
                echo json_encode(["success" => false, "message" => "meter_id required"]);
            }
            break;

        case 'consumer':
            if (isset($_GET['consumer_id'])) {
                getConsumerData($_GET['consumer_id']);
            } else {
                echo json_encode(["success" => false, "message" => "consumer_id required"]);
            }
            break;

        case 'consumer_meters':
            if (isset($_GET['consumer_id'])) {
                getConsumerMeters($_GET['consumer_id']);
            } else {
                echo json_encode(["success" => false, "message" => "consumer_id required"]);
            }
            break;

        case 'consumer_bills':
            if (isset($_GET['consumer_id'])) {
                $limit = $_GET['limit'] ?? 12;
                getConsumerBills($_GET['consumer_id'], $limit);
            } else {
                echo json_encode(["success" => false, "message" => "consumer_id required"]);
            }
            break;

        case 'consumer_stats':
            if (isset($_GET['consumer_id'])) {
                getConsumerStats($_GET['consumer_id']);
            } else {
                echo json_encode(["success" => false, "message" => "consumer_id required"]);
            }
            break;

        default:
            echo json_encode(["success" => false, "message" => "Invalid GET action"]);
    }
}

// Handle unsupported methods
if (!in_array($method, ['GET', 'POST', 'OPTIONS'])) {
    echo json_encode(["success" => false, "message" => "Method not allowed"]);
}
?>
