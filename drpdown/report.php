<?php
// Start the session
session_start();

// Include config file
require_once '../config.php';

// Check if the user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch financial data
$transactions = [];
$savings = [];
$bills = [];
$budgets = [];

// Fetch transactions
$sql = "SELECT * FROM Transactions WHERE UserID = ?";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $user_id);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $transactions[] = $row;
        }
    }
    $stmt->close();
}

// Fetch savings
$sql = "SELECT * FROM Savings WHERE UserID = ?";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $user_id);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $savings[] = $row;
        }
    }
    $stmt->close();
}

// Fetch bills
$sql = "SELECT * FROM Bills WHERE UserID = ?";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $user_id);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $bills[] = $row;
        }
    }
    $stmt->close();
}

// Fetch budgets
$sql = "SELECT * FROM Budgets WHERE UserID = ?";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $user_id);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $budgets[] = $row;
        }
    }
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Reports</title>
    <link rel="stylesheet" href="../css/navbar.css">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f9f9f9;
            padding: 20px;
        }
        .report-container {
            max-width: 800px;
            margin: auto;
        }
        .report-section {
            background-color: #fff;
            margin-bottom: 20px;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .report-section h3 {
            color: #4a148c;
            margin-bottom: 10px;
        }
        .report-section p {
            margin-bottom: 5px;
            color: #1b1b1b;
        }
        .chart {
            width: 100%;
            height: 300px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <?php include '../navbar.php'; ?>
    <div class="report-container">
        <h2>My Reports</h2>
        <div class="report-section">
            <h3>Expense Report</h3>
            <canvas id="expenseChart" class="chart"></canvas>
        </div>
        <div class="report-section">
            <h3>Categories</h3>
            <ul>
                <li>Groceries: 25%</li>
                <li>Transport: 72%</li>
                <li>Shopping: 50%</li>
                <!-- Add more categories as needed -->
            </ul>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const ctx = document.getElementById('expenseChart').getContext('2d');
        const expenseChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                datasets: [{
                    label: 'Expenses',
                    data: [100, 200, 150, 300, 250, 400, 350, 300, 450, 500, 550, 600], // Example data
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
