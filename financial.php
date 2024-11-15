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
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f9f9f9;
            padding: 20px;
        }
        .resources-container {
            max-width: 800px;
            margin: auto;
        }
        .resources-container h2 {
            margin-bottom: 30px;
            padding-left: 10px;
            color: #4a148c;
        }
        .resource-item {
            display: flex;
            align-items: center;
            background-color: #fff;
            margin-bottom: 20px;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .resource-item iframe {
            width: 350px;
            height: 200px;
            margin-right: 20px;
            border-radius: 8px;
        }
        .resource-info {
            flex: 1;
        }
        .resource-info h3 {
            color: #4a148c;
            margin-bottom: 10px;
        }
        .resource-info p {
            margin-bottom: 5px;
            color: #1b1b1b;
        }
        .resource-info .date {
            font-size: 0.9em;
            color: #666;
        }
        .learn-more-btn {
            background-color: #ffcc00;
            color: #1b1b1b;
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
        }
        .learn-more-btn:hover {
            background-color: #e6b800;
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    <div class="resources-container">
        <h2>Financial Education Resources</h2>
        <?php foreach ($resources as $index => $resource): ?>
            <?php 
            // Hardcoded video IDs
            $video_id = $index === 0 ? 'MXCvtC0HqLE' : 'G9PPtAKJ944'; 
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
