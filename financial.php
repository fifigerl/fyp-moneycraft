<?php
// Include config file
require_once 'config.php';

// Fetch all financial education resources
$sql = "SELECT ResourceID, ResourceTitle, ResourceCont, ResourceLink, ContentDate FROM FinancialEducationResources";
$resources = [];

if ($result = $conn->query($sql)) {
    while ($row = $result->fetch_assoc()) {
        $resources[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Financial Education Resources</title>
    <link rel="stylesheet" href="navbar.css">
    <link rel="stylesheet" href="styles.css">
           
</head>
<body>
    <?php include 'navbar.php'; ?>
    <div class="resources-container">
        <h2>Financial Education Resources</h2>
        <?php foreach ($resources as $index => $resource): ?>
            <?php 
            // Hardcoded video IDs
            $video_id = $index === 0 ? 'MXCvtC0HqLE' : 'bEElvs_5byk'; 
            ?>
            <div class="resource-item">
                <iframe src="https://www.youtube.com/embed/<?php echo $video_id; ?>" frameborder="0" allowfullscreen></iframe>
                <div class="resource-info">
                    <h3><?php echo htmlspecialchars($resource['ResourceTitle']); ?></h3>
                    <p class="date">Added <?php echo date('j F Y', strtotime($resource['ContentDate'])); ?>.</p>
                    <p>Source: Youtube. Retrieved From <?php echo htmlspecialchars($resource['ResourceLink']); ?></p>
                    <a href="view_financial.php?id=<?php echo $resource['ResourceID']; ?>" class="learn-more-btn">Learn More</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</body>
</html>
