<?php 
// Initialize the session
session_start();

// Check if the user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Include config file
require_once '../config.php';

// Get the logged-in user's ID
$user_id = $_SESSION['id'];

// Fetch previously generated reports
$sql = "SELECT ReportID, DateGenerated FROM Reports WHERE UserID = ? ORDER BY DateGenerated DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$reports = $stmt->get_result();

// Handle report generation
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === 'generate') {
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    // Insert new report into the database
    $sql = "INSERT INTO Reports (UserID, DateGenerated) VALUES (?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    $report_id = $stmt->insert_id; // Get the generated ReportID

    // Redirect to view the generated report
    header("location: view_report.php?report_id=$report_id&start_date=$start_date&end_date=$end_date");
    exit;
}

// Handle report deletion
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $report_id = $_POST['report_id'];

    // Delete the report from the database
    $sql = "DELETE FROM Reports WHERE ReportID = ? AND UserID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $report_id, $user_id);
    $stmt->execute();

    // Redirect to the reports page
    header("location: report.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Management - MoneyCraft</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Inter', sans-serif;
        }

        h1{
            font-weight: 900; /* Make it bold */
            color: rgb(0, 35, 72); /* Keep your custom color */
        }
        .card {
            border-radius: 20px;
            border: none;
        }
        .btn-primary {
            background-color: #FFD000;
            color: #161925;
            border: none;
            border-radius: 50px;
        }
        .btn-primary:hover {
            background-color: #FDF09D;
            color: #161925;
        }
    </style>
</head>
<body>
<?php include '../navbar.php'; ?>

<div class="container mt-4">
    <h1 class="text-left mb-4">📊 Manage Your Reports</h1>

    <!-- Generate Report Form -->
    <form method="POST" class="mb-4">
        <input type="hidden" name="action" value="generate">
        <div class="row g-3">
            <div class="col-md-5">
                <label for="start_date" class="form-label">Start Date</label>
                <input type="date" name="start_date" class="form-control" required>
            </div>
            <div class="col-md-5">
                <label for="end_date" class="form-label">End Date</label>
                <input type="date" name="end_date" class="form-control" required>
            </div>
            <div class="col-md-2 align-self-end">
                <button type="submit" class="btn btn-primary w-100">Generate</button>
            </div>
        </div>
    </form>

    <!-- List of Reports -->
    <div class="card">
        <div class="card-body">
            <h4 class="card-title">Your Generated Reports</h4>
            <?php if ($reports->num_rows > 0): ?>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Date Generated</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($report = $reports->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($report['DateGenerated']); ?></td>
                                <td>
                                    <a href="view_report.php?report_id=<?php echo $report['ReportID']; ?>" class="btn btn-sm btn-primary">View</a>
                                    <form method="POST" style="display: inline-block;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="report_id" value="<?php echo $report['ReportID']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this report?');">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No reports generated yet. Use the form above to generate a new report.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
