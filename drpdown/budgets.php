<?php
session_start();
include '../config.php';
include '../navbar.php';

// Check if the user is logged in, if not redirect to login page
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php");
    exit;
}

// Get the logged-in user's ID
$user_id = $_SESSION['id']; // Use session user ID
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
            const budgetForm = document.getElementById('budget-form');
            const addBudgetBtn = document.getElementById('add-budget-btn');
            let submitted = false;

            // Add Budget button click handler
            addBudgetBtn.addEventListener('click', () => {
                budgetForm.reset();
                document.getElementById('budget_id').value = '';
                budgetModal.style.display = 'block';
            });

            // Function to get appropriate icon based on category
            function getCategoryIcon(category) {
                const icons = {
                    'Groceries': 'fa-shopping-basket',
                    'Transportation': 'fa-car',
                    'Shopping': 'fa-shopping-bag',
                    'Dining Out': 'fa-utensils',
                    'Entertainment': 'fa-film',
                    'Utilities': 'fa-bolt',
                    'Rent': 'fa-home',
                    'Other': 'fa-wallet'
                };
                return icons[category] || 'fa-wallet';
            }

            async function fetchBudgets() {
                try {
                    const response = await fetch('budgets_process.php', {
                        method: 'GET'
                    });
                    const budgets = await response.json();
                    const transactions = await fetchTransactions();
                    renderBudgets(budgets, transactions);
                } catch (error) {
                    console.error("Error fetching budgets:", error);
                }
            }

            async function fetchTransactions() {
                try {
                    const response = await fetch('tran_process.php', {
                        method: 'GET'
                    });
                    return await response.json();
                } catch (error) {
                    console.error("Error fetching transactions:", error);
                    return [];
                }
            }

            function renderBudgets(budgets, transactions) {
                budgetsList.innerHTML = '';

                budgets.forEach(budget => {
                    const spent = calculateSpent(budget, transactions);
                    const progress = (spent / budget.BudgetAmt) * 100;
                    const budgetDiv = document.createElement('div');
                    budgetDiv.classList.add('budget-item');
                    
                    const icon = getCategoryIcon(budget.BudgetCat);
                    budgetDiv.innerHTML = `
                        <div class="budget-icon">
                            <i class="fas ${icon}"></i>
                        </div>
                        <div class="budget-details">
                            <div class="budget-header">
                                <h3>${budget.BudgetTitle}</h3>
                                <div class="budget-actions">
                                    <button onclick='editBudget(${JSON.stringify(budget)})' class="edit-btn">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <button onclick="deleteBudget(${budget.BudgetID})" class="delete-btn">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </div>
                            </div>
                            <div class="budget-amounts">
                                <span class="total-balance">Total Balance: RM${budget.BudgetAmt.toFixed(2)}</span>
                                <span class="total-expense">Total Expense: RM${spent.toFixed(2)}</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress" style="width: ${progress}%"></div>
                            </div>
                            <div class="budget-status">
                                ${progress.toFixed(0)}% of your budget used
                            </div>
                        </div>
                    `;
                    budgetsList.appendChild(budgetDiv);
                });
            }

            function calculateSpent(budget, transactions) {
                return transactions
                    .filter(tran => tran.TranCat === budget.BudgetCat && tran.TranType === 'Expense')
                    .reduce((total, tran) => total + parseFloat(tran.TranAmount), 0);
            }

            // Close modal when clicking X or outside
            closeModal.addEventListener('click', () => {
                budgetModal.style.display = 'none';
            });

            window.addEventListener('click', (event) => {
                if (event.target === budgetModal) {
                    budgetModal.style.display = 'none';
                }
            });

            budgetForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                if (submitted) return;
                submitted = true;

                const formData = new FormData(budgetForm);
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
            };

            fetchBudgets();
        });
    </script>
</body>
</html>
