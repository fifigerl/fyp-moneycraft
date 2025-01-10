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

// Fetch budget data
$sql = "SELECT BudgetCat, SUM(BudgetAmt) AS total_budget FROM Budgets WHERE UserID = ? GROUP BY BudgetCat";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    die('Prepare failed: ' . htmlspecialchars($conn->error));
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$budgets = $stmt->get_result();

$budget_data = [];
$budget_labels = [];
while ($budget = $budgets->fetch_assoc()) {
    $budget_labels[] = $budget['BudgetCat'];
    $budget_data[] = $budget['total_budget'];
}

// Fetch transaction data for spending trends
$sql = "SELECT DATE_FORMAT(TranDate, '%Y-%m') AS month, SUM(TranAmount) AS total_spending
        FROM Transactions
        WHERE UserID = ? AND TranType = 'Expense'
        GROUP BY month
        ORDER BY month";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    die('Prepare failed: ' . htmlspecialchars($conn->error));
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$spending_trends = $stmt->get_result();

$spending_labels = [];
$spending_data = [];
while ($trend = $spending_trends->fetch_assoc()) {
    $spending_labels[] = $trend['month'];
    $spending_data[] = $trend['total_spending'];
}

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
        <div class="dashboard-widget savings-progress">
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
        <div class="dashboard-widget interactive-chart">
            <h3>Spending Trends</h3>
            <canvas id="spendingChart"></canvas>
        </div>
        <div class="dashboard-widget budget-breakdown">
            <h3>Budget Breakdown</h3>
            <canvas id="budgetChart"></canvas>
        </div>
        <div class="dashboard-widget bills-tracker">
            <h3>Upcoming Bills</h3>
            <?php while ($bill = $bills->fetch_assoc()): ?>
                <div class="dashboard-bill-item">
                    <i class="fas fa-bell"></i>
                    <p><?php echo htmlspecialchars($bill['BillTitle']); ?>: RM<?php echo number_format($bill['BillAmt'], 2); ?></p>
                    <span>Due: <?php echo date('F j', strtotime($bill['BillDue'])); ?></span>
                </div>
            <?php endwhile; ?>
        </div>
        <div class="dashboard-widget recent-transactions">
            <h3>Recent Transactions</h3>
            <input type="text" id="searchTransactions" placeholder="Search transactions...">
            <?php while ($transaction = $transactions->fetch_assoc()): ?>
                <div class="dashboard-transaction-item">
                    <i class="fas fa-receipt icon"></i>
                    <p><?php echo htmlspecialchars($transaction['TranTitle']); ?></p>
                    <span><?php echo date('H:i - F j', strtotime($transaction['TranDate'])); ?></span>
                    <span class="dashboard-amount">RM<?php echo number_format($transaction['TranAmount'], 2); ?></span>
                </div>
            <?php endwhile; ?>
        </div>
        <div class="dashboard-widget quick-access">
            <h3>Quick Access</h3>
            <button class="dashboard-button" onclick="location.href='../drpdown/transactions.php'"><i class="fas fa-plus-circle"></i> Add Transaction</button>
            <button class="dashboard-button" onclick="location.href='../drpdown/budgets.php'"><i class="fas fa-chart-line"></i> Create Budget</button>
            <button class="dashboard-button" onclick="location.href='../drpdown/savings.php'"><i class="fas fa-piggy-bank"></i> Manage Savings</button>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const spendingCtx = document.getElementById('spendingChart').getContext('2d');
        const spendingChart = new Chart(spendingCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($spending_labels); ?>,
                datasets: [{
                    label: 'Spending',
                    data: <?php echo json_encode($spending_data); ?>,
                    borderColor: '#4A148C',
                    fill: false
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `RM${context.raw}`;
                            }
                        }
                    }
                }
            }
        });

        const budgetCtx = document.getElementById('budgetChart').getContext('2d');
        const budgetChart = new Chart(budgetCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($budget_labels); ?>,
                datasets: [{
                    data: <?php echo json_encode($budget_data); ?>,
                    backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `${context.label}: RM${context.raw}`;
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>