<?php
include '../config.php';
session_start();

// Get the logged-in user's ID from the session
$userId = $_SESSION['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
if ($action === 'save') {
    // Default values for scanned receipts
    $title = $_POST['title'] ?? 'Scanned Receipt'; // Default title
    $category = $_POST['category'] ?? 'Uncategorized'; // Default category
    $type = $_POST['type'] ?? 'Expense'; // Default type
    $amount = $_POST['amount'] ?? 0; // Extracted amount
    $date = $_POST['date'] ?? date('Y-m-d'); // Default to today's date
    $tranId = $_POST['tran_id'] ?? null;

    // Validate the amount
    if ($amount <= 0) {
        echo json_encode(["error" => "Invalid amount provided"]);
        exit();
    }

    // Insert or update the transaction
    if ($tranId) {
        $sql = "UPDATE Transactions SET TranTitle=?, TranCat=?, TranType=?, TranAmount=?, TranDate=? WHERE TranID=? AND UserID=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssdsii", $title, $category, $type, $amount, $date, $tranId, $userId);
    } else {
        $sql = "INSERT INTO Transactions (UserID, TranType, TranTitle, TranCat, TranAmount, TranDate) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isssds", $userId, $type, $title, $category, $amount, $date);
    }

    // Execute and respond
    if ($stmt->execute()) {
        echo json_encode(["success" => "Transaction saved successfully!"]);
    } else {
        echo json_encode(["error" => "Failed to save transaction"]);
    }
    $stmt->close();


    } elseif ($action === 'delete') {
        $tranId = $_POST['tran_id'] ?? null;
        if ($tranId) {
            $sql = "DELETE FROM Transactions WHERE TranID = ? AND UserID = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $tranId, $userId);
            $stmt->execute();
            echo json_encode(["success" => "Transaction deleted successfully!"]);
            $stmt->close();
        } else {
            echo json_encode(["error" => "Invalid transaction ID"]);
        }
    }
    exit();
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Example filter logic
    $dateFilter = isset($_GET['date']) ? $_GET['date'] : null;

    $sql = "SELECT TranID, TranTitle, TranType, TranAmount, TranDate 
    FROM Transactions 
    WHERE UserID = ?";
if ($dateFilter) {
$sql .= " AND DATE(TranDate) = ?";
}
$sql .= " ORDER BY TranDate DESC"; // Ensure latest transactions come first

$stmt = $conn->prepare($sql);
if ($dateFilter) {
$stmt->bind_param("is", $userId, $dateFilter);
} else {
$stmt->bind_param("i", $userId);
}

    $stmt->execute();
    $result = $stmt->get_result();

    $transactions = [];
    while ($row = $result->fetch_assoc()) {
        $transactions[] = $row;
    }
    echo json_encode($transactions);
    $stmt->close();
    exit();
}

?>