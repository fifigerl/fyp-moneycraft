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
    <link href="https://fonts.googleapis.com/css2?family=Nunito:ital,wght@0,200..1000;1,200..1000&display=swap" rel="stylesheet">
    <style>
        /* Basic Reset */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Nunito', sans-serif;
            background: linear-gradient(to bottom right, #ffffff, hsl(64, 100%, 97%));
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            overflow: hidden;
        }

        .login-container {
            width: 400px;
            background: #fff;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .login-container img {
            width: 100px;
            height: auto;
            margin-bottom: 1rem;
        }

        .login-container h2 {
            font-size: 1.8rem;
            color: #333;
            margin-bottom: 1.5rem;
        }

        .input-group {
            margin-bottom: 1rem;
            text-align: left;
        }

        .input-group label {
            font-size: 0.9rem;
            color: #333;
            display: block;
        }

        .input-group input {
            width: 100%;
            padding: 0.75rem;
            margin-top: 0.5rem;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 1rem;
        }

        .login-btn {
            width: 100%;
            background-color: #ffdd00;
            color: #fff;
            border: none;
            padding: 0.75rem 0;
            font-size: 1rem;
            font-weight: bold;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 1rem;
        }

        .login-btn:hover {
            background-color: rgb(172, 130, 41);
        }

        .error-message {
            background-color: #ffeded;
            color: #d32f2f;
            padding: 0.75rem;
            margin-bottom: 1rem;
            border: 1px solid #d32f2f;
            border-radius: 5px;
        }

        .error {
            color: #d32f2f;
            font-size: 0.8rem;
            margin-top: 0.5rem;
            display: block;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <img src="images/moneycraftnewlogo.png" alt="logo">
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
