<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MoneyCraft Sign Up</title>
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

        .signup-container {
            width: 400px;
            background: #fff;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .signup-container h2 {
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

        .error {
            color: #ff6b6b;
            font-size: 0.8rem;
        }

        .success-message {
            color: #4caf50;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }

        .signup-btn {
            width: 100%;
            background-color: #ffdd00;
            color: #fff;
            border: none;
            padding: 0.75rem 0;
            font-size: 1rem;
            font-weight: bold;
            border-radius: 5px;
            cursor: pointer;
            margin-bottom: 1rem;
        }

        .signup-btn:hover {
            background-color: rgb(172, 130, 41);
        }

        .login-text {
            font-size: 0.9rem;
            color: #333;
        }

        .login-text a {
            color: #ffdd00;
            text-decoration: none;
            font-weight: bold;
        }

        .login-text a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="signup-container">
        <h2>Create a New Account</h2>
        
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

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" class="signup-form">
            <div class="input-group">
                <label for="first-name">Username</label>
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
