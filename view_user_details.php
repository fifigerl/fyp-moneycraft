<?php
// Start the session
session_start();
require_once 'config.php';

// Ensure admin is logged in
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    header("location: admin.php");
    exit;
}

// Validate user ID
if (!isset($_GET['user_id']) || !is_numeric($_GET['user_id'])) {
    die("Invalid user ID.");
}

$user_id = (int)$_GET['user_id'];

// Fetch non-sensitive user details
$sql = "SELECT UserID, Username, UserEmail, createdAt, status FROM Users WHERE UserID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("User not found.");
}

$user = $result->fetch_assoc();

// Fetch user activity logs
$sql_activities = "SELECT Action, Timestamp, Type FROM UserActivities WHERE UserID = ? ORDER BY Timestamp DESC LIMIT 10";
$stmt = $conn->prepare($sql_activities);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$activities = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Details</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
        }
        .container {
            max-width: 800px;
            margin: 20px auto;
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        h1 {
            font-size: 1.5rem;
            color: #333;
            margin-bottom: 20px;
        }
        .user-detail, .activity-log {
            margin-bottom: 20px;
        }
        .activity-log-item {
            margin-bottom: 10px;
        }
        .activity-log-item span {
            font-size: 0.9rem;
            color: #555;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>User Details</h1>
    <div class="user-detail">
        <p><strong>Username:</strong> <?php echo htmlspecialchars($user['Username']); ?></p>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($user['UserEmail']); ?></p>
        <p><strong>Join Date:</strong> <?php echo htmlspecialchars($user['createdAt']); ?></p>
        <p><strong>Status:</strong> <?php echo htmlspecialchars($user['status']); ?></p>
    </div>
    <h2>Recent Activities</h2>
    <div class="activity-log">
        <?php if (!empty($activities)): ?>
            <?php foreach ($activities as $activity): ?>
                <div class="activity-log-item">
                    <p><strong>Action:</strong> <?php echo htmlspecialchars($activity['Action']); ?></p>
                    <span><?php echo htmlspecialchars($activity['Timestamp']); ?></span>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No recent activities found.</p>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
