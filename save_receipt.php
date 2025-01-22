<?php
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['id'];
    $data = json_decode(file_get_contents('php://input'), true);

    if (isset($data['amount'])) {
        $amount = floatval($data['amount']);
        $date = date('Y-m-d');

        $sql = "INSERT INTO Transactions (UserID, TranType, TranTitle, TranAmount, TranDate) VALUES (?, 'Expense', 'Scanned Receipt', ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ids", $user_id, $amount, $date);

        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Database error.']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid data.']);
    }
}
?>
