<?php
header('Content-Type: application/json');

// 1. DYNAMIC DATABASE CONFIGURATION FOR RAILWAY
$db_host = getenv('MYSQLHOST') ?: "sql101.infinityfree.com"; 
$db_port = getenv('MYSQLPORT') ?: "3306";
$db_user = getenv('MYSQLUSER') ?: "if0_xxxxx";                 
$db_pass = getenv('MYSQLPASSWORD') ?: "your_password";              
$db_name = getenv('MYSQLDATABASE') ?: (getenv('MYSQL_DATABASE') ?: "if0_xxxxx_kurodb");            

// Establish database connection with port routing
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);
if ($conn->connect_error) {
    // Log the actual internal error safely to Railway logs, do not send it to the public client
    error_log("Database offline error: " . $conn->connect_error);
    echo json_encode(["status" => "error", "message" => "Authentication server unavailable"]);
    exit();
}

// 2. INPUT VALIDATION
$user_key = isset($_GET['key']) ? trim($_GET['key']) : '';

if (empty($user_key)) {
    echo json_encode(["status" => "error", "message" => "Empty key field"]);
    $conn->close();
    exit();
}

// 3. SECURE KEY VERIFICATION
$stmt = $conn->prepare("SELECT * FROM `keys` WHERE `license_key` = ?");
$stmt->bind_param("s", $user_key);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    
    // If the key is fresh/unused, change its status to 'used' upon first login
    if (isset($row['status']) && $row['status'] === 'unused') {
        $update = $conn->prepare("UPDATE `keys` SET `status` = 'used' WHERE `license_key` = ?");
        $update->bind_param("s", $user_key);
        $update->execute();
        $update->close();
    }
    
    echo json_encode(["status" => "success", "message" => "Access Granted"]);
} else {
    echo json_encode(["status" => "failed", "message" => "Invalid license key"]);
}

$stmt->close();
$conn->close();
?>
