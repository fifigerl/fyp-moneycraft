<?php
// Initialize the session
session_start();

// Check if the user is logged in, if not then redirect them to login page
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Include config file
require_once 'config.php';

// Fetch user data from the database
$user_id = $_SESSION['id'];

// Fetch total income, expenses, and savings
$sql = "SELECT 
            SUM(CASE WHEN TranType = 'Income' THEN TranAmount ELSE 0 END) AS total_income,
            SUM(CASE WHEN TranType = 'Expense' THEN TranAmount ELSE 0 END) AS total_expenses,
            SUM(CurrentSavings) AS total_savings
        FROM Transactions
        LEFT JOIN Savings ON Transactions.UserID = Savings.UserID
        WHERE Transactions.UserID = ?";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    die('Prepare failed: ' . htmlspecialchars($conn->error));
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();
$total_income = $data['total_income'] ?? 0;
$total_expenses = $data['total_expenses'] ?? 0;
$total_savings = $data['total_savings'] ?? 0;

// Fetch recent transactions
$sql = "SELECT TranTitle, TranDate, TranAmount FROM Transactions WHERE UserID = ? ORDER BY TranDate DESC LIMIT 5";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    die('Prepare failed: ' . htmlspecialchars($conn->error));
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$transactions = $stmt->get_result();

// Fetch upcoming bills
$sql = "SELECT BillTitle, BillDue, BillAmt FROM BillsReminder WHERE UserID = ? AND BillDue >= CURDATE() ORDER BY BillDue ASC LIMIT 5";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    die('Prepare failed: ' . htmlspecialchars($conn->error));
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$bills = $stmt->get_result();

// Fetch savings goals
$sql = "SELECT SavingsTitle, SavingsAmt, CurrentSavings FROM Savings WHERE UserID = ?";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    die('Prepare failed: ' . htmlspecialchars($conn->error));
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$savings = $stmt->get_result();

// Fetch featured financial education resources
$sql = "SELECT ResourceTitle, ResourceLink FROM FinancialEducationResources ORDER BY ContentDate DESC LIMIT 3";
$resources = $conn->query($sql);

if ($resources === false) {
    die('Query failed: ' . htmlspecialchars($conn->error));
}

// Hardcoded video ID for demonstration
$video_id = 'MXCvtC0HqLE';
$resource_title = 'Financial Literacy 101';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MoneyCraft Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/navbar.css">
    <link rel="stylesheet" href="styles.css">
</head>
<body class="dashboard">
    <?php include 'navbar.php'; ?>
    <div class="dashboard-container">
        <div class="dashboard-widget current-balance">
            <h3>Current Balance</h3>
            <p><i class="fas fa-wallet icon"></i> Total Income: RM<?php echo number_format($total_income, 2); ?></p>
            <p><i class="fas fa-money-bill-wave icon"></i> Total Expenses: RM<?php echo number_format($total_expenses, 2); ?></p>
            <p><i class="fas fa-piggy-bank icon"></i> Total Savings: RM<?php echo number_format($total_savings, 2); ?></p>
        </div>
        <div class="dashboard-widget recent-transactions">
            <h3>Recent Transactions</h3>
            <?php while ($transaction = $transactions->fetch_assoc()): ?>
                <div class="dashboard-transaction-item">
                    <i class="fas fa-receipt icon"></i>
                    <p><?php echo htmlspecialchars($transaction['TranTitle']); ?></p>
                    <span><?php echo date('H:i - F j', strtotime($transaction['TranDate'])); ?></span>
                    <span class="dashboard-amount">RM<?php echo number_format($transaction['TranAmount'], 2); ?></span>
                </div>
            <?php endwhile; ?>
        </div>
        <div class="dashboard-widget">
            <h3>Upcoming Bill Reminders</h3>
            <?php while ($bill = $bills->fetch_assoc()): ?>
                <div class="dashboard-bill-item">
                    <i class="fas fa-bell"></i>
                    <p><?php echo htmlspecialchars($bill['BillTitle']); ?>: RM<?php echo number_format($bill['BillAmt'], 2); ?></p>
                    <span>Due: <?php echo date('F j', strtotime($bill['BillDue'])); ?></span>
                </div>
            <?php endwhile; ?>
        </div>
        <div class="dashboard-widget savings-goals">
            <h3>Savings Goals</h3>
            <?php while ($saving = $savings->fetch_assoc()): ?>
                <div class="dashboard-saving-item">
                    <i class="fas fa-bullseye"></i>
                    <p><?php echo htmlspecialchars($saving['SavingsTitle']); ?></p>
                    <div class="dashboard-progress-bar">
                        <div class="dashboard-progress" style="width: <?php echo ($saving['CurrentSavings'] / $saving['SavingsAmt']) * 100; ?>%;"></div>
                    </div>
                    <span>RM<?php echo number_format($saving['CurrentSavings'], 2); ?> / RM<?php echo number_format($saving['SavingsAmt'], 2); ?></span>
                </div>
            <?php endwhile; ?>
        </div>
        <div class="dashboard-widget quick-access">
            <h3>Quick Access</h3>
            <button class="dashboard-button" onclick="location.href='../drpdown/transactions.php'"><i class="fas fa-plus-circle"></i> Add Transaction</button>
            <button class="dashboard-button" onclick="location.href='../drpdown/budgets.php'"><i class="fas fa-chart-line"></i> Create Budget</button>
            <button class="dashboard-button" onclick="location.href='../drpdown/savings.php'"><i class="fas fa-piggy-bank"></i> Manage Savings</button>
        </div>
        <div class="dashboard-widget reports-overview">
            <h3>Reports Overview</h3>
            <p>Monthly Spending: RM<?php echo number_format($total_expenses, 2); ?></p>
            <p>Income vs. Expenses: RM<?php echo number_format($total_income, 2); ?> vs. RM<?php echo number_format($total_expenses, 2); ?></p>
        </div>
    </div>
    <div class="dashboard-video-section">
        <h2>Watch Financial Literacy Videos</h2>
        <iframe src="https://www.youtube.com/embed/<?php echo $video_id; ?>" frameborder="0" allowfullscreen style="width: 100%; height: 400px;"></iframe>
        <p><?php echo htmlspecialchars($resource_title); ?></p>
    </div>
</body>
</html>