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
            <img src="logo.jpg" alt="MoneyCraft logo">
        </div>
        <h2>Create a new account.</h2>
        <form action="#" method="POST" class="login-form">
            <div class="input-group">
                <label for="username">Username</label>
                <input type="text" id="first-name" name="first-name" required>
            </div>
           
            <div class="input-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="input-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="input-group">
                <label for="confirm-password">Confirm Password</label>
                <input type="password" id="confirm-password" name="confirm-password" required>
            </div>
            <button type="submit" class="signup-btn">Sign Up</button>
        </form>
        <p class="login-text">Already Have An Account? <a href="login.php">Login</a></p>

    </div>
</body>
</html>
