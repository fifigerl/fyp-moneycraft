<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Include config file
require_once 'config.php';
include 'navbar.php';

// Check if the user is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("location: login.php");
    exit;
}

// Get the resource ID from the URL
$resource_id = $_GET['id'] ?? null;
$resource = null;

if ($resource_id) {
    // Fetch the resource details
    $sql = "SELECT ResourceTitle, ResourceCont, ResourceLink, ContentDate FROM FinancialEducationResources WHERE ResourceID = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $resource_id);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $resource = $result->fetch_assoc();

            // Check if the resource was found
            if (!$resource) {
                echo "<p>Resource not found. Please check the ID or try again later.</p>";
                exit;
            }
        } else {
            echo "<p>Failed to execute query: " . $stmt->error . "</p>";
            exit;
        }
        $stmt->close();
    } else {
        echo "<p>Failed to prepare query: " . $conn->error . "</p>";
        exit;
    }
} else {
    echo "<p>No resource ID provided. Please try again.</p>";
    exit;
}

// Function to extract YouTube video ID
function getYouTubeID($url) {
    if (preg_match('/(?:https?:\/\/)?(?:www\.)?youtu\.be\/([a-zA-Z0-9_-]+)|(?:https?:\/\/)?(?:www\.)?youtube\.com\/watch\?v=([a-zA-Z0-9_-]+)/', $url, $matches)) {
        return $matches[1] ?? $matches[2];
    }
    return null;
}

$video_id = $resource ? getYouTubeID($resource['ResourceLink']) : null;

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($resource['ResourceTitle'] ?? 'Resource Details'); ?></title>
    <link rel= "stylesheet" href="styles.css">
  
  
</head>
<body>
  
    <div class="resource-details">
        <?php if ($resource): ?>
            <h2><?php echo htmlspecialchars($resource['ResourceTitle']); ?></h2>
            <p class="date">Added <?php echo date('j F Y', strtotime($resource['ContentDate'])); ?>.</p>
            <p>Source: Youtube. Retrieved From <?php echo htmlspecialchars($resource['ResourceLink']); ?></p>
            <?php if ($video_id): ?>
                <iframe width="100%" height="300" src="https://www.youtube.com/embed/<?php echo $video_id; ?>" frameborder="0" allowfullscreen></iframe>
            <?php else: ?>
                <p>Invalid YouTube link.</p>
            <?php endif; ?>
            <h3>Understanding <?php echo htmlspecialchars($resource['ResourceTitle']); ?></h3>
            <p><?php echo nl2br(htmlspecialchars($resource['ResourceCont'])); ?></p>
        <?php else: ?>
            <p>Resource not found.</p>
        <?php endif; ?>
    </div>
</body>
</html>

<?php
// Close the connection after all includes and operations
$conn->close();
?>
