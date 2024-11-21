<?php
// Start the session
session_start();

// Include config file
require_once 'config.php';

// Define variables and initialize with empty values
$admin_username = $admin_password = "";
$admin_username_err = $admin_password_err = "";
$login_err = "";

// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if admin username is empty
    if (empty(trim($_POST["admin_username"]))) {
        $admin_username_err = "Please enter admin username.";
    } else {
        $admin_username = trim($_POST["admin_username"]);
    }

    // Check if password is empty
    if (empty(trim($_POST["admin_password"]))) {
        $admin_password_err = "Please enter your password.";
    } else {
        $admin_password = trim($_POST["admin_password"]);
    }

    // Validate credentials
    if (empty($admin_username_err) && empty($admin_password_err)) {
        // Prepare a select statement
        $sql = "SELECT AdminID, AdminUsername, AdminPwd FROM Admin WHERE AdminUsername = ?";

        if ($stmt = $conn->prepare($sql)) {
            // Bind variables to the prepared statement as parameters
            $stmt->bind_param("s", $param_admin_username);
            
            // Set parameters
            $param_admin_username = $admin_username;
            
            // Attempt to execute the prepared statement
            if ($stmt->execute()) {
                // Store result
                $stmt->store_result();
                
                // Check if admin username exists, if yes then verify password
                if ($stmt->num_rows == 1) {
                    // Bind result variables
                    $stmt->bind_result($admin_id, $admin_username, $hashed_password);
                    if ($stmt->fetch()) {
                        if (password_verify($admin_password, $hashed_password)) {
                            // Password is correct, so start a new session
                            session_start();
                            
                            // Store data in session variables
                            $_SESSION["admin_loggedin"] = true;
                            $_SESSION["admin_id"] = $admin_id;
                            $_SESSION["admin_username"] = $admin_username;                            
                            
                            // Redirect admin to admin dashboard page
                            header("location: admin_manage_users.php");
                        } else {
                            // Password is not valid, display a generic error message
                            $login_err = "Invalid admin username or password.";
                        }
                    }
                } else {
                    // Admin username doesn't exist, display a generic error message
                    $login_err = "Invalid admin username or password.";
                }
            } else {
                echo "Oops! Something went wrong. Please try again later.";
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
    <title>Admin Login</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <img src="images/logo.jpg" alt="logo">
        </div>
        <h2>Admin Login</h2>
        
        <?php 
        if (!empty($login_err)) {
            echo '<div class="error-message">' . $login_err . '</div>';
        }        
        ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" class="login-form">
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
            <button type="submit" class="login-btn">Log In</button>
        </form>
    </div>
</body>
</html>

