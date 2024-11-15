<?php
// Start the session
session_start();

// Include config file
require_once '../config.php';

// Check if the user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Fetch user feedbacks
$user_id = $_SESSION['user_id'];
$sql = "SELECT f.FeedbackID, f.FeedbackContent, f.FeedbackRating, f.FeedbackDate, r.ResourceTitle FROM Feedback f JOIN FinancialEducationResources r ON f.ResourceID = r.ResourceID WHERE f.UserID = ?";
$feedbacks = [];

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $user_id);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $feedbacks[] = $row;
        }
    }
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Feedbacks</title>
    <link rel="stylesheet" href="../css/navbar.css">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f9f9f9;
            padding: 20px;
        }
        .feedbacks-container {
            max-width: 800px;
            margin: auto;
        }
        .feedback-item {
            background-color: #fff;
            margin-bottom: 20px;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .feedback-item h3 {
            color: #4a148c;
            margin-bottom: 10px;
        }
        .feedback-item p {
            margin-bottom: 5px;
            color: #1b1b1b;
        }
        .feedback-item .date {
            font-size: 0.9em;
            color: #666;
        }
        .feedback-actions {
            display: flex;
            gap: 10px;
        }
        .feedback-actions button {
            background-color: #ffcc00;
            color: #1b1b1b;
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
        }
        .feedback-actions button:hover {
            background-color: #e6b800;
        }
    </style>
</head>
<body>
    <?php include '../navbar.php'; ?>
    <div class="feedbacks-container">
        <h2>Your Feedbacks</h2>
        <?php foreach ($feedbacks as $feedback): ?>
            <div class="feedback-item">
                <h3><?php echo htmlspecialchars($feedback['ResourceTitle']); ?></h3>
                <p class="date">Submitted on <?php echo date('j F Y', strtotime($feedback['FeedbackDate'])); ?></p>
                <p>Rating: <?php echo str_repeat('&#9733;', $feedback['FeedbackRating']); ?></p>
                <p><?php echo nl2br(htmlspecialchars($feedback['FeedbackContent'])); ?></p>
                <div class="feedback-actions">
                    <a href="edit_feedback.php?id=<?php echo $feedback['FeedbackID']; ?>" class="edit-btn">Edit</a>
                    <a href="delete_feedback.php?id=<?php echo $feedback['FeedbackID']; ?>" class="delete-btn">Delete</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</body>
</html>
