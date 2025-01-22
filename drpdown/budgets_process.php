<?php
include '../config.php';
session_start();

// Get the logged-in user's ID from the session
$userId = $_SESSION['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $title = $_POST['title'];
        $category = $_POST['category'];
        $amount = $_POST['amount'];
        $start = $_POST['start_date'];
        $end = $_POST['end_date'];
        $budgetId = $_POST['budget_id'] ?? null;

        if ($budgetId) {
            $sql = "UPDATE Budgets SET BudgetTitle=?, BudgetCat=?, BudgetAmt=?, BudgetStart=?, BudgetEnd=? WHERE BudgetID=? AND UserID=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssdssii", $title, $category, $amount, $start, $end, $budgetId, $userId);
        } else {
            $sql = "INSERT INTO Budgets (UserID, BudgetTitle, BudgetCat, BudgetAmt, BudgetStart, BudgetEnd) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("issdss", $userId, $title, $category, $amount, $start, $end);
        }
        $stmt->execute();
        echo json_encode(["success" => "Budget saved successfully!"]);
        $stmt->close();
    } elseif ($action === 'delete') {
        $budgetId = $_POST['budget_id'];
        $sql = "DELETE FROM Budgets WHERE BudgetID = ? AND UserID = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $budgetId, $userId);
        $stmt->execute();
        echo json_encode(["success" => "Budget deleted successfully!"]);
        $stmt->close();
    }
    exit();
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $sql = "SELECT * FROM Budgets WHERE UserID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $budgets = [];

    while ($budget = $result->fetch_assoc()) {
        // Calculate utilized amount for this budget
        $utilization_sql = "
            SELECT SUM(TranAmount) AS UtilizedAmount
            FROM Transactions
            WHERE UserID = ? AND TranCat = ? AND TranDate BETWEEN ? AND ?";
        $util_stmt = $conn->prepare($utilization_sql);
        $util_stmt->bind_param(
            "isss",
            $userId,
            $budget['BudgetCat'],
            $budget['BudgetStart'],
            $budget['BudgetEnd']
        );
        $util_stmt->execute();
        $util_result = $util_stmt->get_result()->fetch_assoc();
        $utilized_amount = $util_result['UtilizedAmount'] ?? 0;

        // Calculate remaining budget and days
        $remaining_budget = max($budget['BudgetAmt'] - $utilized_amount, 0);
        $current_date = new DateTime();
        $end_date = new DateTime($budget['BudgetEnd']);
        $days_remaining = max($end_date->diff($current_date)->days, 0);

        // Calculate daily spending limit
        $daily_limit = $days_remaining > 0 ? ($remaining_budget / $days_remaining) : 0;

        // Determine budget status
        $status = $remaining_budget <= 0 ? "Exceeded" : "Within Budget";

        // Add details to the budget
        $budget['UtilizedAmount'] = $utilized_amount;
        $budget['RemainingBudget'] = $remaining_budget;
        $budget['DaysRemaining'] = $days_remaining;
        $budget['DailyLimit'] = $daily_limit;
        $budget['Status'] = $status;

        $budgets[] = $budget;

        $util_stmt->close();
    }

    echo json_encode($budgets);
    $stmt->close();
    exit();
}

?>