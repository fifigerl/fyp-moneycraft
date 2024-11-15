<?php
// Start the session
session_start();

// Include config file
require_once '../config.php';

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Fetch all resources for the dropdown
$sql = "SELECT ResourceID, ResourceTitle FROM FinancialEducationResources";
$resources = [];

if ($result = $conn->query($sql)) {
    while ($row = $result->fetch_assoc()) {
        $resources[] = $row;
    }
}

// Handle form submission
$feedback_err = $rating_err = "";
$feedback_content = $feedback_rating = "";
$selected_resource_id = $_POST['resource_id'] ?? null;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate feedback content
    if (empty(trim($_POST["feedback_content"]))) {
        $feedback_err = "Please enter your feedback.";
    } else {
        $feedback_content = trim($_POST["feedback_content"]);
    }

    // Validate rating
    if (empty($_POST["feedback_rating"])) {
        $rating_err = "Please select a rating.";
    } else {
        $feedback_rating = $_POST["feedback_rating"];
    }

    // Insert feedback into database
    if (empty($feedback_err) && empty($rating_err)) {
        $sql = "INSERT INTO Feedback (UserID, ResourceID, FeedbackContent, FeedbackRating, FeedbackDate) VALUES (?, ?, ?, ?, NOW())";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("iisi", $_SESSION['user_id'], $selected_resource_id, $feedback_content, $feedback_rating);
            if ($stmt->execute()) {
                header("location: feedbacks.php");
                exit();
            } else {
                echo "Something went wrong. Please try again later.";
            }
            $stmt->close();
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback Form</title>
    <link rel="stylesheet" href="../css/navbar.css">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f9f9f9;
            padding: 20px;
        }
        .form-container {
            max-width: 600px;
            margin: auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .form-container h2 {
            color: #4a148c;
            margin-bottom: 20px;
        }
        .input-group {
            margin-bottom: 15px;
        }
        .input-group label {
            display: block;
            margin-bottom: 5px;
            color: #1b1b1b;
        }
        .input-group select, .input-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .input-group textarea {
            resize: vertical;
            height: 100px;
        }
        .rating {
            display: flex;
            gap: 5px;
            direction: rtl;
        }
        .rating input {
            display: none;
        }
        .rating label {
            font-size: 2em;
            color: #ccc;
            cursor: pointer;
        }
        .rating input:checked ~ label,
        .rating label:hover,
        .rating label:hover ~ label {
            color: #ffcc00;
        }
        .submit-btn {
            background-color: #ffcc00;
            color: #1b1b1b;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
        }
        .submit-btn:hover {
            background-color: #e6b800;
        }
        .error {
            color: red;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <?php include '../navbar.php'; ?>
    <div class="form-container">
        <h2>Feedback Form</h2>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
            <div class="input-group">
                <label for="resource_id">Resource Title</label>
                <select id="resource_id" name="resource_id">
                    <?php foreach ($resources as $resource): ?>
                        <option value="<?php echo $resource['ResourceID']; ?>" <?php echo ($selected_resource_id == $resource['ResourceID']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($resource['ResourceTitle']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="input-group">
                <label for="feedback_content">Message</label>
                <textarea id="feedback_content" name="feedback_content"><?php echo htmlspecialchars($feedback_content); ?></textarea>
                <span class="error"><?php echo $feedback_err; ?></span>
            </div>
            <div class="input-group">
                <label>Rating:</label>
                <div class="rating">
                    <?php for ($i = 5; $i >= 1; $i--): ?>
                        <input type="radio" id="star<?php echo $i; ?>" name="feedback_rating" value="<?php echo $i; ?>" <?php echo ($feedback_rating == $i) ? 'checked' : ''; ?>>
                        <label for="star<?php echo $i; ?>">&#9733;</label>
                    <?php endfor; ?>
                </div>
                <span class="error"><?php echo $rating_err; ?></span>
            </div>
            <button type="submit" class="submit-btn">Submit Feedback</button>
        </form>
    </div>
</body>
</html>
