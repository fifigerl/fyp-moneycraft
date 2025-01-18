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
    <style>
        /* General Styles */
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(to bottom, #C5C5C5, #FFFFFF);
            color: #161925;
            margin: 0;
            padding: 20px;
        }

        .materials-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .materials-container h2 {
            font-size: 2rem;
            font-weight: bold;
            color: rgb(0, 35, 72);
            text-align: center;
            margin-bottom: 20px;
        }

        .add-material-btn {
            background-color: #FFD000;
            color: #161925;
            border: none;
            padding: 10px 20px;
            font-size: 1rem;
            font-weight: bold;
            text-decoration: none;
            border-radius: 10px;
            display: inline-block;
            transition: background-color 0.3s;
            margin-bottom: 20px;
        }

        .add-material-btn:hover {
            background-color: #FDF09D;
            color: #222;
        }

        .material-item {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
            animation: fadeIn 0.5s ease-in-out;
        }

        .material-item iframe {
            width: 100%;
            height: 250px;
            border: none;
            border-radius: 10px;
            margin-bottom: 15px;
        }

        .material-info {
            margin-bottom: 15px;
        }

        .material-info h3 {
            font-size: 1.5rem;
            font-weight: bold;
            color: rgb(0, 35, 72);
            margin-bottom: 10px;
        }

        .material-info p {
            font-size: 1rem;
            color: #555;
        }

        .material-info .date {
            font-size: 0.9rem;
            color: #888;
        }

        .material-actions {
            display: flex;
            gap: 10px;
        }

        .material-actions button,
        .material-actions form button {
            background-color: #FFD000;
            color: #161925;
            border: none;
            padding: 10px 15px;
            font-size: 1rem;
            font-weight: bold;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .material-actions button:hover,
        .material-actions form button:hover {
            background-color: #FDF09D;
            color: #222;
        }

        /* Edit Form Styles */
        .edit-form-container {
            display: none;
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .edit-form-container h2 {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 15px;
        }

        .edit-form-container .input-group {
            margin-bottom: 15px;
        }

        .edit-form-container label {
            font-size: 1rem;
            font-weight: bold;
            margin-bottom: 5px;
            display: block;
        }

        .edit-form-container input,
        .edit-form-container textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 1rem;
        }

        .edit-form-container button {
            background-color: #FFD000;
            color: #161925;
            border: none;
            padding: 10px 15px;
            font-size: 1rem;
            font-weight: bold;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .edit-form-container button:hover {
            background-color: #FDF09D;
            color: #222;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
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
<?php include 'admin_navbar.php'; ?>
    <div class="materials-container">
        <h2>Manage Financial Education Materials</h2>
        <a href="add_materials.php" class="add-material-btn">Add Materials</a>

        <?php if (empty($materials)): ?>
            <p>No materials uploaded yet.</p>
        <?php else: ?>
            <?php foreach ($materials as $material): ?>
                <div class="material-item">
                    <?php $video_id = getYouTubeID($material['ResourceLink']); ?>
                    <?php if ($video_id): ?>
                        <iframe src="https://www.youtube.com/embed/<?php echo $video_id; ?>" allowfullscreen></iframe>
                    <?php else: ?>
                        <p>Invalid YouTube link.</p>
                    <?php endif; ?>

                    <div class="material-info">
                        <h3><?php echo htmlspecialchars($material['ResourceTitle']); ?></h3>
                        <p class="date">Added on <?php echo date('j F Y', strtotime($material['ContentDate'])); ?></p>
                        <p>Source: YouTube. Retrieved from <?php echo htmlspecialchars($material['ResourceLink']); ?></p>
                    </div>

                    <div class="material-actions">
                        <button onclick="editMaterial(<?php echo $material['ResourceID']; ?>, '<?php echo htmlspecialchars($material['ResourceTitle']); ?>', '<?php echo htmlspecialchars($material['ResourceCont']); ?>', '<?php echo htmlspecialchars($material['ResourceLink']); ?>')">Edit</button>
                        <form action="materials_process.php" method="POST" style="display: inline;">
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
