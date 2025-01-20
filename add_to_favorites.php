<?php
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $resourceID = $input['resourceID'] ?? null;

    if (!isset($_SESSION['id']) || !$resourceID) {
        echo json_encode(['success' => false, 'message' => 'Invalid input or user not logged in.']);
        exit;
    }

    $userID = $_SESSION['id'];

    // Check for duplicate entry
    $checkStmt = $conn->prepare("SELECT * FROM Favorites WHERE UserID = ? AND ResourceID = ?");
    $checkStmt->bind_param('ii', $userID, $resourceID);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if ($checkResult->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Already added to favorites.']);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO Favorites (UserID, ResourceID) VALUES (?, ?)");
    $stmt->bind_param('ii', $userID, $resourceID);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
    }

    $stmt->close();
    $conn->close();
}
