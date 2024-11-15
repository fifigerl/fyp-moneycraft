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

// Ensure user ID is set
$user_id = $_SESSION['id'];

// Fetch the most recent financial resource
$resource_sql = "SELECT ResourceTitle, ResourceLink FROM FinancialEducationResources ORDER BY ContentDate DESC LIMIT 1";
$resource_result = $conn->query($resource_sql);
$resource = $resource_result->fetch_assoc();
$video_id = $resource ? getYouTubeID($resource['ResourceLink']) : null;

// Fetch user account balance
$balance_sql = "SELECT SUM(TranAmount) as balance FROM Transactions WHERE UserID = ?";
$balance_stmt = $conn->prepare($balance_sql);
if ($balance_stmt) {
    $balance_stmt->bind_param("i", $user_id);
    $balance_stmt->execute();
    $balance_result = $balance_stmt->get_result();
    $balance = $balance_result->fetch_assoc()['balance'];
    $balance_stmt->close();
} else {
    die("Error preparing statement: " . $conn->error);
}

// Fetch transaction history
$transactions_sql = "SELECT * FROM Transactions WHERE UserID = ? ORDER BY TranDate DESC LIMIT 3";
$transactions_stmt = $conn->prepare($transactions_sql);
if ($transactions_stmt) {
    $transactions_stmt->bind_param("i", $user_id);
    $transactions_stmt->execute();
    $transactions_result = $transactions_stmt->get_result();
    $transactions = $transactions_result->fetch_all(MYSQLI_ASSOC);
    $transactions_stmt->close();
} else {
    die("Error preparing statement: " . $conn->error);
}

// Fetch unpaid bills
$bills_sql = "SELECT * FROM BillsReminder WHERE UserID = ? AND Paid = 0";
$bills_stmt = $conn->prepare($bills_sql);
if ($bills_stmt) {
    $bills_stmt->bind_param("i", $user_id);
    $bills_stmt->execute();
    $bills_result = $bills_stmt->get_result();
    $bills = $bills_result->fetch_all(MYSQLI_ASSOC);
    $bills_stmt->close();
} else {
    die("Error preparing statement: " . $conn->error);
}

$conn->close();

// Function to extract YouTube video ID
function getYouTubeID($url) {
    $parsed_url = parse_url($url);
    if (isset($parsed_url['query'])) {
        parse_str($parsed_url['query'], $query);
        return $query['v'] ?? null;
    }
    if (isset($parsed_url['host']) && $parsed_url['host'] === 'youtu.be') {
        return ltrim($parsed_url['path'], '/');
    }
    return null;
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
            <div class="balance">
                <h2>RM<?php echo number_format($balance, 2); ?></h2>
                <p>Account Balance</p>
            </div>
            <div class="bills">
                <h3>Unpaid Bills</h3>
                <?php foreach ($bills as $bill): ?>
                    <p><?php echo htmlspecialchars($bill['BillTitle']); ?>: RM<?php echo number_format($bill['BillAmt'], 2); ?></p>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="right-panel">
            <div class="video-section">
                <h2>Latest Resource</h2>
                <?php if ($video_id): ?>
                    <iframe width="100%" height="300" src="https://www.youtube.com/embed/<?php echo $video_id; ?>" frameborder="0" allowfullscreen></iframe>
                <?php endif; ?>
                <p><?php echo htmlspecialchars($resource['ResourceTitle']); ?></p>
            </div>
        </div>
    </div>

    <div class="transaction-history">
        <h3>Transaction History</h3>
        <?php foreach ($transactions as $transaction): ?>
            <div class="transaction-item">
                <p><?php echo htmlspecialchars($transaction['TranTitle']); ?></p>
                <span><?php echo date('H:i - F j', strtotime($transaction['TranDate'])); ?></span>
                <span class="amount">RM<?php echo number_format($transaction['TranAmount'], 2); ?></span>
            </div>
        <?php endforeach; ?>
    </div>
</body>
</html>