<!DOCTYPE html> 
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MoneyCraft Sign Up</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <img src="images/logo.jpg" alt="MoneyCraft logo">
        </div>
        <h2>Create a new account.</h2>
        
        <?php
        // Include config file
        require_once 'config.php';
        
        // Define variables and initialize with empty values
        $username = $email = $password = $confirm_password = "";
        $username_err = $email_err = $password_err = $confirm_password_err = "";
        $success_message = "";
        
        // Processing form data when form is submitted
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            // Validate username
            if (empty(trim($_POST["first-name"]))) {
                $username_err = "Please enter a username.";
            } else {
                $username = trim($_POST["first-name"]);
            }
            
            // Validate email
            if (empty(trim($_POST["email"]))) {
                $email_err = "Please enter an email.";
            } elseif (!filter_var(trim($_POST["email"]), FILTER_VALIDATE_EMAIL)) {
                $email_err = "Invalid email format.";
            } else {
                $email = trim($_POST["email"]);
            }
            
            // Validate password
            if (empty(trim($_POST["password"]))) {
                $password_err = "Please enter a password.";     
            } elseif (strlen(trim($_POST["password"])) < 6) {
                $password_err = "Password must have at least 6 characters.";
            } else {
                $password = trim($_POST["password"]);
            }
            
            // Validate confirm password
            if (empty(trim($_POST["confirm-password"]))) {
                $confirm_password_err = "Please confirm password.";     
            } else {
                $confirm_password = trim($_POST["confirm-password"]);
                if (empty($password_err) && ($password != $confirm_password)) {
                    $confirm_password_err = "Password did not match.";
                }
            }

            // Check input errors before inserting in the database
            if (empty($username_err) && empty($email_err) && empty($password_err) && empty($confirm_password_err)) {
                
                // Prepare an insert statement
                $sql = "INSERT INTO users (Username, UserEmail, UserPwd, createdAt) VALUES (?, ?, ?, ?)";
                
                if ($stmt = $conn->prepare($sql)) {
                    // Bind variables to the prepared statement as parameters
                    $stmt->bind_param("ssss", $param_username, $param_email, $param_password, $param_createdAt);
                    
                    // Set parameters
                    $param_username = $username;
                    $param_email = $email;
                    $param_password = password_hash($password, PASSWORD_DEFAULT); // Creates a password hash
                    $param_createdAt = date("Y-m-d");
                    
                    // Attempt to execute the prepared statement
                    if ($stmt->execute()) {
                        $success_message = "Account creation is successful!";
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

        <?php if (!empty($success_message)): ?>
            <p class="success-message"><?php echo $success_message; ?></p>
        <?php endif; ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" class="login-form">
            <div class="input-group">
                <label for="username">Username</label>
                <input type="text" id="first-name" name="first-name" value="<?php echo $username; ?>" required>
                <span class="error"><?php echo $username_err; ?></span>
            </div>
           
            <div class="input-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?php echo $email; ?>" required>
                <span class="error"><?php echo $email_err; ?></span>
            </div>
            <div class="input-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
                <span class="error"><?php echo $password_err; ?></span>
            </div>
            <div class="input-group">
                <label for="confirm-password">Confirm Password</label>
                <input type="password" id="confirm-password" name="confirm-password" required>
                <span class="error"><?php echo $confirm_password_err; ?></span>
            </div>
            <button type="submit" class="signup-btn">Sign Up</button>
        </form>
        <p class="login-text">Already Have An Account? <a href="login.php">Login</a></p>
    </div>
</body>
</html>
