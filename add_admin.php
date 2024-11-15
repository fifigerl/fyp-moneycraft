<?php
// Start the session
session_start();

// Include config file
require_once 'config.php';

// Define variables and initialize with empty values
$admin_username = $admin_password = $admin_email = "";
$admin_username_err = $admin_password_err = $admin_email_err = "";

// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate admin username
    if (empty(trim($_POST["admin_username"]))) {
        $admin_username_err = "Please enter an admin username.";
    } else {
        $admin_username = trim($_POST["admin_username"]);
    }

    // Validate password
    if (empty(trim($_POST["admin_password"]))) {
        $admin_password_err = "Please enter a password.";
    } elseif (strlen(trim($_POST["admin_password"])) < 6) {
        $admin_password_err = "Password must have at least 6 characters.";
    } else {
        $admin_password = trim($_POST["admin_password"]);
    }

    // Validate email
    if (empty(trim($_POST["admin_email"]))) {
        $admin_email_err = "Please enter an email.";
    } elseif (!filter_var(trim($_POST["admin_email"]), FILTER_VALIDATE_EMAIL)) {
        $admin_email_err = "Invalid email format.";
    } else {
        $admin_email = trim($_POST["admin_email"]);
    }

    // Check input errors before inserting in database
    if (empty($admin_username_err) && empty($admin_password_err) && empty($admin_email_err)) {
        // Prepare an insert statement
        $sql = "INSERT INTO Admin (AdminUsername, AdminPwd, AdminEmail) VALUES (?, ?, ?)";

        if ($stmt = $conn->prepare($sql)) {
            // Bind variables to the prepared statement as parameters
            $stmt->bind_param("sss", $param_admin_username, $param_admin_password, $param_admin_email);

            // Set parameters
            $param_admin_username = $admin_username;
            $param_admin_password = password_hash($admin_password, PASSWORD_DEFAULT); // Creates a password hash
            $param_admin_email = $admin_email;

            // Attempt to execute the prepared statement
            if ($stmt->execute()) {
                // Redirect to login page
                header("location: admin.php");
            } else {
                echo "Something went wrong. Please try again later.";
            }

            // Close statement
            $stmt->close();
        }
    }

    // Close connection
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Admin Account</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
</head>
<body>
    <div class="signup-container">
        <div class="logo">
            <img src="images/logo.jpg" alt="logo">
        </div>
        <h2>Add Admin Account</h2>
        
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" class="signup-form">
            <div class="input-group">
                <label for="admin_username">Admin Username</label>
                <input type="text" id="admin_username" name="admin_username" value="<?php echo $admin_username; ?>" required>
                <span class="error"><?php echo $admin_username_err; ?></span>
            </div>
            <div class="input-group">
                <label for="admin_password">Password</label>
                <input type="password" id="admin_password" name="admin_password" required>
                <span class="error"><?php echo $admin_password_err; ?></span>
            </div>
            <div class="input-group">
                <label for="admin_email">Email</label>
                <input type="email" id="admin_email" name="admin_email" value="<?php echo $admin_email; ?>" required>
                <span class="error"><?php echo $admin_email_err; ?></span>
            </div>
            <button type="submit" class="signup-btn">Add Admin</button>
        </form>
    </div>
</body>
</html> 