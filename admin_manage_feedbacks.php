<?php
// Include config file
require_once 'config.php';

// Fetch all feedbacks
$sql = "SELECT f.FeedbackContent, f.FeedbackRating, f.FeedbackDate, r.ResourceTitle, u.username FROM Feedback f JOIN FinancialEducationResources r ON f.ResourceID = r.ResourceID JOIN users u ON f.UserID = u.UserID";
$feedbacks = [];

if ($result = $conn->query($sql)) {
    while ($row = $result->fetch_assoc()) {
        $feedbacks[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Feedbacks</title>
    <link rel="stylesheet" href="css/navbar.css">
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
    </style>
</head>
<body>
    <?php include 'admin_navbar.php'; ?>
    <div class="feedbacks-container">
        <h2>Manage Feedbacks</h2>
        <?php foreach ($feedbacks as $feedback): ?>
            <div class="feedback-item">
                <h3><?php echo htmlspecialchars($feedback['ResourceTitle']); ?> by <?php echo htmlspecialchars($feedback['username']); ?></h3>
                <p class="date">Submitted on <?php echo date('j F Y', strtotime($feedback['FeedbackDate'])); ?></p>
                <p>Rating: <?php echo str_repeat('&#9733;', $feedback['FeedbackRating']); ?></p>
                <p><?php echo nl2br(htmlspecialchars($feedback['FeedbackContent'])); ?></p>
            </div>
        <?php endforeach; ?>
    </div>
</body>
</html>
