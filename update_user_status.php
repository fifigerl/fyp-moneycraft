<?php
// Start session and include config
session_start();
require_once 'config.php';

// Ensure the admin is logged in
if (!isset($_SESSION['admin_loggedin']) || $_SESSION['admin_loggedin'] !== true) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access. Please log in as an admin.']);
    exit;
}

// Validate and sanitize request data
$user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : null;
$action = isset($_POST['action']) ? $_POST['action'] : null;

// Ensure user_id and action are provided
if (!$user_id || !in_array($action, ['activate', 'suspend'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid request data']);
    exit;
}

// Map action to status
$status = $action === 'activate' ? 'active' : 'suspended';

// Update user status in the database
$sql = "UPDATE Users SET status = ? WHERE UserID = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Database preparation failed: ' . $conn->error]);
    exit;
}

$stmt->bind_param("si", $status, $user_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => "User status updated to '$status'."]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to update user status: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
