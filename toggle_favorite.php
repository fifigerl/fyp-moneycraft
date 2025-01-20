<?php
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $resourceID = $input['resourceID'] ?? null;
    $isFavorite = $input['isFavorite'] ?? false;

    if (!isset($_SESSION['id']) || !$resourceID) {
        echo json_encode(['success' => false, 'message' => 'Invalid input or user not logged in.']);
        exit;
    }

    $userID = $_SESSION['id'];

    if ($isFavorite) {
        // Remove from favorites
        $stmt = $conn->prepare("DELETE FROM Favorites WHERE UserID = ? AND ResourceID = ?");
        $stmt->bind_param('ii', $userID, $resourceID);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Removed from favorites.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to remove from favorites.']);
        }
        $stmt->close();
    } else {
        // Add to favorites
        $stmt = $conn->prepare("INSERT INTO Favorites (UserID, ResourceID) VALUES (?, ?)");
        $stmt->bind_param('ii', $userID, $resourceID);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Added to favorites!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to add to favorites.']);
        }
        $stmt->close();
    }

    $conn->close();
}
?>
