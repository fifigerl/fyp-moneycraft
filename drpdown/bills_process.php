<?php
include '../config.php';

$action = $_POST['action'] ?? '';

if ($action == 'create') {
    $userId = $_POST['user_id'];
    $title = $_POST['title'];
    $due = $_POST['due'];
    $frequency = $_POST['frequency'];
    $amount = $_POST['amount'];

    $stmt = $conn->prepare("INSERT INTO BillsReminder (UserID, BillTitle, BillDue, BillFrequency, BillAmt, Paid) VALUES (?, ?, ?, ?, ?, 0)");
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



} elseif ($action == 'countOverdue') {
    $userId = $_POST['user_id'];
    $today = date('Y-m-d'); // Current date

    // Query to count overdue and unpaid bills
    $stmt = $conn->prepare("
        SELECT COUNT(*) as OverdueCount 
        FROM BillsReminder 
        WHERE UserID = ? AND BillDue < ? AND Paid = 0
    ");
    $stmt->bind_param("is", $userId, $today);
    $stmt->execute();
    $result = $stmt->get_result();
    $overdueCount = $result->fetch_assoc()['OverdueCount'] ?? 0;

    echo json_encode(['overdueCount' => $overdueCount]);
 


    if ($paid) {
        // Fetch the current bill details
        $result = $conn->query("SELECT BillDue, BillFrequency FROM BillsReminder WHERE ReminderID = $reminderId");
        $bill = $result->fetch_assoc();

        $newDueDate = new DateTime($bill['BillDue']);
        switch ($bill['BillFrequency']) {
            case 'Monthly':
                $newDueDate->modify('+1 month');
                break;
            case 'Quarterly':
                $newDueDate->modify('+3 months');
                break;
            case 'Yearly':
                $newDueDate->modify('+1 year');
                break;
        }

        // Update the new due date in the database
        $stmt = $conn->prepare("UPDATE BillsReminder SET BillDue = ? WHERE ReminderID = ?");
        $newDueDateFormatted = $newDueDate->format('Y-m-d');
        $stmt->bind_param("si", $newDueDateFormatted, $reminderId);
        $stmt->execute();
    }

    echo json_encode(['success' => 'Bill payment status updated successfully']);
}

$conn->close();
