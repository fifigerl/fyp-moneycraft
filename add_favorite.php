<?php
session_start(); // Start the session to access session data


var_dump($_SESSION);

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'User not logged in']);
    exit;
}

// Check if resource_id is passed in the POST request
if (isset($_POST['resource_id'])) {
    $userID = $_SESSION['user_id']; // Get user ID from session
    $resourceID = $_POST['resource_id']; // Get resource ID from the request

    // Database connection
    include('db_connection.php'); // Include your DB connection file

    // Prepare the SQL query to insert into Favorites table
    $query = "INSERT INTO Favorites (UserID, ResourceID) VALUES (?, ?)";
    if ($stmt = $conn->prepare($query)) {
        // Bind the user ID and resource ID to the prepared statement
        $stmt->bind_param("ii", $userID, $resourceID);

        // Execute the statement
        if ($stmt->execute()) {
            echo json_encode(['success' => true]); // Success
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to add to favorites']); // Error
        }

        $stmt->close(); // Close the statement
    } else {
        echo json_encode(['success' => false, 'error' => 'Database error']);
    }

    $conn->close(); // Close the database connection
} else {
    echo json_encode(['success' => false, 'error' => 'No resource ID provided']);
}
?>
