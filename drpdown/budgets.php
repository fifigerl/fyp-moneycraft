<?php
session_start();
include '../config.php';
include '../navbar.php';

// Include the logging function
function logUserActivity($conn, $userId, $username, $action, $type = null) {
    $stmt = $conn->prepare("INSERT INTO UserActivities (UserID, Username, Action, Type) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $userId, $username, $action, $type);
    $stmt->execute();
    $stmt->close();
}

// Check if the user is logged in, if not redirect to login page
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php");
    exit;
}

// Get the logged-in user's ID and username
$user_id = $_SESSION['id'];
$username = $_SESSION['username'];

// Log the action of viewing the budgets page
logUserActivity($conn, $user_id, $username, "Viewed Budgets Page", "View");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Budgets</title>
    <link rel="stylesheet" href="../styles.css">
    <link rel="stylesheet" href="css/navbar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <div class="budget-header">
            <h1 class="budget-title">My Budgets</h1>
            <button id="add-budget-btn" class="add-budget-btn">
                <i class="fas fa-plus"></i> Add Budget
            </button>
        </div>
        <div id="budgets-list"></div>
    </div>

    <div id="budget-modal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <form id="budget-form">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="budget_id" id="budget_id">
                <input type="text" name="title" id="budget-title" placeholder="Budget Title" required>
                <select name="category" id="budget-category" required>
                    <optgroup label="Expense">
                        <option value="Tuition Fees">Tuition Fees</option>
                        <option value="Rent">Rent</option>
                        <option value="Utilities">Utilities</option>
                        <option value="Groceries">Groceries</option>
                        <option value="Dining Out">Dining Out</option>
                        <option value="Transportation">Transportation</option>
                        <option value="School Supplies">School Supplies</option>
                        <option value="Technology">Technology</option>
                        <option value="Health and Wellness">Health and Wellness</option>
                        <option value="Entertainment">Entertainment</option>
                        <option value="Shopping">Shopping</option>
                        <option value="Other">Other</option>
                    </optgroup>
                </select>
                <input type="number" name="amount" id="budget-amount" placeholder="Amount" required>
                <input type="date" name="start_date" id="budget-start" required>
                <input type="date" name="end_date" id="budget-end" required>
                <button type="submit">Save</button>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const budgetsList = document.getElementById('budgets-list');
            const budgetModal = document.getElementById('budget-modal');
            const closeModal = document.querySelector('.close');
            const addBudgetBtn = document.getElementById('add-budget-btn');
            const budgetForm = document.getElementById('budget-form');
            let submitted = false;

            async function fetchBudgets() {
                try {
                    const response = await fetch('budgets_process.php', {
                        method: 'GET'
                    });
                    const data = await response.json();
                    renderBudgets(data);
                } catch (error) {
                    console.error("Error fetching budgets:", error);
                }
            }

            function renderBudgets(budgets) {
                budgetsList.innerHTML = '';

                budgets.forEach(budget => {
                    const budgetDiv = document.createElement('div');
                    budgetDiv.classList.add('budget');
                    budgetDiv.innerHTML = `
                        <p>${budget.BudgetTitle} - ${budget.BudgetCat} - RM${parseFloat(budget.BudgetAmt).toFixed(2)} - ${budget.BudgetStart} to ${budget.BudgetEnd}</p>
                        <button onclick="deleteBudget(${budget.BudgetID})">Delete</button>
                        <button onclick='editBudget(${JSON.stringify(budget)})'>Edit</button>
                    `;
                    budgetsList.appendChild(budgetDiv);
                });
            }

            addBudgetBtn.addEventListener('click', () => {
                budgetForm.reset();
                document.getElementById('budget_id').value = '';
                budgetModal.style.display = 'block';
            });

            closeModal.addEventListener('click', () => {
                budgetModal.style.display = 'none';
            });

            budgetForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                if (submitted) return;
                submitted = true;

                const formData = new FormData(budgetForm);
                formData.append("action", "save");

                try {
                    const response = await fetch('budgets_process.php', {
                        method: 'POST',
                        body: formData
                    });
                    const data = await response.json();
                    if (data.success) {
                        budgetModal.style.display = 'none';
                        budgetForm.reset();
                        fetchBudgets();

                        // Log the action of adding a budget
                        logUserActivity(<?php echo $user_id; ?>, "<?php echo $username; ?>", "Added a budget", "Budget");
                    }
                } catch (error) {
                    console.error("Error submitting budget:", error);
                } finally {
                    submitted = false;
                }
            });

            window.deleteBudget = async (budgetID) => {
                if (confirm('Are you sure you want to delete this budget?')) {
                    const formData = new FormData();
                    formData.append('action', 'delete');
                    formData.append('budget_id', budgetID);

                    try {
                        const response = await fetch('budgets_process.php', {
                            method: 'POST',
                            body: formData
                        });
                        const data = await response.json();
                        if (data.success) {
                            fetchBudgets();

                            // Log the action of deleting a budget
                            logUserActivity(<?php echo $user_id; ?>, "<?php echo $username; ?>", "Deleted a budget", "Budget");
                        }
                    } catch (error) {
                        console.error("Error deleting budget:", error);
                    }
                }
            };

            window.editBudget = (budget) => {
                document.getElementById('budget_id').value = budget.BudgetID;
                document.getElementById('budget-title').value = budget.BudgetTitle;
                document.getElementById('budget-category').value = budget.BudgetCat;
                document.getElementById('budget-amount').value = budget.BudgetAmt;
                document.getElementById('budget-start').value = budget.BudgetStart.split(' ')[0];
                document.getElementById('budget-end').value = budget.BudgetEnd.split(' ')[0];
                budgetModal.style.display = 'block';

                // Log the action of editing a budget
                logUserActivity(<?php echo $user_id; ?>, "<?php echo $username; ?>", "Edited a budget", "Budget");
            };

            fetchBudgets();
        });
    </script>
</body>
</html>
