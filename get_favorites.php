<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['id'])) {
    echo json_encode([]);
    exit;
}

$userID = $_SESSION['id'];

$sql = "SELECT r.ResourceID, r.ResourceTitle, r.ResourceLink, r.ContentDate 
        FROM Favorites f
        JOIN FinancialEducationResources r ON f.ResourceID = r.ResourceID
        WHERE f.UserID = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $userID);
$stmt->execute();
$result = $stmt->get_result();

$favorites = [];
while ($row = $result->fetch_assoc()) {
    $favorites[] = $row;
}

echo json_encode($favorites);
$stmt->close();
$conn->close();
?>
