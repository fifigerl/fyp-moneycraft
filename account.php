<?php
include 'config.php';
include 'navbar.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    $userId = $_POST['user_id'] ?? null;
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

    if ($action === 'create') {
        $hashedPwd = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (Username, UserEmail, UserPwd, createdAt, ProfilePicture) VALUES ('$username', '$email', '$hashedPwd', CURDATE(), '$targetFile')";
        $conn->query($sql);
    } elseif ($action === 'update' && $userId) {
        $hashedPwd = password_hash($password, PASSWORD_DEFAULT);
        $sql = "UPDATE users SET Username='$username', UserEmail='$email', UserPwd='$hashedPwd'";
        
        if ($targetFile) {
            $sql .= ", ProfilePicture='$targetFile'";
        }
        
        $sql .= " WHERE UserID=$userId";
        $conn->query($sql);
    } elseif ($action === 'delete' && $userId) {
        $sql = "DELETE FROM users WHERE UserID=$userId";
        $conn->query($sql);
    }
}

// Fetch user data
$userId = 1; // Replace with dynamic user ID
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
            <img src="<?php echo $user['ProfilePicture']; ?>" alt="Profile Picture">
        </div>
        <form method="POST" action="account.php" enctype="multipart/form-data">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="user_id" value="<?php echo $user['UserID']; ?>">
            <label for="username">Name</label>
            <input type="text" name="username" value="<?php echo $user['Username']; ?>" required>
            <label for="email">Email</label>
            <input type="email" name="email" value="<?php echo $user['UserEmail']; ?>" required>
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
            <input type="hidden" name="user_id" value="<?php echo $user['UserID']; ?>">
            <div class="button-group">
                <button type="submit">Delete Account</button>
            </div>
        </form>
    </div>
</body>
</html>