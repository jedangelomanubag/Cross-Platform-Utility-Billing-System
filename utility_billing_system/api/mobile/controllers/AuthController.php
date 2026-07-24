<?php
require_once __DIR__ . '/../config/database.php';

/**
 * Login for Utility Reader
 * Authenticates reader using username and password
 */
function loginReader($username, $password) {
    global $conn;

    $stmt = $conn->prepare("SELECT ReaderID, FirstName, LastName, Username, Email, ContactNumber, Area, Status 
                            FROM utilityreader 
                            WHERE Username = ? AND Status = 'active'");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $reader = $result->fetch_assoc();
        
        // Verify password
        $stmt2 = $conn->prepare("SELECT Password FROM utilityreader WHERE ReaderID = ?");
        $stmt2->bind_param("i", $reader['ReaderID']);
        $stmt2->execute();
        $passResult = $stmt2->get_result();
        $passData = $passResult->fetch_assoc();
        
        if (password_verify($password, $passData['Password'])) {
            // Generate auth token
            $token = bin2hex(random_bytes(32));
            
            // Update auth token in database
            $updateStmt = $conn->prepare("UPDATE utilityreader SET AuthToken = ? WHERE ReaderID = ?");
            $updateStmt->bind_param("si", $token, $reader['ReaderID']);
            $updateStmt->execute();
            
            $reader['auth_token'] = $token;
            
            echo json_encode([
                "success" => true, 
                "message" => "Login successful",
                "reader" => $reader
            ]);
        } else {
            echo json_encode(["success" => false, "message" => "Invalid password"]);
        }
        
        $stmt2->close();
    } else {
        echo json_encode(["success" => false, "message" => "Invalid username or account inactive"]);
    }

    $stmt->close();
}

/**
 * Login for Consumer
 * Authenticates consumer using email and password
 */
function loginConsumer($email, $password) {
    global $conn;

    $stmt = $conn->prepare("SELECT ConsumerID, FirstName, LastName, Email, ContactNumber, Address, Status 
                            FROM consumer 
                            WHERE Email = ? AND Status = 'active'");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $consumer = $result->fetch_assoc();
        
        // Verify password
        $stmt2 = $conn->prepare("SELECT Password FROM consumer WHERE ConsumerID = ?");
        $stmt2->bind_param("i", $consumer['ConsumerID']);
        $stmt2->execute();
        $passResult = $stmt2->get_result();
        $passData = $passResult->fetch_assoc();
        
        if (password_verify($password, $passData['Password'])) {
            echo json_encode([
                "success" => true, 
                "message" => "Login successful",
                "consumer" => $consumer
            ]);
        } else {
            echo json_encode(["success" => false, "message" => "Invalid password"]);
        }
        
        $stmt2->close();
    } else {
        echo json_encode(["success" => false, "message" => "Invalid email or account inactive"]);
    }

    $stmt->close();
}

/**
 * Verify Auth Token
 */
function verifyToken($token) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT ReaderID, FirstName, LastName, Username FROM utilityreader WHERE AuthToken = ? AND Status = 'active'");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return false;
}
?>
