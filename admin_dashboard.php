<?php
// Start the session
session_start();

// Check if the admin is logged in, if not then redirect to login page
if (!isset($_SESSION["admin_loggedin"]) || $_SESSION["admin_loggedin"] !== true) {
    header("location: admin.php");
    exit;
}

// Include config file
require_once 'config.php';

// Fetch some data if needed, for example, a list of users or other admin-related data
// $result = $conn->query("SELECT * FROM users");

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include 'admin_navbar.php'; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
</head>
<body>
    <div class="dashboard-container">
        <h1>Welcome, <?php echo htmlspecialchars($_SESSION["admin_username"]); ?>!</h1>
        <p>This is your admin dashboard.</p>
        
        <!-- Example of admin functionalities -->
        <div class="admin-actions">
            <h2>Admin Actions</h2>
            <ul>
                <li><a href="manage_users.php">Manage Users</a></li>
                <li><a href="view_reports.php">View Reports</a></li>
                <li><a href="settings.php">Settings</a></li>
            </ul>
        </div>

        <!-- Logout button -->
        <form action="logout.php" method="post">
            <button type="submit" class="logout-btn">Log Out</button>
        </form>
    </div>
</body>
</html>
