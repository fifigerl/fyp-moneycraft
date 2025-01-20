<?php
// Start the session
session_start();

// Check if admin is logged in, if not redirect to admin login page
if (!isset($_SESSION["admin_loggedin"]) || $_SESSION["admin_loggedin"] !== true || !isset($_SESSION["admin_id"])) {
    header("location: admin.php");
    exit;
}

// Include config file
require_once 'config.php';

// Get admin ID from session
$admin_id = $_SESSION["admin_id"];

// Fetch materials uploaded by the admin
$sql = "SELECT ResourceID, ResourceTitle, ResourceCont, ResourceLink, ContentDate FROM FinancialEducationResources WHERE AdminID = ?";
$materials = [];

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $admin_id);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $materials[] = $row;
        }
    }
    $stmt->close();
}

$conn->close();

// Function to extract YouTube video ID
function getYouTubeID($url) {
    if (preg_match('/(?:https?:\/\/)?(?:www\.)?youtu\.be\/([a-zA-Z0-9_-]+)|(?:https?:\/\/)?(?:www\.)?youtube\.com\/watch\?v=([a-zA-Z0-9_-]+)/', $url, $matches)) {
        return $matches[1] ?? $matches[2];
    }
    return null;
}

// Get message from URL if available
$message = isset($_GET['message']) ? $_GET['message'] : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Materials</title>
    <link rel="stylesheet" href="../css/admin_styles.css">
    <script>
        function confirmDelete() {
            return confirm("Are you sure you want to delete this material?");
        }
    </script>
</head>
<body>
<?php include 'admin_navbar.php'; ?>
<div class="materials-container">
    <h2>Manage Financial Education Materials</h2>
    <a href="add_materials.php" class="add-material-btn">Add Materials</a>

    <?php if (!empty($message)): ?>
        <p><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>

    <?php if (empty($materials)): ?>
        <p>No materials uploaded yet.</p>
    <?php else: ?>
        <?php foreach ($materials as $material): ?>
            <div class="material-item">
                <?php $video_id = getYouTubeID($material['ResourceLink']); ?>
                <?php if ($video_id): ?>
                    <iframe src="https://www.youtube.com/embed/<?php echo $video_id; ?>" allowfullscreen></iframe>
                <?php else: ?>
                    <p>Invalid YouTube link or no video available.</p>
                <?php endif; ?>

                <h3><?php echo htmlspecialchars($material['ResourceTitle']); ?></h3>
                <p>Added on <?php echo date('j F Y', strtotime($material['ContentDate'])); ?></p>
                <div class="material-actions">
                    <a href="edit_material.php?id=<?php echo $material['ResourceID']; ?>">Edit</a>
                    <form action="materials_process.php" method="POST" style="display: inline;" onsubmit="return confirmDelete();">
                        <input type="hidden" name="resource_id" value="<?php echo $material['ResourceID']; ?>">
                        <button type="submit" name="delete_resource">Delete</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
</body>
</html>
