<?php
include '../config.php'; // Include the MySQLi connection

$userId = 1; // Example UserID (replace with session logic if needed)

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $title = $_POST['title'];
        $type = $_POST['type'];
        $amount = $_POST['amount'];
        $date = $_POST['date'];

        $sql = "INSERT INTO transactions (UserID, TranType, TranTitle, TranAmount, TranDate) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);

        if ($stmt) {
            $stmt->bind_param("issds", $userId, $type, $title, $amount, $date);
            if ($stmt->execute()) {
                echo json_encode(["success" => "Transaction saved successfully!"]);
            } else {
                echo json_encode(["error" => "Failed to save transaction: " . $stmt->error]);
            }
            $stmt->close();
        } else {
            echo json_encode(["error" => "Failed to prepare SQL query."]);
        }
        exit();
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';

    if ($action === 'fetch') {
        $sql = "SELECT * FROM transactions WHERE UserID = ? ORDER BY TranDate DESC";
        $stmt = $conn->prepare($sql);

        if ($stmt) {
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();

            $transactions = $result->fetch_all(MYSQLI_ASSOC);
            header('Content-Type: application/json');
            echo json_encode($transactions);
            $stmt->close();
        } else {
            echo json_encode(['error' => 'Failed to prepare SQL query.']);
        }
        exit();
    }

    if ($action === 'delete' && isset($_GET['TranID'])) {
        $tranID = $_GET['TranID'];

        $sql = "DELETE FROM transactions WHERE TranID = ?";
        $stmt = $conn->prepare($sql);

        if ($stmt) {
            $stmt->bind_param("i", $tranID);
            if ($stmt->execute()) {
                echo json_encode(["success" => "Transaction deleted successfully!"]);
            } else {
                echo json_encode(["error" => "Failed to delete transaction: " . $stmt->error]);
            }
            $stmt->close();
        } else {
            echo json_encode(["error" => "Failed to prepare SQL query."]);
        }
        exit();
    }
}
?>
