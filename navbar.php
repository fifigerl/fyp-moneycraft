<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config.php';

// Default username if not logged in
$username = 'Guest';

if (isset($_SESSION['id'])) {
    $user_id = $_SESSION['id'];

    // Fetch the username from the database
    $sql = "SELECT Username FROM Users WHERE UserID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        $username = htmlspecialchars($user['Username']);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MoneyCraft Dashboard</title>
    <link rel="stylesheet" href="../css/navbar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:ital,wght@0,200..1000;1,200..1000&display=swap" rel="stylesheet">
</head>
<body>
    <nav class="navbar">
        <!-- Left Section: Logo -->
        <div class="logo">
            <img src="../images/moneycraftnewlogo.png" alt="MoneyCraft Logo">
        </div>

      

        <!-- Center Section: Links -->
        <ul class="nav-links">
            <li><a href="../index.php">Home</a></li>
            <li class="dropdown">
                <a href="#" class="dropbtn">Financial Activity</a>
                <div class="dropdown-content">
                    <a href="../drpdown/transactions.php">My Transactions</a>
                    <a href="../drpdown/budgets.php">My Budgets</a>
                    <a href="../drpdown/savings.php">My Savings Goals</a>
                    <a href="../drpdown/bills.php">My Bills</a>
                    <a href="../drpdown/feedbacks.php">My Feedbacks</a>
                    <a href="../drpdown/report.php">My Report</a>
                </div>
            </li>
            <li><a href="../financial.php">Learning Center</a></li>
        </ul>

        <!-- Right Section: User Info -->
        <div class="navbar-end">
        <div class="profile">
    <a href="../account.php" title="Account">
        <i class="fa-regular fa-circle-user"></i>
        
    </a>
</div>

<div class="navbar-username">
<span>  Hi, <?php echo $username; ?></span>
</div>


            <a href="../logout.php" class="button">Logout</a>
        </div>
    </nav>
</body>
<script>
    // Highlight the active link
    const currentPage = window.location.pathname;
    const navLinks = document.querySelectorAll('.nav-links a, .dropdown-content a');
    navLinks.forEach(link => {
        if (new URL(link.href).pathname === currentPage) {
            link.classList.add('active');
        }
    });

    // Add scroll effect for navbar
    const navbar = document.querySelector('.navbar');
    window.addEventListener('scroll', () => {
        if (window.scrollY > 50) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    });
</script>
</html>
