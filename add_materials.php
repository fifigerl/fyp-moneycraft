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
    <link rel="stylesheet" href="../css/navbar.css">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f9f9f9;
            padding: 20px;
        }
        .form-container {
            max-width: 600px;
            margin: auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .form-container h2 {
            color: #4a148c;
            margin-bottom: 20px;
        }
        .input-group {
            margin-bottom: 15px;
        }
        .input-group label {
            display: block;
            margin-bottom: 5px;
            color: #1b1b1b;
        }
        .input-group input, .input-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .input-group textarea {
            resize: vertical;
            height: 150px;
        }
        .submit-btn {
            background-color: #ffcc00;
            color: #1b1b1b;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
        }
        .submit-btn:hover {
            background-color: #e6b800;
        }
        .error {
            color: red;
            font-size: 0.9em;
        }

        .back-btn {
            background-color: #4a148c;
            color: #fff;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            text-decoration: none; /* Remove underline from link */
            display: inline-block; /* Ensure it behaves like a button */
            margin-bottom: 20px; /* Add some space below the button */
            margin-top: 30px;
        }

        .back-btn:hover {
            background-color: #3b0d6b;
        }
    </style>
</head>
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