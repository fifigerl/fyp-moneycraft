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
    $budgets = $result->fetch_all(MYSQLI_ASSOC);
    echo json_encode($budgets);
    $stmt->close();
    exit();
}
?>