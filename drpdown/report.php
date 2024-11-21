<?php
// Start the session
session_start();

// Include config file
require_once '../config.php';

// Check if the user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php");
    exit;
}

$user_id = $_SESSION['id']; // Use session user ID

// Initialize arrays for financial data
$transactions = [];
$income = [];
$expenses = [];
$categories = [];

// Determine the selected period
$selectedPeriod = $_GET['period'] ?? 'monthly';

// Fetch transactions based on the selected period
$periodCondition = '';
switch ($selectedPeriod) {
    case 'daily':
        $periodCondition = "AND TranDate = CURDATE()";
        break;
    case 'weekly':
        $periodCondition = "AND YEARWEEK(TranDate, 1) = YEARWEEK(CURDATE(), 1)";
        break;
    case 'monthly':
        $periodCondition = "AND MONTH(TranDate) = MONTH(CURDATE()) AND YEAR(TranDate) = YEAR(CURDATE())";
        break;
    case 'yearly':
        $periodCondition = "AND YEAR(TranDate) = YEAR(CURDATE())";
        break;
}

$sql = "SELECT TranID, TranTitle, TranAmount, TranType, TranCat, TranDate 
        FROM Transactions 
        WHERE UserID = ? $periodCondition";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $user_id);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $transactions[] = $row;
            if ($row['TranType'] === 'income') {
                $income[] = $row;
            } else {
                $expenses[] = $row;
                $categories[$row['TranCat']] = ($categories[$row['TranCat']] ?? 0) + $row['TranAmount'];
            }
        }
    }
    $stmt->close();
}

$conn->close();

// Calculate totals
$totalIncome = array_sum(array_column($income, 'TranAmount'));
$totalExpenses = array_sum(array_column($expenses, 'TranAmount'));
$savingsTotal = $totalIncome - $totalExpenses;

// Function to categorize transactions by time period
function categorizeTransactions($transactions, $period) {
    $categorized = [];
    $now = new DateTime();

    foreach ($transactions as $transaction) {
        $date = new DateTime($transaction['TranDate']);
        $interval = $now->diff($date);

        switch ($period) {
            case 'daily':
                if ($interval->days === 0) {
                    $categorized[] = $transaction;
                }
                break;
            case 'weekly':
                if ($interval->days <= 7) {
                    $categorized[] = $transaction;
                }
                break;
            case 'monthly':
                if ($interval->m === 0 && $interval->y === 0) {
                    $categorized[] = $transaction;
                }
                break;
            case 'yearly':
                if ($interval->y === 0) {
                    $categorized[] = $transaction;
                }
                break;
        }
    }

    return $categorized;
}

$categorizedTransactions = categorizeTransactions($transactions, $selectedPeriod);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Reports</title>
    <link rel="stylesheet" href="../css/navbar.css">
    <link rel="stylesheet" href="../styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="report-page">
    <?php include '../navbar.php'; ?>
  
    <div class="report-container">
        <h2>My Reports</h2>
        <form method="GET" action="">
            <label for="period">Select Report Period:</label>
            <select name="period" id="period" onchange="this.form.submit()">
                <option value="daily" <?php if ($selectedPeriod == 'daily') echo 'selected'; ?>>Daily</option>
                <option value="weekly" <?php if ($selectedPeriod == 'weekly') echo 'selected'; ?>>Weekly</option>
                <option value="monthly" <?php if ($selectedPeriod == 'monthly') echo 'selected'; ?>>Monthly</option>
                <option value="yearly" <?php if ($selectedPeriod == 'yearly') echo 'selected'; ?>>Yearly</option>
            </select>
        </form>
        <div class="report-summary-overview">
            <h3>Summary Overview</h3>
            <p>Total Income: RM<?php echo number_format($totalIncome, 2); ?></p>
            <p>Total Expenses: RM<?php echo number_format($totalExpenses, 2); ?></p>
            <p>Savings: RM<?php echo number_format($savingsTotal, 2); ?></p>
        </div>
        <div class="report-section">
            <h3>Expenses by Category</h3>
            <canvas id="expensesByCategoryChart"></canvas>
        </div>
        <div class="report-section">
            <h3>Spending Trends</h3>
            <canvas id="spendingTrendsChart"></canvas>
        </div>
    </div>
  
    <script>
        // Expenses by Category Chart
        const expensesByCategoryCtx = document.getElementById('expensesByCategoryChart').getContext('2d');
        const expensesByCategoryChart = new Chart(expensesByCategoryCtx, {
            type: 'pie',
            data: {
                labels: <?php echo json_encode(array_keys($categories)); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_values($categories)); ?>,
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.2)',
                        'rgba(54, 162, 235, 0.2)',
                        'rgba(255, 206, 86, 0.2)',
                        'rgba(75, 192, 192, 0.2)',
                        'rgba(153, 102, 255, 0.2)',
                        'rgba(255, 159, 64, 0.2)'
                    ],
                    borderColor: [
                        'rgba(255, 99, 132, 1)',
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 206, 86, 1)',
                        'rgba(75, 192, 192, 1)',
                        'rgba(153, 102, 255, 1)',
                        'rgba(255, 159, 64, 1)'
                    ],
                    borderWidth: 1
                }]
            }
        });

        // Spending Trends Chart
        const spendingTrendsCtx = document.getElementById('spendingTrendsChart').getContext('2d');
        const spendingTrendsChart = new Chart(spendingTrendsCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_map(function($t) { return $t['TranDate']; }, $categorizedTransactions)); ?>,
                datasets: [{
                    label: 'Expenses',
                    data: <?php echo json_encode(array_map(function($t) { return $t['TranAmount']; }, $categorizedTransactions)); ?>,
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
    
</body>
</html>
