<?php
// Initialize the session
session_start();

// Check if the user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Include config file
require_once '../config.php';

// Get the logged-in user's ID
$user_id = $_SESSION['id'];

// Fetch report filters
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Transaction Trends and Category Breakdown
$sql = "SELECT TranDate, TranAmount, TranType, TranCat 
        FROM Transactions 
        WHERE UserID = ? AND TranDate BETWEEN ? AND ?
        ORDER BY TranDate";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iss", $user_id, $start_date, $end_date);
$stmt->execute();
$transaction_data = $stmt->get_result();

$transaction_trends = [];
$category_breakdown = [];
$total_spent = 0;
$max_category = "";
$max_category_amount = 0;

while ($row = $transaction_data->fetch_assoc()) {
    $date = $row['TranDate'];
    $amount = $row['TranAmount'];
    $type = $row['TranType'];
    $category = $row['TranCat'];

    $transaction_trends[$date][$type] = ($transaction_trends[$date][$type] ?? 0) + $amount;

    if ($type === 'Expense') {
        $category_breakdown[$category] = ($category_breakdown[$category] ?? 0) + $amount;
        $total_spent += $amount;

        if ($category_breakdown[$category] > $max_category_amount) {
            $max_category_amount = $category_breakdown[$category];
            $max_category = $category;
        }
    }
}

// Savings Progress
$sql = "SELECT SavingsTitle, SavingsAmt, CurrentSavings FROM Savings WHERE UserID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$savings_goals = $stmt->get_result();

$savings_progress = [];
while ($row = $savings_goals->fetch_assoc()) {
    $progress = round(($row['CurrentSavings'] / $row['SavingsAmt']) * 100, 2);
    $savings_progress[] = [
        'title' => $row['SavingsTitle'],
        'target' => $row['SavingsAmt'],
        'saved' => $row['CurrentSavings'],
        'progress' => $progress
    ];
}

// Upcoming Bills
$sql = "SELECT BillTitle, BillDue, BillAmt, Paid 
        FROM BillsReminder 
        WHERE UserID = ? AND Paid = 0 
        ORDER BY BillDue ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$bills = $stmt->get_result();

// Budget Overview
$sql = "SELECT BudgetTitle, BudgetAmt, 
        (SELECT SUM(TranAmount) FROM Transactions WHERE BudgetID = Budgets.BudgetID) AS Spent
        FROM Budgets 
        WHERE UserID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$budget_data = $stmt->get_result();

$budget_analysis = [];
while ($row = $budget_data->fetch_assoc()) {
    $utilized = round(($row['Spent'] / $row['BudgetAmt']) * 100, 2);
    $budget_analysis[] = [
        'title' => $row['BudgetTitle'],
        'amount' => $row['BudgetAmt'],
        'spent' => $row['Spent'],
        'utilized' => $utilized
    ];
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detailed Report - MoneyCraft</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Inter', sans-serif;
        }
        h1 {
            font-weight: bold;
            color: #002348;
        }
        .card {
            border-radius: 15px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border: none;
            margin-bottom: 20px;
        }
        .card h4 {
            color: #002348;
        }
        .btn-primary {
            background-color: #FFD000;
            border: none;
            border-radius: 10px;
        }
        .btn-primary:hover {
            background-color: #FDF09D;
        }
        .insights {
            background-color: #fff3cd;
            padding: 20px;
            border-left: 5px solid #ffc107;
            border-radius: 10px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
<div class="container mt-4">
    <h1 class="mb-4"> Detailed Report</h1>

    <!-- Insights Section -->
    <div class="insights">
        <h5><i class="fas fa-lightbulb"></i> Insights</h5>
        <p><strong>Most Spent On:</strong> Your highest spending category for the selected period is 
            <strong><?php echo htmlspecialchars($max_category); ?></strong>, with a total expense of 
            <strong>RM<?php echo number_format($max_category_amount, 2); ?></strong>. Consider reviewing your expenses in this category to identify potential savings.</p>
        <p><strong>Total Expenses:</strong> Over the selected period, your total expenses amounted to 
            <strong>RM<?php echo number_format($total_spent, 2); ?></strong>. Comparing this with previous months can help you track whether your spending is increasing or decreasing.</p>
        <?php if (!empty($savings_progress)): ?>
            <p><strong>Savings Tip:</strong> Your savings goal progress shows that you are 
                <strong><?php echo end($savings_progress)['progress']; ?>%</strong> towards achieving your target of 
                RM<?php echo number_format(end($savings_progress)['target'], 2); ?> for 
                "<?php echo htmlspecialchars(end($savings_progress)['title']); ?>". Keep up the consistent savings!</p>
        <?php endif; ?>
        <?php if (!empty($bills)): ?>
            <p><strong>Upcoming Bills:</strong> You have <?php echo $bills->num_rows; ?> unpaid bills. Make sure to allocate funds for them to avoid late fees.</p>
        <?php endif; ?>
    </div>

    <!-- Transaction Trends -->
    <div class="card">
        <div class="card-body">
            <h4>Transaction Trends</h4>
            <div id="transactionTrendsChart"></div>
        </div>
    </div>

    <!-- Category Breakdown -->
    <div class="card">
        <div class="card-body">
            <h4>Category Breakdown</h4>
            <div id="categoryBreakdownChart"></div>
        </div>
    </div>

    <!-- Savings Progress -->
    <div class="card">
        <div class="card-body">
            <h4>Savings Progress</h4>
            <div id="savingsProgressChart"></div>
        </div>
    </div>

    <!-- Budget Overview -->
    <div class="card">
        <div class="card-body">
            <h4>Budget Overview</h4>
            <div id="budgetOverviewChart"></div>
        </div>
    </div>

    <!-- Bills Reminder -->
    <div class="card">
        <div class="card-body">
            <h4>Upcoming Bills</h4>
            <ul>
                <?php while ($bill = $bills->fetch_assoc()): ?>
                    <li><?php echo "{$bill['BillTitle']} - Due: {$bill['BillDue']} - Amount: RM{$bill['BillAmt']}"; ?></li>
                <?php endwhile; ?>
            </ul>
        </div>
    </div>

    <!-- Print Button -->
    <button class="btn btn-primary" onclick="window.print()">Print Report</button>
</div>

<script>
    // Transaction Trends Chart
    var transactionTrendsChart = new ApexCharts(document.querySelector("#transactionTrendsChart"), {
        chart: { type: 'line', height: 350, zoom: { enabled: true } },
        series: [
            { name: 'Income', data: <?php echo json_encode(array_values(array_column($transaction_trends, 'Income'))); ?> },
            { name: 'Expenses', data: <?php echo json_encode(array_values(array_column($transaction_trends, 'Expense'))); ?> }
        ],
        xaxis: { categories: <?php echo json_encode(array_keys($transaction_trends)); ?> }
    });
    transactionTrendsChart.render();

    // Category Breakdown Chart
    var categoryBreakdownChart = new ApexCharts(document.querySelector("#categoryBreakdownChart"), {
        chart: { type: 'pie', height: 350 },
        series: <?php echo json_encode(array_values($category_breakdown)); ?>,
        labels: <?php echo json_encode(array_keys($category_breakdown)); ?>
    });
    categoryBreakdownChart.render();

    // Savings Progress Chart
    var savingsProgressChart = new ApexCharts(document.querySelector("#savingsProgressChart"), {
        chart: { type: 'bar', height: 350 },
        series: [{ name: 'Progress', data: <?php echo json_encode(array_column($savings_progress, 'progress')); ?> }],
        xaxis: { categories: <?php echo json_encode(array_column($savings_progress, 'title')); ?> }
    });
    savingsProgressChart.render();

    // Budget Overview Chart
    var budgetOverviewChart = new ApexCharts(document.querySelector("#budgetOverviewChart"), {
        chart: { type: 'bar', height: 350 },
        series: [{ name: 'Utilized', data: <?php echo json_encode(array_column($budget_analysis, 'utilized')); ?> }],
        xaxis: { categories: <?php echo json_encode(array_column($budget_analysis, 'title')); ?> }
    });
    budgetOverviewChart.render();
</script>
</body>
</html>
