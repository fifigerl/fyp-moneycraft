<?php
// Start the session
session_start();

// Include config file
require_once '../config.php';

// Include the logging function
function logUserActivity($conn, $userId, $username, $action, $type = null) {
    $stmt = $conn->prepare("INSERT INTO UserActivities (UserID, Username, Action, Type) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $userId, $username, $action, $type);
    $stmt->execute();
    $stmt->close();
}

// Check if the user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php");
    exit;
}

// Get the logged-in user's ID and username
$user_id = $_SESSION['id'];
$username = $_SESSION['username'];

// Log the action of viewing the feedbacks page
logUserActivity($conn, $user_id, $username, "Viewed Feedbacks Page", "View");

// Fetch the username and profile picture
$sql_user = "SELECT Username, ProfilePicture FROM Users WHERE UserID = ?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$stmt_user->bind_result($username, $profile_picture);
$stmt_user->fetch();
$stmt_user->close();

// Fetch feedbacks for the logged-in user
$sql = "SELECT Feedback.*, FinancialEducationResources.ResourceTitle 
        FROM Feedback 
        JOIN FinancialEducationResources ON Feedback.ResourceID = FinancialEducationResources.ResourceID 
        WHERE Feedback.UserID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$feedbacks = $stmt->get_result();

$sql_resources = "SELECT ResourceID, ResourceTitle FROM FinancialEducationResources";
$resources = $conn->query($sql_resources);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Feedbacks</title>
    <link rel="stylesheet" href="../css/navbar.css">
    <link rel="stylesheet" href="../styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include '../navbar.php'; ?>
    <div class="feedbacks-container">
        <div class="profile-section">
            <h3><?php echo htmlspecialchars($username); ?></h3>
            <p>3 Feedbacks Published</p>
        </div>
        <div class="feedbacks-header">
            <h2>Previous Feedbacks</h2>
            <button class="add-feedback-btn" onclick="openAddModal()">
                <i class="fas fa-plus"></i> Add New Feedback
            </button>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Resource Title</th>
                    <th>Comments</th>
                    <th>Rating</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($feedback = $feedbacks->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($feedback['ResourceTitle']); ?></td>
                        <td><?php echo htmlspecialchars($feedback['FeedbackContent']); ?></td>
                        <td class="rating"><?php echo str_repeat('&#9733;', $feedback['FeedbackRating']); ?></td>
                        <td class="action-buttons">
                            <button class="edit-btn" onclick="openEditModal(<?php echo $feedback['FeedbackID']; ?>)">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button class="delete-btn" onclick="deleteFeedback(<?php echo $feedback['FeedbackID']; ?>)">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- Add/Edit Feedback Modal -->
    <div id="feedbackModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2 id="modalTitle">Add New Feedback</h2>
            <form id="feedbackForm" class="feedback-form" action="feedbacks_process.php" method="POST">
                <input type="hidden" id="feedbackId" name="feedback_id">
                <div class="input-group">
                    <label for="resource_id">Resource Title</label>
                    <select id="resource_id" name="resource_id">
                        <?php while ($resource = $resources->fetch_assoc()): ?>
                            <option value="<?php echo $resource['ResourceID']; ?>">
                                <?php echo htmlspecialchars($resource['ResourceTitle']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="input-group">
                    <label for="feedback_content">Message</label>
                    <textarea id="feedback_content" name="feedback_content"></textarea>
                </div>
                <div class="input-group">
                    <label>Rating:</label>
                    <div class="rating">
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                            <input type="radio" id="star<?php echo $i; ?>" name="feedback_rating" value="<?php echo $i; ?>">
                            <label for="star<?php echo $i; ?>">&#9733;</label>
                        <?php endfor; ?>
                    </div>
                </div>
                <button type="submit" class="submit-btn">Submit Feedback</button>
            </form>
        </div>
    </div>

    <script>
        const modal = document.getElementById('feedbackModal');
        const modalTitle = document.getElementById('modalTitle');
        const feedbackForm = document.getElementById('feedbackForm');

        function openAddModal() {
            modalTitle.textContent = 'Add New Feedback';
            feedbackForm.reset();
            modal.style.display = 'block';
        }

        function openEditModal(feedbackId) {
            modalTitle.textContent = 'Edit Feedback';
            fetch(`feedbacks_process.php?id=${feedbackId}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('feedbackId').value = data.FeedbackID;
                    document.getElementById('resource_id').value = data.ResourceID;
                    document.getElementById('feedback_content').value = data.FeedbackContent;
                    document.querySelector(`input[name="feedback_rating"][value="${data.FeedbackRating}"]`).checked = true;
                    modal.style.display = 'block';

                    // Log the action of editing feedback
                    logUserActivity(<?php echo $user_id; ?>, "<?php echo $username; ?>", "Edited feedback", "Feedback");
                });
        }

        function closeModal() {
            modal.style.display = 'none';
        }

        function deleteFeedback(feedbackId) {
            if (confirm('Are you sure you want to delete this feedback?')) {
                fetch(`feedbacks_process.php?id=${feedbackId}`, { method: 'DELETE' })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();

                            // Log the action of deleting feedback
                            logUserActivity(<?php echo $user_id; ?>, "<?php echo $username; ?>", "Deleted feedback", "Feedback");
                        } else {
                            alert('Failed to delete feedback.');
                        }
                    });
            }
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>

