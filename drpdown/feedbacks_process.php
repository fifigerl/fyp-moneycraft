<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php");
    exit;
}

$response = [];

// Handle GET request for fetching feedback details
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['id'])) {
    $feedbackId = $_GET['id'];
    $sql = "SELECT * FROM Feedback WHERE FeedbackID = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $feedbackId);
        $stmt->execute();
        $result = $stmt->get_result();
        $feedback = $result->fetch_assoc();
        echo json_encode($feedback);
        $stmt->close();
    }
}

// Handle DELETE request for deleting feedback
if ($_SERVER["REQUEST_METHOD"] == "DELETE" && isset($_GET['id'])) {
    parse_str(file_get_contents("php://input"), $_DELETE);
    $feedbackId = $_GET['id'];
    $sql = "DELETE FROM Feedback WHERE FeedbackID = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $feedbackId);
        $response['success'] = $stmt->execute();
        echo json_encode($response);
        $stmt->close();
    } else {
        $response['success'] = false;
        echo json_encode($response);
    }
}

// Handle POST request for adding/updating feedback
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $feedback_err = $rating_err = "";
    $feedback_content = $feedback_rating = "";
    $selected_resource_id = $_POST['resource_id'] ?? null;
    $feedback_id = $_POST['feedback_id'] ?? null;

    if (empty(trim($_POST["feedback_content"]))) {
        $feedback_err = "Please enter your feedback.";
    } else {
        $feedback_content = trim($_POST["feedback_content"]);
    }

    if (empty($_POST["feedback_rating"])) {
        $rating_err = "Please select a rating.";
    } else {
        $feedback_rating = $_POST["feedback_rating"];
    }

    if (empty($feedback_err) && empty($rating_err)) {
        if ($feedback_id) {
            $sql = "UPDATE Feedback SET ResourceID = ?, FeedbackContent = ?, FeedbackRating = ? WHERE FeedbackID = ?";
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("isii", $selected_resource_id, $feedback_content, $feedback_rating, $feedback_id);
                if ($stmt->execute()) {
                    header("location: feedbacks.php");
                    exit();
                } else {
                    echo "Something went wrong. Please try again later.";
                }
                $stmt->close();
            }
        } else {
            $sql = "INSERT INTO Feedback (UserID, ResourceID, FeedbackContent, FeedbackRating, FeedbackDate) VALUES (?, ?, ?, ?, NOW())";
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("iisi", $_SESSION['id'], $selected_resource_id, $feedback_content, $feedback_rating);
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
}

$conn->close();
?>
