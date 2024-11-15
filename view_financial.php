<?php
// Include config file
require_once 'config.php';

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
        }
        $stmt->close();
    }
}

$conn->close();

// Function to extract YouTube video ID
function getYouTubeID($url) {
    parse_str(parse_url($url, PHP_URL_QUERY), $query);
    return $query['v'] ?? null;
}

$video_id = $resource ? getYouTubeID($resource['ResourceLink']) : null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($resource['ResourceTitle'] ?? 'Resource Details'); ?></title>
    <link rel="stylesheet" href="navbar.css">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f9f9f9;
            padding: 20px;
        }
        .resource-details {
            max-width: 800px;
            margin: auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .resource-details h2 {
            color: #4a148c;
            margin-bottom: 10px;
        }
        .resource-details p {
            margin-bottom: 5px;
            color: #1b1b1b;
        }
        .resource-details .date {
            font-size: 0.9em;
            color: #666;
        }
        .resource-details img {
            width: 100%;
            height: auto;
            border-radius: 8px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    <div class="resource-details">
        <?php if ($resource): ?>
            <h2><?php echo htmlspecialchars($resource['ResourceTitle']); ?></h2>
            <p class="date">Added <?php echo date('j F Y', strtotime($resource['ContentDate'])); ?>.</p>
            <p>Source: Youtube. Retrieved From <?php echo htmlspecialchars($resource['ResourceLink']); ?></p>
            <?php if ($video_id): ?>
                <a href="<?php echo htmlspecialchars($resource['ResourceLink']); ?>" target="_blank">
                    <img src="https://img.youtube.com/vi/<?php echo $video_id; ?>/0.jpg" alt="Video Thumbnail">
                </a>
            <?php endif; ?>
            <h3>Understanding <?php echo htmlspecialchars($resource['ResourceTitle']); ?></h3>
            <p><?php echo nl2br(htmlspecialchars($resource['ResourceCont'])); ?></p>
        <?php else: ?>
            <p>Resource not found.</p>
        <?php endif; ?>
    </div>
</body>
</html>