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

// Fetch the logged-in user's username
$sql = "SELECT Username FROM Users WHERE UserID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$users = $result->fetch_assoc();
$username = $users['Username'] ?? 'User'; // Default to 'User' if username is not found

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

// Fetch cash flow data by month (Income and Expenses)
$sql = "SELECT 
            DATE_FORMAT(TranDate, '%Y-%m') AS month,
            SUM(CASE WHEN TranType = 'Income' THEN TranAmount ELSE 0 END) AS total_income,
            SUM(CASE WHEN TranType = 'Expense' THEN TranAmount ELSE 0 END) AS total_expenses
        FROM Transactions
        WHERE UserID = ?
        GROUP BY month
        ORDER BY month";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$cash_flow_trends = $stmt->get_result();

$cash_flow_labels = [];
$income_data = [];
$expense_data = [];

while ($row = $cash_flow_trends->fetch_assoc()) {
    $cash_flow_labels[] = $row['month'];
    $income_data[] = $row['total_income'];
    $expense_data[] = $row['total_expenses'];
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


// Fetch latest financial education video
$sql = "SELECT ResourceTitle, ResourceLink 
        FROM FinancialEducationResources
        ORDER BY ContentDate DESC
        LIMIT 1";
$latest_resource = $conn->query($sql)->fetch_assoc();

// Extract YouTube video ID
function getYouTubeID($url) {
    if (preg_match('/(?:https?:\/\/)?(?:www\.)?youtu\.be\/([a-zA-Z0-9_-]+)|(?:https?:\/\/)?(?:www\.)?youtube\.com\/watch\?v=([a-zA-Z0-9_-]+)/', $url, $matches)) {
        return $matches[1] ?? $matches[2];
    }
    return null;
}

$latest_video_id = getYouTubeID($latest_resource['ResourceLink'] ?? '');
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
    <!--Modern Font-->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
      body {
    background-color: #C5C5C5; /* Set the page background color */
    color: #161925;
    font-family: 'Inter', sans-serif;
}

h1.text-left {
    font-weight: 900; /* Make it bold */
    color: rgb(0, 35, 72); /* Keep your custom color */
}


.card.current-balance-card {
    background-color: black;
    color: white;
    border-radius: 20px;
    border: none;
}

.card:not(.current-balance-card) {
    background-color: #FFFFFF;
    color: #161925;
    border-radius: 20px;
    border: none;
}

.btn-primary {
    background-color: #FFD000;
    border: none;
    color: #161925;
}

.btn-primary:hover {
    background-color: #FDF09D;
    color: #161925;
}

.circular-progress-container {
    position: relative;
    width: 100px;
    height: 100px;
}

.circular-progress-container svg {
    transform: rotate(-90deg); /* Rotate to make the starting point top */
}

.circular-progress-container circle {
    transition: stroke-dasharray 0.5s ease;
}

.circular-progress-container p {
    position: absolute;
    top: 35%;
    left: 50%;
    transform: translateX(-50%);
    margin: 0;
    font-weight: bold;
    color: #161925;
}

/* New Styles for the bottom section (Savings Goal & Financial Education Video) */
.row.savings-video-row {
    display: flex;
    justify-content: space-between;
    gap: 20px;
    margin-top: 30px;
}

.card.savings-goal-card,
.card.video-card {
    border-radius: 20px;
    background-color: #FFFFFF;
    color: #161925;
    flex: 1;
}

.card.savings-goal-card {
    /* Ensure it stays the same size */
    max-width: 48%;
}

.card.video-card {
    /* Make this card smaller */
    max-width: 48%;
}

.card-title {
    font-weight: bold;
}

.card-body {
    padding: 20px;
}

/* Optional: Adjust the video iframe to fit the card */
.card.video-card iframe {
    border-radius: 20px;
    width: 100%;
    height: 200px;
}

.card-body h5 {
    font-size: 1.1rem;
    margin-top: 10px;
    color: rgb(0, 12, 12);
}

.card-body p {
    font-size: 1rem;
}

/* Responsive: Make sure layout adapts well on smaller screens */
@media (max-width: 768px) {
    .row.savings-video-row {
        flex-direction: column;
        gap: 20px;
    }

    .card.savings-goal-card,
    .card.video-card {
        max-width: 100%;
    }

    .card.video-card iframe {
        height: 180px; /* Adjust the iframe height for small screens */
    }
}

        
    </style>

</head>
<body class="bg-light">
    <?php include 'navbar.php'; ?>

  <!-- Welcome back message -->
<div class="container mt-2"> 
    <h1 class="text-left" style="margin-top: 10px; color:rgb(0, 35, 72);">👋 Welcome back, <?php echo htmlspecialchars($username); ?>!</h1>

    <div class="row">
            <!-- Cash Flow Chart -->
<div class="col-md-6 mb-4">
    <div class="card">
        <div class="card-body">
            <h4 class="card-title">Cash Flow</h4>
            <div id="cashFlowChart" style="height: 350px;"></div>
        </div>
    </div>
</div>


            <!-- Budget Breakdown -->
            <div class="col-md-6 mb-4">
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="card-title">Budget Breakdown</h4>
                <!-- Shortcut Button -->
                <a href="drpdown/budgets.php" class="btn" style="background-color: #FFDD00; color: #161925; font-weight: bold; border-radius: 15px;">
                    + Manage Budgets
                </a>
            </div>
            <div id="budgetChart" style="height: 350px;"></div>
        </div>
    </div>
</div>

    <!-- current balance -->

<div class="col-md-4 mb-4">
    <div class="card current-balance-card">
        <div class="card-body">
            <h4 class="card-title">
                <i class="fas fa-wallet" style="margin-right: 10px; color: #FFD000;"></i>
                Current Balance
            </h4>
            <p><i class="fas fa-credit-card" style="color: #FFD000;"></i> Total Income: RM<?php echo number_format($total_income, 2); ?></p>
            <p><i class="fas fa-money-bill-wave" style="color: #FFD000;"></i> Total Expenses: RM<?php echo number_format($total_expenses, 2); ?></p>
            <p><i class="fas fa-balance-scale" style="color: #FFD000;"></i> Current Balance: RM<?php echo number_format($current_balance, 2); ?></p>
        </div>
    </div>
</div>


<div class="col-md-8 mb-4">
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
                <!-- Bell Icon beside Upcoming Bills title -->
                <h4 class="card-title">
                    <i class="fas fa-bell" style="color: #FFDD00; margin-right: 10px;"></i>
                    Upcoming Bills
                </h4>
                <!-- Shortcut Button -->
                <a href="drpdown/bills.php" class="btn" style="background-color: #FFDD00; color: #161925; font-weight: bold; border-radius: 15px;">
                    + Manage Bills
                </a>
            </div>
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

<div class="col-md-12 mb-4">
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
                <!-- Add icon beside Recent Transactions title -->
                <h4 class="card-title">
                    <i class="fas fa-exchange-alt" style="color: #FFDD00; margin-right: 10px;"></i> <!-- Transaction icon -->
                    Recent Transactions
                </h4>
                <!-- Shortcut Button -->
                <a href="drpdown/transactions.php" class="btn" style="background-color: #FFDD00; color: #161925; font-weight: bold; border-radius: 15px;">
                    + Add Transaction
                </a>
            </div>
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

<div class="row justify-content-center mb-4" style="margin-top: 10px;">
    <!-- Financial Education Video Card -->
    <div class="col-md-6">
        <div class="card" style="border-radius: 20px; background-color:#FFFFFF; color: #FFFFFF;">
            <div class="card-body text-center">
                <h4 class="card-title" style="color:rgb(0, 0, 0); font-size: 18px;">🔔 Watch the Latest Financial Education Video!</h4>
                <?php if ($latest_video_id): ?>
                    <div style="position: relative; padding-bottom: 51%; height: 0; overflow: hidden; border-radius: 20px;">
                        <iframe 
                            src="https://www.youtube.com/embed/<?php echo $latest_video_id; ?>" 
                            frameborder="0" 
                            allowfullscreen 
                            style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; border-radius: 20px;">
                        </iframe>
                    </div>
                    <!-- Button with arrow to go to the resource -->
                    <a href="<?php echo $latest_resource['ResourceLink']; ?>" 
                       target="_blank" 
                       class="btn btn-primary mt-3" 
                       style="background-color: #FFD000; color: #161925; font-weight: bold; border-radius: 15px;">
                        <i class="fas fa-arrow-right" style="margin-right: 10px;"></i> 
                        Go to <?php echo htmlspecialchars($latest_resource['ResourceTitle']); ?>
                    </a>
                <?php else: ?>
                    <p style="color: #dc3545;">No videos available at the moment.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Savings Goals Card -->
    <div class="col-md-6">
        <div class="card" style="border-radius: 20px; background-color:#FFFFFF; color: #161925;">
            <div class="card-body">
                <h4 class="card-title" style="font-size: 18px;">
                    <i class="fas fa-dart" style="color: #FFD000; margin-right: 10px;"></i>
                    Savings Goals
                </h4>
                <div class="row">
                    <?php 
                    // Fetch savings goals for the logged-in user
                    $sql = "SELECT SavingsTitle, SavingsAmt, CurrentSavings, TargetDate 
                            FROM Savings 
                            WHERE UserID = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $savings_goals = $stmt->get_result();

                    if ($savings_goals->num_rows > 0):
                        while ($savings = $savings_goals->fetch_assoc()): 
                    ?>
                    <div class="col-md-12 mb-3">
                        <div class="d-flex align-items-center">
                            <div style="flex: 1;">
                                <h5 style="margin: 0;"><?php echo htmlspecialchars($savings['SavingsTitle']); ?></h5>
                                <p style="margin: 0;">Target: RM<?php echo number_format($savings['SavingsAmt'], 2); ?></p>
                                <p style="margin: 0;">Saved: RM<?php echo number_format($savings['CurrentSavings'], 2); ?></p>
                                <p style="margin: 0;">Target Date: <?php echo htmlspecialchars($savings['TargetDate']); ?></p>
                            </div>

                            <!-- Circular Progress Bar -->
                            <div style="flex-basis: 150px; text-align: center;">
                                <!-- Circular progress bar using CSS -->
                                <div class="circular-progress-container">
                                    <svg width="100" height="100">
                                        <circle cx="50" cy="50" r="45" stroke="#e6e6e6" stroke-width="10" fill="none" />
                                        <circle cx="50" cy="50" r="45" stroke="#FFD000" stroke-width="10" fill="none" 
                                                stroke-dasharray="<?php echo ($savings['CurrentSavings'] / $savings['SavingsAmt']) * 282; ?>" 
                                                stroke-dashoffset="0" style="transition: stroke-dasharray 0.5s ease;"></circle>
                                    </svg>
                                    <p><?php echo round(($savings['CurrentSavings'] / $savings['SavingsAmt']) * 100, 2); ?>%</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php 
                        endwhile;
                    else: 
                    ?>
                    <p>No savings goals added yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
``



    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <!-- ApexCharts Configuration -->
    <script>

       // Cash Flow Chart
var cashFlowOptions = {
    chart: {
        type: 'line',
        height: 350
    },
    series: [{
        name: 'Income',
        data: <?php echo json_encode($income_data); ?>
    }, {
        name: 'Expenses',
        data: <?php echo json_encode($expense_data); ?>
    }],
    xaxis: {
        categories: <?php echo json_encode($cash_flow_labels); ?>
    },
    colors: ['#00FF00', '#FF0000'],  // Green for Income and Red for Expenses
   
    markers: {
        size: 5
    },
    stroke: {
        width: 2
    },
    tooltip: {
        shared: true,
        intersect: false
    }
};

var cashFlowChart = new ApexCharts(document.querySelector("#cashFlowChart"), cashFlowOptions);
cashFlowChart.render();

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