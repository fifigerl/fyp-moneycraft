<?php
session_start();
include '../config.php';


// Include the logging function
function logUserActivity($conn, $userId, $username, $action, $type = null) {
    $stmt = $conn->prepare("INSERT INTO UserActivities (UserID, Username, Action, Type) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $userId, $username, $action, $type);
    $stmt->execute();
    $stmt->close();
}

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php");
    exit;
}

// Get the logged-in user's ID and username
$user_id = $_SESSION['id'];
$username = $_SESSION['username'];

// Log the action of viewing the savings goals page
logUserActivity($conn, $user_id, $username, "Viewed Savings Goals Page", "View");

// Fetch savings goals for the logged-in user
$savingsGoals = $conn->query("SELECT * FROM Savings WHERE UserID = $user_id");

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Savings Goals</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #F8F9FA;
            color: #161925;
            font-family: 'Inter', sans-serif;
        }

        h1 {
            font-weight: 900;
            color: rgb(0, 35, 72);
            margin-bottom: 20px;
        }

        .container {
            margin-top: 20px;
        }

        .add-button {
            background-color: #FFD000;
            color: #161925;
            font-weight: bold;
            border-radius: 15px;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            margin-bottom: 20px;
        }

        .add-button:hover {
            background-color: #FDF09D;
        }

        .savings-goal {
            background-color: #FFFFFF;
            border-radius: 20px;
            padding: 15px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .goal-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 10px;
            margin-right: 20px;
        }

        .goal-details {
            flex: 1;
        }

        .goal-details h2 {
            font-weight: bold;
            margin-bottom: 10px;
        }

        .goal-details p {
            margin: 0;
            font-size: 14px;
        }

        .goal-details .progress {
            height: 20px;
            border-radius: 10px;
            margin-top: 10px;
            background-color: #e9ecef;
            max-width: 70%; /* Shorter progress bar */
        }

        .goal-details .progress-bar {
            background-color: #FFD000;
            color: #161925;
            font-weight: bold;
            text-align: center;
        }

        .edit-button, .delete-button {
            background-color: #FFD000;
            color: #161925;
            font-weight: bold;
            border: none;
            padding: 5px 10px;
            border-radius: 10px;
            cursor: pointer;
            margin-right: 5px;
            margin-top: 10px; /* Added margin to the top */
        }

        .delete-button {
            background-color: #FF4D4D;
            color: #FFFFFF;
        }

        .delete-button:hover {
            background-color: #FF0000;
        }

        .modal-content {
            border-radius: 20px;
            padding: 20px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        }

        .modal-content input, .modal-content button {
            width: 100%;
            margin-bottom: 10px;
            padding: 10px;
            border-radius: 10px;
            border: 1px solid #ddd;
            font-size: 14px;
        }

        .modal-content button {
            background-color: #FFD000;
            color: #161925;
            font-weight: bold;
            border: none;
        }

        .modal-content button:hover {
            background-color: #FDF09D;
        }
       /* Styling for the close button */
       .modal-content .btn-close-custom {
            position: absolute;
            top: 15px;
            right: 15px;
            width: 30px;
            height: 30px;
            background-color: #FFD000;
            color: #161925;
            font-size: 18px;
            font-weight: bold;
            border: none;
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        .modal-content .btn-close-custom:hover {
            background-color: #FDF09D;
        }



    </style>
</head>
<body>
<?php include '../navbar.php'; ?>
    <div class="container">
        <h1>My Savings Goals</h1>
        <button id="add-goal-btn" class="add-button">+ Add Savings Goal</button>
        <div class="savings-goals">
            <?php while($row = $savingsGoals->fetch_assoc()): ?>
                <div class="savings-goal">
                    <img src="<?php echo $row['SavingsPicture']; ?>" alt="<?php echo $row['SavingsTitle']; ?>" class="goal-image">
                    <div class="goal-details">
    <h2><?php echo $row['SavingsTitle']; ?></h2>
    <p>Target: RM<?php echo $row['SavingsAmt']; ?></p>
    <p>Saved: RM<?php echo $row['CurrentSavings'] ?? 0; ?></p>
    <div class="progress">
        <div class="progress-bar" role="progressbar" 
             style="width: <?php echo ($row['CurrentSavings'] / $row['SavingsAmt']) * 100; ?>%;" 
             aria-valuenow="<?php echo $row['CurrentSavings']; ?>" 
             aria-valuemin="0" 
             aria-valuemax="<?php echo $row['SavingsAmt']; ?>">
            <?php echo round(($row['CurrentSavings'] / $row['SavingsAmt']) * 100, 2); ?>%
        </div>
    </div>
    <!-- Update Progress Form -->
    <form onsubmit="updateSavings(event, <?php echo $row['SavingsID']; ?>)">
        <input type="number" step="0.01" min="0" name="amount" placeholder="Add to savings" required>
        <button type="submit" class="edit-button">Update Progress</button>
    </form>
    <!-- Edit Button -->
    <button onclick="editGoal(<?php echo $row['SavingsID']; ?>)" class="edit-button">Edit Goal</button>
    <!-- Delete Button -->
    <button onclick="deleteGoal(<?php echo $row['SavingsID']; ?>)" class="delete-button">Delete</button>
</div>

                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <div id="goal-modal" class="modal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <button type="button" class="btn-close-custom" data-bs-dismiss="modal">&times;</button>
                <div class="modal-header">
                    <h5 class="modal-title">Add/Edit Savings Goal</h5>
                </div>
                <form id="goal-form" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create">
                        <input type="hidden" name="savings_id" id="savings_id">
                        <input type="text" name="SavingsTitle" placeholder="Goal Name" required>
                        <input type="number" name="SavingsAmt" placeholder="Target Amount" required>
                        <input type="date" name="TargetDate" required>
                        <input type="file" name="SavingsPicture" accept="image/*">
                    </div>
                    <div class="modal-footer">
                        <button type="submit">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
      document.addEventListener('DOMContentLoaded', () => {
    const addGoalBtn = document.getElementById('add-goal-btn');
    const goalModal = new bootstrap.Modal(document.getElementById('goal-modal'));
    const goalForm = document.getElementById('goal-form');

    // Open "Add New Goal" Modal
    addGoalBtn.onclick = () => {
        goalForm.reset();
        goalForm.querySelector('input[name="action"]').value = 'create';
        goalModal.show();
    };

    // Submit Goal Form (Create or Edit)
    goalForm.onsubmit = async (e) => {
        e.preventDefault();
        const formData = new FormData(goalForm);
        formData.append('user_id', <?php echo $user_id; ?>);

        const response = await fetch('savings_process.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();
        alert(data.success ? "Savings goal saved successfully!" : `Error: ${data.error}`);
        if (data.success) {
            goalModal.hide();
            location.reload();
        }
    };

    // Edit Goal Function
    window.editGoal = async (id) => {
        const response = await fetch(`savings_process.php?action=fetch&id=${id}`);
        const data = await response.json();
        if (data) {
            document.getElementById('savings_id').value = data.SavingsID;
            document.querySelector('input[name="SavingsTitle"]').value = data.SavingsTitle;
            document.querySelector('input[name="SavingsAmt"]').value = data.SavingsAmt;
            document.querySelector('input[name="TargetDate"]').value = data.TargetDate;
            goalForm.querySelector('input[name="action"]').value = 'edit';
            goalModal.show();
        } else {
            alert('Failed to fetch savings goal details.');
        }
    };

    // Update Savings Progress
    window.updateSavings = async (event, savingsId) => {
        event.preventDefault();
        const form = event.target;
        const formData = new FormData(form);
        formData.append('action', 'update_progress');
        formData.append('savings_id', savingsId);

        const response = await fetch('savings_process.php', {
            method: 'POST',
            body: formData,
        });

        const data = await response.json();
        if (data.success) {
            alert("Savings progress updated successfully!");
            location.reload();
        } else {
            alert(`Error: ${data.error}`);
        }
    };

    // Delete Goal Function
    window.deleteGoal = async (id) => {
        if (confirm('Are you sure you want to delete this savings goal?')) {
            const response = await fetch(`savings_process.php?action=delete&id=${id}`);
            const data = await response.json();
            if (data.success) {
                alert("Savings goal deleted successfully!");
                location.reload();
            } else {
                alert(`Error: ${data.error}`);
            }
        }
    };
});



    </script>
</body>
</html>
