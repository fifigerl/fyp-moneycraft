<?php
// Start the session
session_start();

include 'navbar.php';

// Include config file
require_once 'config.php';

// Include the logging function
function logUserActivity($conn, $userId, $username, $action, $type = null) {
    $stmt = $conn->prepare("INSERT INTO UserActivities (UserID, Username, Action, Type) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $userId, $username, $action, $type);
    $stmt->execute();
    $stmt->close();
}

// Check if the user is logged in, if not redirect to login page
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    $userId = $_SESSION['id']; // Use session user ID
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $profilePicture = $_FILES['profile_picture']['name'] ?? null;
    $targetFile = null;

    if ($profilePicture) {
        $targetDir = "uploads/";
        $targetFile = $targetDir . basename($profilePicture);
        move_uploaded_file($_FILES['profile_picture']['tmp_name'], $targetFile);
    }

    if ($action === 'update') {
        $hashedPwd = password_hash($password, PASSWORD_DEFAULT);
        $sql = "UPDATE users SET Username='$username', UserEmail='$email', UserPwd='$hashedPwd'";
        
        if ($targetFile) {
            $sql .= ", ProfilePicture='$targetFile'";
        }
        
        $sql .= " WHERE UserID=$userId";
        $conn->query($sql);

        // Log the action of updating account
        logUserActivity($conn, $userId, $username, "Updated account", "Account");
    } elseif ($action === 'delete') {
        $sql = "DELETE FROM users WHERE UserID=$userId";
        $conn->query($sql);

        // Log the action of deleting account
        logUserActivity($conn, $userId, $username, "Deleted account", "Account");

        // Log the user out after account deletion
        session_destroy();
        header("location: login.php");
        exit;
    }
}

// Fetch user data
$userId = $_SESSION['id']; // Use session user ID
$user = $conn->query("SELECT * FROM users WHERE UserID = $userId")->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Account Management</title>
    <link rel="stylesheet" href="css/navbar.css">
    <link rel="stylesheet" href="../styles.css">
</head>
<body>
    <div class="account-container">
        <h1>Your Account</h1>
        <div class="profile-picture">
            <img src="<?php echo htmlspecialchars($user['ProfilePicture']); ?>" alt="Profile Picture">
        </div>
        <form method="POST" action="account.php" enctype="multipart/form-data">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user['UserID']); ?>">
            <label for="username">Name</label>
            <input type="text" name="username" value="<?php echo htmlspecialchars($user['Username']); ?>" required>
            <label for="email">Email</label>
            <input type="email" name="email" value="<?php echo htmlspecialchars($user['UserEmail']); ?>" required>
            <label for="password">Password</label>
            <input type="password" name="password" required>
            <label for="profile_picture">Profile Picture</label>
            <input type="file" name="profile_picture">
            <div class="button-group">
                <button type="submit">Update Account</button>
            </div>
        </form>
        <form method="POST" action="account.php" onsubmit="return confirm('Are you sure you want to delete your account?');">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user['UserID']); ?>">
            <div class="button-group">
                <button type="submit">Delete Account</button>
            </div>
        </form>
    </div>
</body>
</html>