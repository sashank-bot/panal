<?php
// 1. DYNAMIC DATABASE CONFIGURATION FOR RAILWAY
// Falls back to InfinityFree values only if Railway environment variables are missing
$db_host = getenv('MYSQLHOST') ?: "sql101.infinityfree.com"; 
$db_port = getenv('MYSQLPORT') ?: "3306";
$db_user = getenv('MYSQLUSER') ?: "if0_42104418";                 
$db_pass = getenv('MYSQLPASSWORD') ?: "WCxVeJiTxhnnAx";              
$db_name = getenv('MYSQLDATABASE') ?: (getenv('MYSQL_DATABASE') ?: "if0_42104418_cyrus");            

// 2. CHOOSE YOUR DASHBOARD PASSWORD
$admin_password = "Blucyrus";

session_start();

// Connect using host and explicit port configuration needed by Railway
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);
if ($conn->connect_error) { 
    error_log("Database connection failure: " . $conn->connect_error);
    die("Connection failed. Please check backend server configuration logs."); 
}

// Handle Login
if (isset($_POST['login'])) {
    if ($_POST['password'] === $admin_password) { 
        $_SESSION['logged_in'] = true; 
    } else { 
        $error = "Incorrect password!"; 
    }
}
if (isset($_GET['logout'])) { 
    session_destroy(); 
    header("Location: index.php"); 
    exit(); 
}

// If not logged in, show the login form
if (!isset($_SESSION['logged_in'])) {
    echo '<form method="POST" style="margin:100px auto; width:300px; text-align:center; font-family:sans-serif;">
          <h2>Admin Login</h2>'.(isset($error)?"<p style='color:red'>".htmlspecialchars($error)."</p>":"").'
          <input type="password" name="password" placeholder="Password" required style="padding:8px; width:100%; margin-bottom:10px;"><br>
          <button type="submit" name="login" style="padding:8px 20px; cursor:pointer;">Login</button>
          </form>';
    exit();
}

// Handle Key Generation
if (isset($_POST['generate'])) {
    $new_key = "KEY-" . strtoupper(bin2hex(random_bytes(8)));
    $stmt = $conn->prepare("INSERT INTO `keys` (license_key, status) VALUES (?, 'unused')");
    $stmt->bind_param("s", $new_key);
    $stmt->execute();
    $stmt->close();
}

// Handle Key Deletion (Prepared Statement applied for SQL Injection Defense)
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM `keys` WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: index.php");
    exit();
}

// Fetch all keys to show in a table
$result = $conn->query("SELECT * FROM `keys` ORDER BY id DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Auth Panel Dashboard</title>
    <style>
        body { font-family: sans-serif; background: #f4f6f9; margin: 40px; color: #333; }
        .container { max-width: 800px; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); margin: auto; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #007bff; color: white; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .btn { padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; font-weight: bold; }
        .btn-gen { background: #28a745; color: white; }
        .btn-del { background: #dc3545; color: white; padding: 5px 10px; font-size: 12px; }
        .logout { float: right; color: #dc3545; }
    </style>
</head>
<body>
<div class="container">
    <a href="?logout=1" class="logout">Logout</a>
    <h2>License Key Management Panel</h2>
    
    <form method="POST">
        <button type="submit" name="generate" class="btn btn-gen">+ Generate New Key</button>
    </form>

    <table>
        <tr>
            <th>ID</th>
            <th>License Key</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
        <?php while($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?php echo htmlspecialchars($row['id']); ?></td>
            <td><strong><?php echo htmlspecialchars($row['license_key']); ?></strong></td>
            <td>
                <span style="color:<?php echo $row['status']=='used'?'red':'green'; ?>">
                    <?php echo htmlspecialchars(ucfirst($row['status'] ?? 'unused')); ?>
                </span>
            </td>
            <td>
                <a href="?delete=<?php echo htmlspecialchars($row['id']); ?>" class="btn btn-del" onclick="return confirm('Delete this key?')">Delete</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
</div>
</body>
</html>

