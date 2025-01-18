<?php
session_start();

include 'navbar.php';
require_once 'config.php';

// Include the logging function
function logUserActivity($conn, $userId, $username, $action, $type = null) {
    $stmt = $conn->prepare("INSERT INTO UserActivities (UserID, Username, Action, Type) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $userId, $username, $action, $type);
    $stmt->execute();
    $stmt->close();
}

// Check if the user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    $userId = $_SESSION['id'];
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
$userId = $_SESSION['id'];
$user = $conn->query("SELECT * FROM users WHERE UserID = $userId")->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #F8F9FA;
            font-family: 'Inter', sans-serif;
            color: #161925;
        }

        .account-container {
            max-width: 800px;
            margin: 40px auto;
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .profile-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .profile-picture-container {
            position: relative;
            width: 100px;
            height: 100px;
            border-radius: 50%;
            overflow: hidden;
        }

        .profile-picture-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-picture-container:hover .edit-icon {
            opacity: 1;
        }

        .edit-icon {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            color: #fff;
            font-size: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s;
            cursor: pointer;
        }

        h1 {
            font-weight: 700;
            color: rgb(0, 35, 72);
        }

        .form-group label {
            font-weight: 600;
            margin-bottom: 5px;
        }

        .form-control {
            margin-bottom: 15px;
            border-radius: 5px;
        }

        .update-button {
            background-color: #FFD000;
            color: #161925;
            font-weight: bold;
            border: none;
            padding: 10px 20px;
            border-radius: 10px;
            cursor: pointer;
        }

        .update-button:hover {
            background-color: #FDF09D;
        }

        .delete-button {
            background-color: #FF4D4D;
            color: #FFFFFF;
            font-weight: bold;
            border: none;
            padding: 10px 20px;
            border-radius: 10px;
            cursor: pointer;
            margin-top: 10px;
        }

        .delete-button:hover {
            background-color: #FF0000;
        }
    </style>
</head>
<body>
    <div class="account-container">
        <div class="profile-header">
            <h1>My Profile</h1>
            <div class="profile-picture-container">
                <img src="<?php echo htmlspecialchars($user['ProfilePicture']); ?>" alt="Profile Picture">
                <div class="edit-icon" onclick="document.getElementById('profile-picture-input').click();">
                    <i class="fas fa-pencil-alt"></i>
                </div>
            </div>
        </div>
        <form method="POST" action="account.php" enctype="multipart/form-data">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user['UserID']); ?>">
            <input type="file" id="profile-picture-input" name="profile_picture" style="display: none;">
            <div class="form-group">
                <label for="username">Name</label>
                <input type="text" name="username" class="form-control" value="<?php echo htmlspecialchars($user['Username']); ?>" required>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['UserEmail']); ?>" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="update-button">Update Account</button>
        </form>
        <form method="POST" action="account.php" onsubmit="return confirm('Are you sure you want to delete your account?');">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user['UserID']); ?>">
            <button type="submit" class="delete-button">Delete Account</button>
        </form>
    </div>
    <script>
        // JavaScript to trigger file input on icon click
        document.getElementById('profile-picture-input').addEventListener('change', function () {
            this.form.submit();
        });
    </script>
</body>
</html>
