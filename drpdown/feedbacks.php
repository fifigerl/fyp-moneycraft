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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #F8F9FA;
            color: #161925;
            font-family: 'Inter', sans-serif;
        }

        h1 {
            font-weight: 900;
    font-size: 48px; /* Increase the font size */
    letter-spacing: 2px; /* Add letter spacing for a wider appearance */
    color: rgb(0, 35, 72); /* Retain the existing color */
    margin-top: 20px;
    margin-bottom: 20px;
    margin-left:20px;
    text-align: left; /* Optional: Center-align the text */
    padding: 20px;
}

        .feedbacks-container {
            margin: 20px auto;
            max-width: 1200px;
            padding: 20px;
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            
        }

        .profile-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .profile-section h3 {
            margin: 0;
            font-weight: bold;
            font-size: 24px;
        }

        .profile-section p {
            margin: 0;
            color: gray;
        }

        .feedbacks-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .feedbacks-header h2 {
            font-size: 24px;
            font-weight: bold;
            color: rgb(0, 35, 72);
        }

        .add-feedback-btn {
            background-color: #FFD000;
            color: #161925;
            font-weight: bold;
            border-radius: 15px;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
        }

        .add-feedback-btn:hover {
            background-color: #FDF09D;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #f1f1f1;
            font-weight: bold;
        }

        .rating {
            color: #FFD700;
        }

        .action-buttons button {
            background-color: #FFD000;
            color: #161925;
            font-weight: bold;
            border: none;
            border-radius: 10px;
            padding: 5px 10px;
            margin-right: 5px;
            cursor: pointer;
        }

        .action-buttons button.delete-btn {
            background-color: #FF4D4D;
            color: #fff;
        }

        .action-buttons button:hover {
            opacity: 0.9;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1050;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.4);
        }

        .modal-content {
            background-color: #fff;
            margin: 10% auto;
            padding: 20px;
            border-radius: 20px;
            width: 80%;
            max-width: 500px;
            position: relative;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        }

        .modal-content h2 {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .modal-content .input-group {
            margin-bottom: 20px;
        }

        .modal-content label {
            font-weight: bold;
            margin-bottom: 5px;
            display: block;
        }

        .modal-content input,
        .modal-content textarea,
        .modal-content select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .modal-content button.submit-btn {
            background-color: #FFD000;
            color: #161925;
            font-weight: bold;
            border: none;
            padding: 10px 20px;
            border-radius: 10px;
            cursor: pointer;
            width: 100%;
            margin-top: 30px;
        }

        .modal-content button.submit-btn:hover {
            background-color: #FDF09D;
        }

        .modal-content .close {
            position: absolute;
            top: 15px;
            right: 15px;
            font-size: 18px;
            font-weight: bold;
            background-color: #FFD000;
            color: #161925;
            border: none;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        .modal-content .close:hover {
            background-color: #FDF09D;
        }

        .stars {
    display: flex;
    gap: 40px;
    justify-content: flex-start;
    direction: rtl; /* Right-to-left layout for stars */
    margin-top: 30px; /* Adds space between the label and stars */
   
}

.stars input[type="radio"] {
    display: none; /* Hide the radio buttons */
}

.stars label {
    font-size: 40px;
    color: #ddd; /* Default gray color */
    cursor: pointer;
    transition: color 0.2s ease-in-out;
}

.stars label:hover,
.stars label:hover ~ label {
    color: #FFD700; /* Yellow color when hovering */
}

.stars input[type="radio"]:checked ~ label {
    color: #ddd; /* Reset stars to gray after the checked star */
}

.stars input[type="radio"]:checked + label,
.stars input[type="radio"]:checked + label ~ label {
    color: #FFD700; /* Fill stars up to the checked one */
}

.input-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}


    </style>
</head>
<body>
    <?php include '../navbar.php'; ?>

    <h1>My Feedbacks</h1>

    <div class="feedbacks-container">
        <div class="profile-section">
            <h3><?php echo htmlspecialchars($username); ?></h3>
            <p><?php echo $feedbacks->num_rows; ?> Feedbacks Published</p>
        </div>

        <div class="feedbacks-header">
            <h2>My Previous Feedbacks</h2>
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

    <div id="feedbackModal" class="modal">
        <div class="modal-content">
            <button class="close" onclick="closeModal()">&times;</button>
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
                    <div class="input-group">


    <label>Rating  </label>
    <div class="stars">
    <input type="radio" id="star5" name="feedback_rating" value="5">
    <label for="star5" class="star">&#9733;</label>
    <input type="radio" id="star4" name="feedback_rating" value="4">
    <label for="star4" class="star">&#9733;</label>
    <input type="radio" id="star3" name="feedback_rating" value="3">
    <label for="star3" class="star">&#9733;</label>
    <input type="radio" id="star2" name="feedback_rating" value="2">
    <label for="star2" class="star">&#9733;</label>
    <input type="radio" id="star1" name="feedback_rating" value="1">
    <label for="star1" class="star">&#9733;</label>
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

