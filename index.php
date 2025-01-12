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

// Get the logged-in user's ID
$user_id = $_SESSION['id'];

// Fetch total income, expenses, and current balance
$sql = "SELECT 
            SUM(CASE WHEN TranType = 'Income' THEN TranAmount ELSE 0 END) AS total_income,
            SUM(CASE WHEN TranType = 'Expense' THEN TranAmount ELSE 0 END) AS total_expenses
        FROM Transactions
        WHERE UserID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();
$total_income = $data['total_income'] ?? 0;
$total_expenses = $data['total_expenses'] ?? 0;
$current_balance = $total_income - $total_expenses;

// Fetch recent transactions for the logged-in user
$sql = "SELECT TranTitle, TranType, TranAmount, TranDate 
        FROM Transactions
        WHERE UserID = ?
        ORDER BY TranDate DESC
        LIMIT 5";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recent_transactions = $stmt->get_result();

// Fetch upcoming bills for the logged-in user
$sql = "SELECT BillTitle, BillDue, BillAmt 
        FROM BillsReminder
        WHERE UserID = ? AND Paid = 0 AND BillDue >= CURDATE()
        ORDER BY BillDue ASC
        LIMIT 5";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$upcoming_bills = $stmt->get_result();

// Fetch spending trends
$sql = "SELECT DATE_FORMAT(TranDate, '%Y-%m') AS month, SUM(TranAmount) AS total_spending
        FROM Transactions
        WHERE UserID = ? AND TranType = 'Expense'
        GROUP BY month
        ORDER BY month";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$spending_trends = $stmt->get_result();
$spending_labels = [];
$spending_data = [];
while ($trend = $spending_trends->fetch_assoc()) {
    $spending_labels[] = $trend['month'];
    $spending_data[] = $trend['total_spending'];
}

// Fetch budget data
$sql = "SELECT BudgetCat, SUM(BudgetAmt) AS total_budget FROM Budgets WHERE UserID = ? GROUP BY BudgetCat";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$budgets = $stmt->get_result();
$budget_data = [];
$budget_labels = [];
while ($budget = $budgets->fetch_assoc()) {
    $budget_labels[] = $budget['BudgetCat'];
    $budget_data[] = $budget['total_budget'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MoneyCraft Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- ApexCharts -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
</head>
<body class="bg-light">
    <?php include 'navbar.php'; ?>

    <div class="container mt-4">
        <div class="row">
            <!-- Current Balance -->
            <div class="col-md-4 mb-4">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h4 class="card-title">Current Balance</h4>
                        <p><i class="fas fa-wallet"></i> Total Income: RM<?php echo number_format($total_income, 2); ?></p>
                        <p><i class="fas fa-money-bill-wave"></i> Total Expenses: RM<?php echo number_format($total_expenses, 2); ?></p>
                        <p><i class="fas fa-balance-scale"></i> Current Balance: RM<?php echo number_format($current_balance, 2); ?></p>
                    </div>
                </div>
            </div>

            <!-- Upcoming Bills -->
            <div class="col-md-8 mb-4">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Upcoming Bills</h4>
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Due Date</th>
                                    <th>Title</th>
                                    <th>Amount (RM)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($bill = $upcoming_bills->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo date('Y-m-d', strtotime($bill['BillDue'])); ?></td>
                                        <td><?php echo htmlspecialchars($bill['BillTitle']); ?></td>
                                        <td class="text-danger"><?php echo number_format($bill['BillAmt'], 2); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Recent Transactions -->
            <div class="col-md-12 mb-4">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Recent Transactions</h4>
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Title</th>
                                    <th>Type</th>
                                    <th>Amount (RM)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($transaction = $recent_transactions->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo date('Y-m-d', strtotime($transaction['TranDate'])); ?></td>
                                        <td><?php echo htmlspecialchars($transaction['TranTitle']); ?></td>
                                        <td><?php echo htmlspecialchars($transaction['TranType']); ?></td>
                                        <td class="<?php echo $transaction['TranType'] === 'Expense' ? 'text-danger' : 'text-success'; ?>">
                                            <?php echo number_format($transaction['TranAmount'], 2); ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Spending Trends -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Spending Trends</h4>
                        <div id="spendingChart" style="height: 350px;"></div>
                    </div>
                </div>
            </div>

            <!-- Budget Breakdown -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Budget Breakdown</h4>
                        <div id="budgetChart" style="height: 350px;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <!-- ApexCharts Configuration -->
    <script>
        // Spending Trends Chart
        var spendingOptions = {
            chart: {
                type: 'line',
                height: 350
            },
            series: [{
                name: 'Spending',
                data: <?php echo json_encode($spending_data); ?>
            }],
            xaxis: {
                categories: <?php echo json_encode($spending_labels); ?>
            },
            colors: ['#FF4560']
        };
        var spendingChart = new ApexCharts(document.querySelector("#spendingChart"), spendingOptions);
        spendingChart.render();

        // Budget Breakdown Chart
        var budgetOptions = {
            chart: {
                type: 'donut',
                height: 350
            },
            series: <?php echo json_encode($budget_data); ?>,
            labels: <?php echo json_encode($budget_labels); ?>
        };
        var budgetChart = new ApexCharts(document.querySelector("#budgetChart"), budgetOptions);
        budgetChart.render();
    </script>
</body>
</html>
