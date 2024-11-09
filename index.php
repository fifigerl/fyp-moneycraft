<?php
// Initialize the session
session_start();

// Check if the user is logged in, if not then redirect them to login page
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MoneyCraft Dashboard</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<?php include 'navbar.php'; ?>
    <div class="dashboard-container">
        <div class="left-panel">
            <div class="savings-goals">
                <h2>RM500</h2>
                <p>Savings On Goals</p>
            </div>
            <div class="bills">
                <h3>Bills due 1st June (Rent)</h3>
                <p>RM150</p>
            </div>
            <div class="food-expenses">
                <h3>Food Last Week</h3>
                <p>-RM100</p>
            </div>
        </div>
        <div class="right-panel">
            <div class="video-section">
                <h2>Dashboard</h2>
                <div class="video">
                    <iframe width="100%" height="300" src="https://www.youtube.com/embed/your-video-id" frameborder="0" allowfullscreen></iframe>
                </div>
                <p>Don't forget to watch the latest financial literacy video!</p>
            </div>
        </div>
    </div>

    <div class="transaction-history">
        <h3>Transaction History (April)</h3>
        <div class="filter">
            <button>Daily</button>
            <button>Weekly</button>
            <button>Monthly</button>
            <button>Yearly</button>
        </div>
        <div class="transaction-item">
            <img src="salary-icon.png" alt="Salary Icon">
            <p>Salary</p>
            <span>18:27 - April 30</span>
            <span class="amount">RM1500</span>
        </div>
        <div class="transaction-item">
            <img src="groceries-icon.png" alt="Groceries Icon">
            <p>Groceries</p>
            <span>17:00 - April 24</span>
            <span class="amount">-RM174</span>
        </div>
        <div class="transaction-item">
            <img src="rent-icon.png" alt="Rent Icon">
            <p>Rent</p>
            <span>8:30 - April 15</span>
            <span class="amount">-RM150</span>
        </div>
    </div>

</body>
</html>