<?php
// Start the session
session_start();

// Check if admin is logged in
if (!isset($_SESSION["admin_loggedin"]) || $_SESSION["admin_loggedin"] !== true || !isset($_SESSION["admin_id"])) {
    header("location: admin.php");
    exit;
}

// Include config file
require_once 'config.php';

$admin_id = $_SESSION["admin_id"];
$resource_id = $_GET['id'] ?? null;

// Fetch resource details
$resource = null;
if ($resource_id) {
    $sql = "SELECT ResourceTitle, ResourceCont, ResourceLink FROM FinancialEducationResources WHERE ResourceID = ? AND AdminID = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("ii", $resource_id, $admin_id);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $resource = $result->fetch_assoc();
        }
        $stmt->close();
    }
}

$conn->close();

// If no resource is found, redirect
if (!$resource) {
    header("location: manage_materials.php?message=" . urlencode("Material not found."));
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Material</title>
   <link rel="stylesheet" href="../css/admin_styles.css">
</head>
<body>
<div class="edit-form-container">
    <h2>Edit Material</h2>
    <form action="materials_process.php" method="POST">
        <input type="hidden" name="resource_id" value="<?php echo $resource_id; ?>">
        <div class="input-group">
            <label for="title">Title</label>
            <input type="text" name="title" id="title" value="<?php echo htmlspecialchars($resource['ResourceTitle']); ?>" required>
        </div>
        <div class="input-group">
            <label for="content">Content</label>
            <textarea name="content" id="content" rows="10" required><?php echo htmlspecialchars($resource['ResourceCont']); ?></textarea>
        </div>
        <div class="input-group">
            <label for="link">Link</label>
            <input type="url" name="link" id="link" value="<?php echo htmlspecialchars($resource['ResourceLink']); ?>" required>
        </div>
        <button type="submit" name="edit_resource">Save Changes</button>
    </form>
</div>
</body>
</html>
