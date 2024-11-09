<?php
include '../config.php';
$userId = 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $title = $_POST['title'];
        $category = $_POST['category'];
        $type = $_POST['type'];
        $amount = $_POST['amount'];
        $date = $_POST['date'];
        $tranId = $_POST['tran_id'] ?? null;

        if ($tranId) {
            $sql = "UPDATE Transactions SET TranTitle=?, TranCat=?, TranType=?, TranAmount=?, TranDate=? WHERE TranID=? AND UserID=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssdsii", $title, $category, $type, $amount, $date, $tranId, $userId);
        } else {
            $sql = "INSERT INTO Transactions (UserID, TranType, TranTitle, TranCat, TranAmount, TranDate) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("isssds", $userId, $type, $title, $category, $amount, $date);
        }
        $stmt->execute();
        echo json_encode(["success" => "Transaction saved successfully!"]);
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
    $sql = "SELECT * FROM Transactions WHERE UserID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $transactions = $result->fetch_all(MYSQLI_ASSOC);
    echo json_encode($transactions);
    $stmt->close();
    exit();
}
?>
