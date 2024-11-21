<?php
session_start();
require_once 'config.php';

// Check if the admin is logged in
if (!isset($_SESSION["admin_loggedin"]) || $_SESSION["admin_loggedin"] !== true) {
    header("location: ../admin.php");
    exit;
}

$sql = "SELECT Feedback.*, Users.Username, FinancialEducationResources.ResourceTitle 
        FROM Feedback 
        JOIN Users ON Feedback.UserID = Users.UserID
        JOIN FinancialEducationResources ON Feedback.ResourceID = FinancialEducationResources.ResourceID";
$feedbacks = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Feedbacks</title>
    <link rel="stylesheet" href="../css/navbar.css">
    <link rel="stylesheet" href="../styles.css">
</head>
<body>
    <?php include 'admin_navbar.php'; ?>
    <div class="feedbacks-container">
        <h2>All Feedbacks</h2>
        <table>
            <thead>
                <tr>
                    <th>User</th>
                    <th>Resource Title</th>
                    <th>Comments</th>
                    <th>Rating</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($feedback = $feedbacks->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($feedback['Username']); ?></td>
                        <td><?php echo htmlspecialchars($feedback['ResourceTitle']); ?></td>
                        <td><?php echo htmlspecialchars($feedback['FeedbackContent']); ?></td>
                        <td class="rating"><?php echo str_repeat('&#9733;', $feedback['FeedbackRating']); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
