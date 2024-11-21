<?php
// Start the session
session_start();

// Include config file
require_once 'config.php';

// Check if admin is logged in
if (!isset($_SESSION["admin_loggedin"]) || $_SESSION["admin_loggedin"] !== true || !isset($_SESSION["admin_id"])) {
    header("location: admin.php");
    exit;
}

$admin_id = $_SESSION["admin_id"];

// Initialize a message variable
$message = '';

// Handle form submission for editing a resource
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_resource'])) {
    $resource_id = $_POST['resource_id'];
    $title = $_POST['title'];
    $content = $_POST['content'];
    $link = $_POST['link'];

    $sql = "UPDATE FinancialEducationResources SET ResourceTitle = ?, ResourceCont = ?, ResourceLink = ? WHERE ResourceID = ? AND AdminID = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("sssii", $title, $content, $link, $resource_id, $admin_id);
        if ($stmt->execute()) {
            $message = "Material updated successfully.";
        } else {
            $message = "Error updating material.";
        }
        $stmt->close();
    }
}

// Handle deletion of a resource
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_resource'])) {
    $resource_id = $_POST['resource_id'];

    $sql = "DELETE FROM FinancialEducationResources WHERE ResourceID = ? AND AdminID = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("ii", $resource_id, $admin_id);
        if ($stmt->execute()) {
            $message = "Resource deleted successfully.";
        } else {
            $message = "Error deleting resource.";
        }
        $stmt->close();
    }
}

// Redirect back to manage_materials.php with a message
header("location: manage_materials.php?message=" . urlencode($message));
exit;
?>
