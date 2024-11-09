<?php include '../config.php'; ?>
<?php include '../navbar.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Budgets</title>
    <link rel="stylesheet" href="../styles.css">
    <link rel="stylesheet" href="navbar.css">
</head>
<body>
    <div class="container">
        <h1>My Budgets</h1>
        <button id="add-budget-btn">+ Add Budget</button>
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
                    <optgroup label="Income">
                        <option value="Allowance">Allowance</option>
                        <option value="Part-time Job">Part-time Job</option>
                        <option value="Scholarships">Scholarships</option>
                        <option value="Student Loans">Student Loans</option>
                        <option value="Freelance Work">Freelance Work</option>
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
                    budgetDiv.classList.add('budget');
                    budgetDiv.innerHTML = `
                        <div class="budget-header">
                            <h3>${budget.BudgetTitle}</h3>
                            <p>RM${spent.toFixed(2)} / RM${budget.BudgetAmt.toFixed(2)}</p>
                        </div>
                        <div class="progress-bar">
                            <div class="progress" style="width: ${progress}%;"></div>
                        </div>
                        <p>${progress.toFixed(2)}% of your budget used</p>
                        <button onclick="deleteBudget(${budget.BudgetID})">Delete</button>
                        <button onclick='editBudget(${JSON.stringify(budget)})'>Edit</button>
                    `;
                    budgetsList.appendChild(budgetDiv);
                });
            }

            function calculateSpent(budget, transactions) {
                return transactions
                    .filter(tran => tran.TranCat === budget.BudgetCat && tran.TranType === 'Expense')
                    .reduce((total, tran) => total + parseFloat(tran.TranAmount), 0);
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
                    alert(data.success || data.error);
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
                        alert(data.success || data.error);
                        fetchBudgets();
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
                document.getElementById('budget-start').value = budget.BudgetStart;
                document.getElementById('budget-end').value = budget.BudgetEnd;
                budgetModal.style.display = 'block';
            };

            async function fetchCategories() {
                try {
                    const response = await fetch('category_process.php', {
                        method: 'GET'
                    });
                    const data = await response.json();
                    renderCategoryOptions(data);
                } catch (error) {
                    console.error("Error fetching categories:", error);
                }
            }

            function renderCategoryOptions(categories) {
                const categorySelect = document.getElementById('budget-category');
                categorySelect.innerHTML = '';

                categories.forEach(category => {
                    const option = document.createElement('option');
                    option.value = category.Name;
                    option.textContent = category.Name;
                    categorySelect.appendChild(option);
                });
            }

            fetchBudgets();
            fetchCategories();
        });
    </script>
</body>
</html>
