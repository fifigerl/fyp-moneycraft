<?php
// Start the session
session_start();

// Include config file
require_once 'config.php';

// Define variables and initialize with empty values
$username = $password = "";
$username_err = $password_err = "";
$login_err = "";

// Check for logout success message
$logout_message = "";
if (isset($_GET['logout']) && $_GET['logout'] == 'success') {
    $logout_message = "You have been successfully logged out.";
}

// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if username is empty
    if (empty(trim($_POST["username"]))) {
        $username_err = "Please enter username.";
    } else {
        $username = trim($_POST["username"]);
    }

    // Check if password is empty
    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter your password.";
    } else {
        $password = trim($_POST["password"]);
    }

    // Validate credentials
    if (empty($username_err) && empty($password_err)) {
        // Prepare a select statement
        $sql = "SELECT UserID, Username, UserPwd, status FROM users WHERE Username = ?";

        if ($stmt = $conn->prepare($sql)) {
            // Bind variables to the prepared statement as parameters
            $stmt->bind_param("s", $param_username);
            
            // Set parameters
            $param_username = $username;
            
            // Attempt to execute the prepared statement
            if ($stmt->execute()) {
                // Store result
                $stmt->store_result();
                
                // Check if username exists, if yes then verify password
                if ($stmt->num_rows == 1) {
                    // Bind result variables
                    $stmt->bind_result($id, $username, $hashed_password, $status);
                    if ($stmt->fetch()) {
                        if ($status === 'inactive') {
                            $login_err = "Your account has been suspended. Please contact support.";
                        } elseif (password_verify($password, $hashed_password)) {
                            // Password is correct, so start a new session
                            session_start();
                            
                            // Store data in session variables
                            $_SESSION["loggedin"] = true;
                            $_SESSION["id"] = $id;
                            $_SESSION["username"] = $username;                            
                            
                            // Redirect user to index page
                            header("location: index.php");
                        } else {
                            // Password is not valid, display a generic error message
                            $login_err = "Invalid username or password.";
                        }
                    }
                } else {
                    // Username doesn't exist, display a generic error message
                    $login_err = "Invalid username or password.";
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
    <title>MoneyCraft Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:ital,wght@0,200..1000;1,200..1000&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@dotlottie/player-component@2.7.12/dist/dotlottie-player.mjs" type="module"></script>
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

        /* Animation keyframes */
        @keyframes fadeInUp {
            0% {
                opacity: 0;
                transform: translateY(50px);
            }
            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .illustration-container,
        .login-container {
            animation: fadeInUp 1.5s ease-in-out;
        }

        .illustration-container {
            width: 50%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            padding: 2rem;
        }

        .illustration-container h1 {
            font-size: 3rem;
            color: #00296b;
            font-weight: 900;
            margin-bottom: 1rem;
        }

        .illustration-container p {
            font-size: 1.2rem;
            color: #555;
            margin-bottom: 2rem;
            line-height: 1.5;
        }

        .illustration-container dotlottie-player {
            max-width: 400px;
            width: 100%;
            height: auto;
        }

        .login-container {
            width: 400px;
            background: #fff;
            border-radius: 10px;
            padding: 2rem;
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
        }

        .login-container h2 {
            text-align: center;
            font-size: 1.8rem;
            color: #333;
            margin-bottom: 1.5rem;
        }

        .input-group {
            margin-bottom: 1rem;
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

        .options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            color: #666;
        }

        .options a {
            text-decoration: none;
            color: #666;
        }

        .options a:hover {
            text-decoration: underline;
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
            margin-bottom: 1rem;
            transition: background-color 0.3s ease-in-out;
        }

        .login-btn:hover {
            background-color: rgb(172, 130, 41);
        }

        .social-login {
            text-align: center;
            margin: 1rem 0;
            font-size: 0.9rem;
            color: #333;
        }

        .social-login-buttons {
            display: flex;
            justify-content: space-evenly;
            margin-top: 1rem;
        }

        .social-login-buttons a {
            font-size: 1.5rem;
            color: #666;
            text-decoration: none;
        }

        .social-login-buttons a:hover {
            color: #333;
        }

        @media (max-width: 768px) {
            body {
                flex-direction: column;
                padding: 1rem;
            }

            .illustration-container {
                width: 100%;
                padding: 1rem;
            }

            .login-container {
                width: 90%;
            }
        }
    </style>
</head>
<body>
    <!-- Illustration Section -->
    <div class="illustration-container">
        <h1>Welcome to MoneyCraft</h1>
        <p>Your trusted financial tracker for students. Manage your expenses, stay on top of your savings, and take control of your financial future—all in one place.</p>
        <dotlottie-player 
            src="https://lottie.host/5ed9af5d-5bde-4fd5-81a1-3f915b28c1e8/bTEu5NivS0.lottie" 
            background="transparent" 
            speed="1" 
            loop 
            autoplay>
        </dotlottie-player>
    </div>

    <!-- Login Form Section -->
    <div class="login-container">
        <h2>Login to Your Account</h2>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
            <div class="input-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" placeholder="Enter your username" required>
            </div>
            <div class="input-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter your password" required>
            </div>
            <div class="options">
                <label><input type="checkbox" name="remember"> Remember Password</label>
                <a href="#">Forgot Password?</a>
            </div>
            <button type="submit" class="login-btn">Login</button>
        </form>
        <div class="social-login">
            Or Login With:
            <div class="social-login-buttons">
                <a href="#"><i class="fab fa-apple"></i></a>
                <a href="#"><i class="fab fa-twitter"></i></a>
                <a href="#"><i class="fab fa-facebook-f"></i></a>
                <a href="#"><i class="fab fa-google"></i></a>
            </div>
        </div>
        <p style="text-align: center; margin-top: 1rem;">No account yet? <a href="signup.php">Register</a></p>
    </div>
</body>
</html>
