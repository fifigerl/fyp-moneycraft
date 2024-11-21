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

// Include the admin navbar
include 'admin_navbar.php';

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
    parse_str(parse_url($url, PHP_URL_QUERY), $query);
    return $query['v'] ?? null;
}

// Get message from URL if available
$message = isset($_GET['message']) ? $_GET['message'] : '';
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
            padding: 20px;
        }
        .materials-container h2 {
            margin-bottom: 20px;
            color: #4a148c;
        }
        .material-item {
            background-color: #fff;
            margin-bottom: 20px;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .material-info {
            margin-bottom: 10px;
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
        .add-material-btn {
            background-color: #4a148c;
            color: #fff;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            margin-bottom: 20px;
            text-decoration: none;
            display: inline-block;
        }
        .add-material-btn:hover {
            background-color: #3b0d6b;
        }
        .form-container, .edit-form-container {
            display: none;
            max-width: 600px;
            margin: auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
    </style>
    <script>
        function editMaterial(id, title, content, link) {
            document.getElementById('edit_resource_id').value = id;
            document.getElementById('edit_title').value = title;
            document.getElementById('edit_content').value = content;
            document.getElementById('edit_link').value = link;
            toggleEditForm();
        }

        function toggleEditForm() {
            const editFormContainer = document.querySelector('.edit-form-container');
            editFormContainer.style.display = editFormContainer.style.display === 'none' ? 'block' : 'none';
        }
    </script>
</head>
<body>
    <div class="materials-container">
        <h2>Manage Financial Education Materials</h2>
        <a href="add_materials.php" class="add-material-btn">Add Materials</a>
        <?php if (!empty($message)): ?>
            <p><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>
        <div class="edit-form-container">
            <h2>Edit Material</h2>
            <form action="materials_process.php" method="POST">
                <input type="hidden" id="edit_resource_id" name="resource_id">
                <div class="input-group">
                    <label for="edit_title">Title</label>
                    <input type="text" id="edit_title" name="title" required>
                </div>
                <div class="input-group">
                    <label for="edit_content">Article</label>
                    <textarea id="edit_content" name="content" required></textarea>
                </div>
                <div class="input-group">
                    <label for="edit_link">Video Link</label>
                    <input type="url" id="edit_link" name="link" required>
                </div>
                <button type="submit" name="edit_resource" class="submit-btn">Update Material</button>
            </form>
        </div>
        <?php if (empty($materials)): ?>
            <p>No materials uploaded yet.</p>
        <?php else: ?>
            <?php foreach ($materials as $material): ?>
                <div class="material-item">
                    <iframe width="100%" height="300" src="https://www.youtube.com/embed/<?php echo getYouTubeID($material['ResourceLink']); ?>" frameborder="0" allowfullscreen></iframe>
                    <div class="material-info">
                        <h3><?php echo htmlspecialchars($material['ResourceTitle']); ?></h3>
                        <p class="date">Added <?php echo date('j F Y', strtotime($material['ContentDate'])); ?>.</p>
                        <p>Source: Youtube. Retrieved From <?php echo htmlspecialchars($material['ResourceLink']); ?></p>
                    </div>
                    <div class="material-actions">
                        <button onclick="editMaterial(<?php echo $material['ResourceID']; ?>, '<?php echo htmlspecialchars($material['ResourceTitle']); ?>', '<?php echo htmlspecialchars($material['ResourceCont']); ?>', '<?php echo htmlspecialchars($material['ResourceLink']); ?>')">Edit</button>
                        <form action="materials_process.php" method="POST" style="display:inline;">
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