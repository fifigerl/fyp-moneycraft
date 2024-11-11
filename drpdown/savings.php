<?php
include '../config.php';
include '../navbar.php'; // Include the navbar

// Fetch all savings goals to display them
$savingsGoals = $conn->query("SELECT * FROM Savings WHERE UserID = 1"); // Replace with dynamic UserID
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Savings Goals</title>
    <link rel="stylesheet" href="../styles.css">
    <link rel="stylesheet" href="css/navbar.css"> <!-- Ensure the path is correct -->
</head>
<body>
    <div class="container">
        <h1>My Savings Goals</h1>
        <button id="add-goal-btn" class="add-button">+ Add Savings Goal</button>
        <div class="savings-goals">
            <?php while($row = $savingsGoals->fetch_assoc()): ?>
                <div class="savings-goal">
                    <img src="<?php echo $row['SavingsPicture']; ?>" alt="<?php echo $row['SavingsTitle']; ?>" class="goal-image">
                    <div class="goal-details">
                        <h2><?php echo $row['SavingsTitle']; ?></h2>
                        <p>Balance Needed: RM<?php echo $row['SavingsAmt']; ?></p>
                        <p>Total Savings: RM<?php echo $row['CurrentSavings'] ?? 0; ?></p>
                        <progress value="<?php echo $row['CurrentSavings'] ?? 0; ?>" max="<?php echo $row['SavingsAmt']; ?>"></progress>
                        <p><?php echo round(($row['CurrentSavings'] / $row['SavingsAmt']) * 100, 2); ?>%</p>
                        <button onclick="editGoal(<?php echo $row['SavingsID']; ?>)" class="edit-button">Edit</button>
                        <button onclick="deleteGoal(<?php echo $row['SavingsID']; ?>)" class="delete-button">Delete</button>
                        <form onsubmit="updateProgress(event, <?php echo $row['SavingsID']; ?>)">
                            <input type="number" name="amount" placeholder="Add Amount" required>
                            <button type="submit">Add</button>
                            <button type="button" onclick="updateProgress(event, <?php echo $row['SavingsID']; ?>, true)">Undo</button>
                        </form>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <div id="goal-modal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <form id="goal-form" enctype="multipart/form-data">
                <input type="hidden" name="action" value="create">
                <input type="hidden" name="savings_id" id="savings_id">
                <input type="text" name="SavingsTitle" placeholder="Goal Name" required>
                <input type="number" name="SavingsAmt" placeholder="Target Amount" required>
                <input type="date" name="TargetDate" required>
                <input type="file" name="SavingsPicture" accept="image/*">
                <button type="submit">Save</button>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const addGoalBtn = document.getElementById('add-goal-btn');
            const goalModal = document.getElementById('goal-modal');
            const closeModal = document.querySelector('.close');
            const goalForm = document.getElementById('goal-form');

            addGoalBtn.onclick = () => {
                goalForm.reset();
                goalForm.querySelector('input[name="action"]').value = 'create';
                goalModal.style.display = 'block';
            };

            closeModal.onclick = () => {
                goalModal.style.display = 'none';
            };

            goalForm.onsubmit = async (e) => {
                e.preventDefault();
                const formData = new FormData(goalForm);
                formData.append('user_id', 1); // Replace with dynamic user ID

                const response = await fetch('savings_process.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();
                alert(data.success ? "Savings goal saved successfully!" : `Error: ${data.error}`);
                if (data.success) {
                    goalModal.style.display = 'none';
                    location.reload(); // Refresh to show new entry
                }
            };

            window.editGoal = (id) => {
                fetch(`savings_process.php?action=fetch&id=${id}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data) {
                            document.getElementById('savings_id').value = data.SavingsID;
                            document.querySelector('input[name="SavingsTitle"]').value = data.SavingsTitle;
                            document.querySelector('input[name="SavingsAmt"]').value = data.SavingsAmt;
                            document.querySelector('input[name="TargetDate"]').value = data.TargetDate;
                            goalForm.querySelector('input[name="action"]').value = 'edit';
                            goalModal.style.display = 'block';
                        }
                    });
            };

            window.deleteGoal = (id) => {
                if (confirm('Are you sure you want to delete this savings goal?')) {
                    fetch(`savings_process.php?action=delete&id=${id}`)
                        .then(response => response.json())
                        .then(data => {
                            alert(data.success ? "Savings goal deleted successfully!" : `Error: ${data.error}`);
                            if (data.success) {
                                location.reload();
                            }
                        });
                }
            };

            window.updateProgress = async (e, id, isUndo = false) => {
                e.preventDefault();
                const form = e.target.closest('form');
                const amount = parseFloat(form.elements['amount'].value);
                if (isNaN(amount) || amount <= 0) {
                    alert("Please enter a valid amount.");
                    return;
                }
                const formData = new FormData();
                formData.append('action', isUndo ? 'undo_progress' : 'update_progress');
                formData.append('id', id);
                formData.append('amount', amount);

                const response = await fetch('savings_process.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();
                alert(data.success ? "Progress updated successfully!" : `Error: ${data.error}`);
                if (data.success) {
                    location.reload();
                }
            };
        });
    </script>
</body>
</html>
