<?php
require_once 'config.php';
session_start();

$userId = $_SESSION['user_id'];

$sql = "SELECT ResourceID FROM Favorites WHERE UserID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $userId);
$stmt->execute();
$result = $stmt->get_result();

$favorites = [];
while ($row = $result->fetch_assoc()) {
    $favorites[] = $row['ResourceID'];
}

$stmt->close();
$conn->close();

echo json_encode($favorites);
?>
