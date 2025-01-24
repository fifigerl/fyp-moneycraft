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
        $sql = "SELECT UserID, Username, UserPwd, status FROM Users WHERE Username = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $param_username);
            $param_username = $username;

            if ($stmt->execute()) {
                $stmt->store_result();

                if ($stmt->num_rows == 1) {
                    $stmt->bind_result($id, $username, $hashed_password, $status);
                    if ($stmt->fetch()) {
                        if ($status === 'suspended') {
                            // Redirect suspended user to suspension page
                            header("location: user_suspended.php");
                            exit;
                        } elseif (password_verify($password, $hashed_password)) {
                            // Password is correct, start session
                            session_start();
                            $_SESSION["loggedin"] = true;
                            $_SESSION["id"] = $id;
                            $_SESSION["username"] = $username;
                            $_SESSION["status"] = $status;

                            // Redirect to index page
                            header("location: index.php");
                            exit;
                        } else {
                            $login_err = "Invalid username or password.";
                        }
                    }
                } else {
                    $login_err = "Invalid username or password.";
                }
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }
            $stmt->close();
        }
    }
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
            background: linear-gradient(to bottom, rgb(252, 249, 235), rgb(255, 255, 235), hsl(0, 0.00%, 99.60%));
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            overflow: hidden;
        }

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
            width: 45%;
            text-align: center;
            padding: 1rem;
            margin-right: 10px;
        }

        .illustration-container h1 {
            font-size: 3rem;
            color: #00296b;
            font-weight: 900;
            margin-bottom: 1rem;
        }

        .illustration-container dotlottie-player {
            max-width: 600px;
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
            border-radius: 5px;
            font-size: 1rem;
        }

        .login-btn {
            width: 100%;
            background-color: #ffdd00;
            color: #fff;
            padding: 0.75rem 0;
            font-size: 1rem;
            font-weight: bold;
            border-radius: 5px;
            cursor: pointer;
            border: none;
        }

        .login-btn:hover {
            background-color: rgb(172, 130, 41);
        }

        .social-login {
    text-align: center;
    margin-top: 1rem;
}

.social-login-buttons {
    display: flex;
    justify-content: center;
    gap: 10px;
    margin-top: 10px;
}

.social-btn {
    display: flex;
    justify-content: center;
    align-items: center;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    font-size: 1.2rem;
    text-decoration: none;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.social-btn:hover {
    transform: scale(1.1);
    box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
}

.social-btn i {
    color: inherit;
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

        <?php if (!empty($logout_message)): ?>
        <div class="logout-message" style="background-color: #d4edda; color: #155724; padding: 10px; margin-bottom: 15px; border: 1px solid #c3e6cb; border-radius: 5px;">
            <?php echo $logout_message; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($login_err)): ?>
        <div class="error-message" style="background-color: #f8d7da; color: #721c24; padding: 10px; margin-bottom: 15px; border: 1px solid #f5c6cb; border-radius: 5px;">
            <?php echo $login_err; ?>
        </div>
        <?php endif; ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
            <div class="input-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" placeholder="Enter your username" required>
            </div>
            <div class="input-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter your password" required>
            </div>
            <button type="submit" class="login-btn">Login</button>
        </form>
   
        <div class="social-login">
    <p style="text-align: center; font-weight: bold; font-size: 1rem; color: #333; margin-bottom: 1rem;">Or Login With:</p>
    <div class="social-login-buttons">
        <a href="#" class="social-btn" style="background-color: #333; color: #fff;">
            <i class="fab fa-apple"></i>
        </a>
        <a href="#" class="social-btn" style="background-color: #1DA1F2; color: #fff;">
            <i class="fab fa-twitter"></i>
        </a>
        <a href="#" class="social-btn" style="background-color: #1877F2; color: #fff;">
            <i class="fab fa-facebook-f"></i>
        </a>
        <a href="#" class="social-btn" style="background-color: #DB4437; color: #fff;">
            <i class="fab fa-google"></i>
        </a>
    </div>
    <p style="text-align: center; margin-top: 1rem;">No account yet? <a href="signup.php" style="color: #ffdd00; text-decoration: none;">Register</a></p>
</div>

</body>
</html>