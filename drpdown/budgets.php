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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Budgets</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #F8F9FA;
            color: #161925;
            font-family: 'Inter', sans-serif;
        }

        h1.budget-header {
            font-weight: 900;
            font-size: 48px;
            color: rgb(0, 35, 72);
            margin-bottom: 20px;
        }

        .card {
            background-color: #FFFFFF;
            border-radius: 20px;
            padding: 20px;
            border: none;
        }

        .table {
            width: 100%;
            margin-top: 20px;
        }

        .table th, .table td {
            text-align: center;
            vertical-align: middle;
        }

        .table th {
            font-weight: bold;
            color: #161925;
        }

        .table td {
            color: #333;
        }

        .progress {
            height: 20px;
            border-radius: 10px;
            overflow: hidden;
            background-color: #e9ecef;
        }

        .progress-bar {
            background-color: #FFD000;
            color: #161925;
            font-weight: bold;
            text-align: center;
            transition: width 0.3s ease;
        }

        .add-budget-btn {
            background-color: #FFD000;
            color: #161925;
            font-weight: bold;
            border-radius: 15px;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            margin-bottom: 10px;
        }

        .add-budget-btn:hover {
            background-color: #FDF09D;
        }

        .btn-edit {
            background-color: #FFD000;
            color: #161925;
            font-weight: bold;
            border-radius: 10px;
            padding: 5px 10px;
            border: none;
            cursor: pointer;
            margin-bottom: 10px;
        }

        .btn-delete {
            background-color: #FF4D4D;
            color: white;
            font-weight: bold;
            border-radius: 10px;
            padding: 5px 10px;
            border: none;
            cursor: pointer;
        }

        /* Styling for the modal animation */
        .modal.fade .modal-dialog {
            transform: translateY(-50px);
            opacity: 0;
            transition: all 0.3s ease-out;
        }

        .modal.show .modal-dialog {
            transform: translateY(0);
            opacity: 1;
        }

        /* Styling for form fields */
        .modal-content {
            border-radius: 20px;
            padding: 20px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
            position: relative;
        }

        .modal-content input,
        .modal-content select,
        .modal-content button {
            width: 100%;
            margin-bottom: 10px;
            padding: 10px;
            border-radius: 10px;
            border: 1px solid #ddd;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .modal-content input:focus,
        .modal-content select:focus {
            border-color: #FFD000;
            box-shadow: 0 0 8px rgba(255, 208, 0, 0.5);
            outline: none;
        }

        .modal-content button {
            background-color: #FFD000;
            color: #161925;
            font-weight: bold;
            border: none;
            cursor: pointer;
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

        .progress-bar.exceeded {
    background-color: red !important;
}

    </style>
</head>
<body>
<?php include '../navbar.php'; ?>
    <div class="container mt-4">
        <h1 class="budget-header">My Budgets</h1>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <button id="add-budget-btn" class="add-budget-btn">
                <i class="fas fa-plus"></i> Add Budget
            </button>
        </div>

        <div class="card p-4 shadow-sm" style="border-radius: 20px;">
            <h3 class="mb-4">Budget Overview</h3>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Amount (RM)</th>
                        <th>Progress</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="budgets-list">
                    <!-- Rows will be populated dynamically -->
                </tbody>
            </table>
        </div>
    </div>

    <!-- Budget Modal -->
    <div id="budget-modal" class="modal fade" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <button type="button" class="btn-close-custom" data-bs-dismiss="modal">&times;</button>
                <div class="modal-header">
                    <h5 class="modal-title">Add/Edit Budget</h5>
                </div>
                <form id="budget-form">
                    <div class="modal-body">
                        <input type="hidden" name="budget_id" id="budget_id">
                        <input type="text" name="title" id="budget-title" placeholder="Budget Title" required>
                        <select name="category" id="budget-category" required>
                            <optgroup label="Expense">
                                    <option value="Academic Expenses">Academic Expenses</option>
                            <option value="Rent">Rent</option>
                            <option value="Utilities">Utilities</option>
                            <option value="Groceries">Groceries</option>
                            <option value="Dining out">Dining Out</option>
                            <option value="Transportation">Transportation</option>
                            <option value="Personal Care">Personal Care</option>
                            <option value="Entertainment">Entertainment</option>
                            <option value="Technology">Technology</option>
                            <option value="Savings and Investments">Savings and Investments</option>
                            <option value="Miscellaneous">Miscellaneous</option>
                            <option value="Clothing">Clothing</option>
                            </optgroup>
                        </select>
                        <input type="number" name="amount" id="budget-amount" placeholder="Amount" required>
                        <input type="date" name="start_date" id="budget-start" required>
                        <input type="date" name="end_date" id="budget-end" required>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const budgetsList = document.getElementById('budgets-list');
            const budgetModal = new bootstrap.Modal(document.getElementById('budget-modal'));
            const budgetForm = document.getElementById('budget-form');
            const addBudgetBtn = document.getElementById('add-budget-btn');

            async function fetchBudgets() {
                try {
                    const response = await fetch('budgets_process.php', { method: 'GET' });
                    const data = await response.json();
                    renderBudgets(data);
                } catch (error) {
                    console.error("Error fetching budgets:", error);
                }
            }
            function renderBudgets(budgets) {
    budgetsList.innerHTML = ''; // Clear previous rows

    budgets.forEach(budget => {
        const progressPercentage = budget.UtilizedAmount && budget.BudgetAmt
            ? Math.min((budget.UtilizedAmount / budget.BudgetAmt) * 100, 100).toFixed(2)
            : 0;

        const notification = budget.Status === "Exceeded"
            ? `<span style="color: red;">Budget exceeded by RM${(budget.UtilizedAmount - budget.BudgetAmt).toFixed(2)}</span>`
            : `You can spend RM${budget.DailyLimit.toFixed(2)} per day until ${budget.BudgetEnd}.`;

        const row = document.createElement('tr');

        row.innerHTML = `
            <td>${budget.BudgetTitle}</td>
            <td>${budget.BudgetCat}</td>
            <td>RM${parseFloat(budget.BudgetAmt).toFixed(2)}</td>
            <td>
                <div class="progress">
                    <div class="progress-bar" role="progressbar" style="width: ${progressPercentage}%">
                        ${progressPercentage}%
                    </div>
                </div>
                <small>RM${parseFloat(budget.UtilizedAmount).toFixed(2)} used</small>
            </td>
            <td>${budget.BudgetStart}</td>
            <td>${budget.BudgetEnd}</td>
            <td>${notification}</td>
            <td>
                <button class="btn-edit" onclick='editBudget(${JSON.stringify(budget)})'>Edit</button>
                <button class="btn-delete" onclick='deleteBudget(${budget.BudgetID})'>Delete</button>
            </td>
        `;

        budgetsList.appendChild(row);
    });
}



            addBudgetBtn.addEventListener('click', () => {
                budgetForm.reset();
                document.getElementById('budget_id').value = '';
                budgetModal.show();
            });

            budgetForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const formData = new FormData(budgetForm);
                formData.append('action', 'save');
                try {
                    const response = await fetch('budgets_process.php', {
                        method: 'POST',
                        body: formData
                    });
                    const data = await response.json();
                    alert(data.success || data.error);
                    if (data.success) {
                        budgetModal.hide();
                        fetchBudgets();
                    }
                } catch (error) {
                    console.error("Error saving budget:", error);
                }
            });

            window.editBudget = (budget) => {
                document.getElementById('budget_id').value = budget.BudgetID;
                document.getElementById('budget-title').value = budget.BudgetTitle;
                document.getElementById('budget-category').value = budget.BudgetCat;
                document.getElementById('budget-amount').value = budget.BudgetAmt;
                document.getElementById('budget-start').value = budget.BudgetStart;
                document.getElementById('budget-end').value = budget.BudgetEnd;
                budgetModal.show();
            };

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
                        alert(data.success || data.error);
                        fetchBudgets();
                    } catch (error) {
                        console.error("Error deleting budget:", error);
                    }
                }
            };

            fetchBudgets();
        });

        const progressBarClass = budget.Status === "Exceeded" ? "exceeded" : "";
row.innerHTML = `
    <div class="progress-bar ${progressBarClass}" role="progressbar" style="width: ${progressPercentage}%">
        ${progressPercentage}%
    </div>
`;

    </script>
</body>
</html>
