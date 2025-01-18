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

// Fetch transaction data based on date range
$date_filter = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $date_filter = "AND TranDate BETWEEN '$start_date' AND '$end_date'";
} else {
    // Default: last 30 days
    $start_date = date('Y-m-d', strtotime('-30 days'));
    $end_date = date('Y-m-d');
    $date_filter = "AND TranDate BETWEEN '$start_date' AND '$end_date'";
}

// Fetch transaction trends
$sql = "SELECT TranDate, TranAmount, TranType, TranCat 
        FROM Transactions 
        WHERE UserID = ? $date_filter
        ORDER BY TranDate";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$transaction_data = $stmt->get_result();

// Prepare data for charts
$transaction_trends = [];
$category_breakdown = [];
while ($row = $transaction_data->fetch_assoc()) {
    $date = $row['TranDate'];
    $amount = $row['TranAmount'];
    $type = $row['TranType'];
    $category = $row['TranCat'];

    // Transaction trends data
    $transaction_trends[$date][$type] = ($transaction_trends[$date][$type] ?? 0) + $amount;

    // Category breakdown data
    $category_breakdown[$category] = ($category_breakdown[$category] ?? 0) + $amount;
}

// Prepare savings goal progress
$sql = "SELECT SavingsTitle, SavingsAmt, CurrentSavings 
        FROM Savings 
        WHERE UserID = ?";
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

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report - MoneyCraft</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Inter', sans-serif;
        }
        .card {
            border-radius: 20px;
            border: none;
        }
    </style>
</head>
<body>
<?php include '../navbar.php'; ?>

<div class="container mt-4">
    <h1 class="text-left mb-4">📊 Your Reports</h1>

    <!-- Date Range Filter -->
    <form method="POST" class="mb-4">
        <div class="row g-3">
            <div class="col-md-5">
                <label for="start_date" class="form-label">Start Date</label>
                <input type="date" name="start_date" class="form-control" value="<?php echo htmlspecialchars($start_date); ?>">
            </div>
            <div class="col-md-5">
                <label for="end_date" class="form-label">End Date</label>
                <input type="date" name="end_date" class="form-control" value="<?php echo htmlspecialchars($end_date); ?>">
            </div>
            <div class="col-md-2 align-self-end">
                <button type="submit" class="btn btn-primary w-100">Filter</button>
            </div>
        </div>
    </form>

    <div class="row">
        <!-- Transaction Trends -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Transaction Trends</h4>
                    <div id="transactionTrendsChart"></div>
                </div>
            </div>
        </div>

        <!-- Category Breakdown -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Category Breakdown</h4>
                    <div id="categoryBreakdownChart"></div>
                </div>
            </div>
        </div>

        <!-- Savings Goal Progress -->
        <div class="col-md-12 mb-4">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Savings Goals Progress</h4>
                    <div class="row">
                        <?php foreach ($savings_progress as $goal): ?>
                            <div class="col-md-4 mb-3">
                                <h5><?php echo htmlspecialchars($goal['title']); ?></h5>
                                <p>Target: RM<?php echo number_format($goal['target'], 2); ?></p>
                                <p>Saved: RM<?php echo number_format($goal['saved'], 2); ?></p>
                                <p>Progress: <?php echo $goal['progress']; ?>%</p>
                                <div class="progress">
                                    <div class="progress-bar" role="progressbar" style="width: <?php echo $goal['progress']; ?>%;" aria-valuenow="<?php echo $goal['progress']; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Export Options -->
    <div class="text-end">
        <button class="btn btn-secondary" onclick="window.print()">Print Report</button>
    </div>
</div>

<!-- ApexCharts Configuration -->
<script>
    // Transaction Trends Chart
    var transactionTrendsOptions = {
        chart: {
            type: 'line',
            height: 350
        },
        series: [
            { name: 'Income', data: <?php echo json_encode(array_column($transaction_trends, 'Income', 'date')); ?> },
            { name: 'Expenses', data: <?php echo json_encode(array_column($transaction_trends, 'Expense', 'date')); ?> }
        ],
        xaxis: { categories: <?php echo json_encode(array_keys($transaction_trends)); ?> }
    };
    var transactionTrendsChart = new ApexCharts(document.querySelector("#transactionTrendsChart"), transactionTrendsOptions);
    transactionTrendsChart.render();

    // Category Breakdown Chart
    var categoryBreakdownOptions = {
        chart: {
            type: 'pie',
            height: 350
        },
        series: <?php echo json_encode(array_values($category_breakdown)); ?>,
        labels: <?php echo json_encode(array_keys($category_breakdown)); ?>
    };
    var categoryBreakdownChart = new ApexCharts(document.querySelector("#categoryBreakdownChart"), categoryBreakdownOptions);
    categoryBreakdownChart.render();
</script>
</body>
</html>
