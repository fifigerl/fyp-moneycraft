<?php
// Start the session
session_start();

// Include config file
require_once 'config.php';

// Include the admin navbar
include 'admin_navbar.php';

// Fetch materials uploaded by the admin
$admin_id = $_SESSION["admin_id"]; // Assuming admin is logged in
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
    parse_str(parse_url($url, PHP_URL_QUERY), $query);
    return $query['v'] ?? null;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Uploaded Materials</title>
    <link rel="stylesheet" href="../css/navbar.css">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f9f9f9;
            padding: 20px;
        }
        .materials-container {
            max-width: 800px;
            margin: auto;
        }
        .material-item {
            display: flex;
            align-items: center;
            background-color: #fff;
            margin-bottom: 20px;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .material-item img {
            width: 150px;
            height: auto;
            margin-right: 20px;
            border-radius: 8px;
            cursor: pointer;
        }
        .material-info {
            flex: 1;
        }
        .material-info h3 {
            color: #4a148c;
            margin-bottom: 10px;
        }
        .material-info p {
            margin-bottom: 5px;
            color: #1b1b1b;
        }
        .material-info .date {
            font-size: 0.9em;
            color: #666;
        }
        .material-actions {
            display: flex;
            gap: 10px;
        }
        .material-actions button {
            background-color: #ffcc00;
            color: #1b1b1b;
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
        }
        .material-actions button:hover {
            background-color: #e6b800;
        }
    </style>
</head>
<body>
    <div class="materials-container">
        <h2>Education</h2>
        <?php foreach ($materials as $material): ?>
            <?php $video_id = getYouTubeID($material['ResourceLink']); ?>
            <div class="material-item">
                <?php if ($video_id): ?>
                    <a href="<?php echo htmlspecialchars($material['ResourceLink']); ?>" target="_blank">
                        <img src="https://img.youtube.com/vi/<?php echo $video_id; ?>/0.jpg" alt="Video Thumbnail">
                    </a>
                <?php endif; ?>
                <div class="material-info">
                    <h3><?php echo htmlspecialchars($material['ResourceTitle']); ?></h3>
                    <p class="date">Added <?php echo date('j F Y', strtotime($material['ContentDate'])); ?>.</p>
                    <p>Source: Youtube. Retrieved From <?php echo htmlspecialchars($material['ResourceLink']); ?></p>
                </div>
                <div class="material-actions">
                    <button>Edit</button>
                    <button>Delete</button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</body>
</html> 