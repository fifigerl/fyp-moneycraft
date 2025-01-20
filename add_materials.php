<?php
// Start the session
session_start();

// Include config file
require_once 'config.php';

include 'admin_navbar.php';

// Define variables and initialize with empty values
$title = $content = $video_link = "";
$title_err = $content_err = $video_link_err = "";

// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate title
    if (empty(trim($_POST["title"]))) {
        $title_err = "Please enter a title.";
    } else {
        $title = trim($_POST["title"]);
    }

    // Validate content
    if (empty(trim($_POST["content"]))) {
        $content_err = "Please enter the article content.";
    } else {
        $content = trim($_POST["content"]);
    }

    // Validate video link
    if (empty(trim($_POST["video_link"]))) {
        $video_link_err = "Please enter a video link.";
    } else {
        $video_link = trim($_POST["video_link"]);
    }

    // Check input errors before inserting in database
    if (empty($title_err) && empty($content_err) && empty($video_link_err)) {
        // Prepare an insert statement
        $sql = "INSERT INTO FinancialEducationResources (AdminID, ResourceTitle, ResourceCont, ResourceLink, ContentDate) VALUES (?, ?, ?, ?, NOW())";

        if ($stmt = $conn->prepare($sql)) {
            // Bind variables to the prepared statement as parameters
            $stmt->bind_param("isss", $param_admin_id, $param_title, $param_content, $param_video_link);

            // Set parameters
            $param_admin_id = $_SESSION["admin_id"]; // Assuming admin is logged in
            $param_title = $title;
            $param_content = $content;
            $param_video_link = $video_link;

            // Attempt to execute the prepared statement
            if ($stmt->execute()) {
                header("location: manage_materials.php?message=Material added successfully.");
                exit;
            } else {
                echo "Something went wrong. Please try again later.";
            }

            // Close statement
            $stmt->close();
        }
    }

    // Close connection
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Financial Education Materials</title>
    <link rel="stylesheet" href="../css/admin_styles.css">

   
<body>
    <div class="form-container">
        <h2>Manage Financial Education Materials</h2>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
            <div class="input-group">
                <label for="title">Title</label>
                <input type="text" id="title" name="title" value="<?php echo $title; ?>" required>
                <span class="error"><?php echo $title_err; ?></span>
            </div>
            <div class="input-group">
                <label for="content">Article</label>
                <textarea id="content" name="content" required><?php echo $content; ?></textarea>
                <span class="error"><?php echo $content_err; ?></span>
            </div>
            <div class="input-group">
                <label for="video_link">Video Link</label>
                <input type="text" id="video_link" name="video_link" value="<?php echo $video_link; ?>" required>
                <span class="error"><?php echo $video_link_err; ?></span>
            </div>
            <button type="submit" class="submit-btn">Submit Material</button>
        </form>
        <a href="manage_materials.php" class="back-btn">Back</a>
    </div>
</body>
</html> 