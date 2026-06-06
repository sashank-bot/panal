<?php
header('Content-Type: application/json');

$db_host = "://infinityfree.com"; 
$db_user = "if0_xxxxx";                 
$db_pass = "your_password";              
$db_name = "if0_xxxxx_kurodb";            

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Database offline"]));
}

$user_key = isset($_GET['key']) ? trim($_GET['key']) : '';

if (empty($user_key)) {
    die(json_encode(["status" => "error", "message" => "Empty key field"]));
}

// Check database for the key
$stmt = $conn->prepare("SELECT * FROM `keys` WHERE `license_key` = ?");
$stmt->bind_param("s", $user_key);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    
    // If the key is fresh/unused, change its status to 'used' upon first login
    if ($row['status'] == 'unused') {
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
