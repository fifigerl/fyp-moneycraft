<?php
include '../config.php';

$action = $_POST['action'] ?? '';

if ($action == 'create') {
    $userId = $_POST['user_id'];
    $title = $_POST['title'];
    $due = $_POST['due'];
    $frequency = $_POST['frequency'];
    $amount = $_POST['amount'];

    $stmt = $conn->prepare("INSERT INTO BillsReminder (UserID, BillTitle, BillDue, BillFrequency, BillAmt) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("isssd", $userId, $title, $due, $frequency, $amount);
    $stmt->execute();
    echo json_encode(['success' => 'Bill reminder created successfully']);
} elseif ($action == 'read') {
    $userId = $_POST['user_id'];
    $result = $conn->query("SELECT * FROM BillsReminder WHERE UserID = $userId");
    $bills = $result->fetch_all(MYSQLI_ASSOC);
    echo json_encode($bills);
} elseif ($action == 'update') {
    $reminderId = $_POST['reminder_id'];
    $title = $_POST['title'];
    $due = $_POST['due'];
    $frequency = $_POST['frequency'];
    $amount = $_POST['amount'];

    $stmt = $conn->prepare("UPDATE BillsReminder SET BillTitle = ?, BillDue = ?, BillFrequency = ?, BillAmt = ? WHERE ReminderID = ?");
    $stmt->bind_param("sssdi", $title, $due, $frequency, $amount, $reminderId);
    $stmt->execute();
    echo json_encode(['success' => 'Bill reminder updated successfully']);
} elseif ($action == 'delete') {
    $reminderId = $_POST['reminder_id'];

    $stmt = $conn->prepare("DELETE FROM BillsReminder WHERE ReminderID = ?");
    $stmt->bind_param("i", $reminderId);
    $stmt->execute();
    echo json_encode(['success' => 'Bill reminder deleted successfully']);
} elseif ($action == 'togglePaid') {
    $reminderId = $_POST['reminder_id'];
    $paid = $_POST['paid'] ? 1 : 0;

    $stmt = $conn->prepare("UPDATE BillsReminder SET Paid = ? WHERE ReminderID = ?");
    $stmt->bind_param("ii", $paid, $reminderId);
    $stmt->execute();
    echo json_encode(['success' => 'Bill payment status updated successfully']);
}

$conn->close();
?>