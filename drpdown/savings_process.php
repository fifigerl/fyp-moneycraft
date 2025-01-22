<?php 
session_start();
include '../config.php';

// Ensure the uploads directory exists and is writable
$uploadDir = 'uploads';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Get the logged-in user's ID from the session
$userId = $_SESSION['id'];

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$id = $_POST['savings_id'] ?? $_GET['id'] ?? null;

$response = ['success' => false, 'error' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($action === 'update_progress' || $action === 'undo_progress') && $id) {
        $amount = $_POST['amount'] ?? 0;
        $amount = $action === 'undo_progress' ? -$amount : $amount;
        $sql = "UPDATE Savings SET CurrentSavings = CurrentSavings + $amount WHERE SavingsID = $id AND UserID = $userId";
        if ($conn->query($sql) === TRUE) {
            $response['success'] = true;
        } else {
            $response['error'] = 'Failed to update progress: ' . $conn->error;

        }

        if ($action === 'update_progress' && $id) {
            $amount = $_POST['amount'] ?? 0;
        
            // Update the savings goal progress
            $sql = "UPDATE Savings SET CurrentSavings = CurrentSavings + $amount WHERE SavingsID = $id AND UserID = $userId";
            if ($conn->query($sql) === TRUE) {
                $response['success'] = true;
            } else {
                $response['error'] = 'Failed to update savings progress: ' . $conn->error;
            }
        }
        
    } else {
        $title = $_POST['SavingsTitle'];
        $amount = $_POST['SavingsAmt'];
        $targetDate = $_POST['TargetDate'];
        $picture = $_FILES['SavingsPicture']['name'] ?? null;
        $target = "uploads/" . basename($picture);

        // Check for upload errors
        if ($picture && $_FILES['SavingsPicture']['error'] === UPLOAD_ERR_OK) {
            $fileType = mime_content_type($_FILES['SavingsPicture']['tmp_name']);
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];

            if (in_array($fileType, $allowedTypes)) {
                if (!move_uploaded_file($_FILES['SavingsPicture']['tmp_name'], $target)) {
                    $response['error'] = 'Failed to upload the image.';
                    echo json_encode($response);
                    exit;
                }
            } else {
                $response['error'] = 'Invalid image type. Only JPG, PNG, and GIF are allowed.';
                echo json_encode($response);
                exit;
            }
        }

        if ($action === 'create') {
            $sql = "INSERT INTO Savings (UserID, SavingsTitle, SavingsAmt, TargetDate, SavingsPicture) VALUES ($userId, '$title', $amount, '$targetDate', '$target')";
            if ($conn->query($sql) === TRUE) {
                $response['success'] = true;
            } else {
                $response['error'] = 'Failed to save the savings goal: ' . $conn->error;
            }
        } elseif ($action === 'edit' && $id) {
            $sql = $picture ? 
                "UPDATE Savings SET SavingsTitle = '$title', SavingsAmt = $amount, TargetDate = '$targetDate', SavingsPicture = '$target' WHERE SavingsID = $id AND UserID = $userId" :
                "UPDATE Savings SET SavingsTitle = '$title', SavingsAmt = $amount, TargetDate = '$targetDate' WHERE SavingsID = $id AND UserID = $userId";
            
            if ($conn->query($sql) === TRUE) {
                $response['success'] = true;
            } else {
                $response['error'] = 'Failed to update the savings goal: ' . $conn->error;
            }
        }
    }
} elseif ($action === 'delete' && $id) {
    $sql = "DELETE FROM Savings WHERE SavingsID = $id AND UserID = $userId";
    if ($conn->query($sql) === TRUE) {
        $response['success'] = true;
    } else {
        $response['error'] = 'Failed to delete the savings goal: ' . $conn->error;
    }
} elseif ($action === 'fetch' && $id) {
    $result = $conn->query("SELECT * FROM Savings WHERE SavingsID = $id AND UserID = $userId");
    $response = $result->fetch_assoc();
}

echo json_encode($response);
?>
