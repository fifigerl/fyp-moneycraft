<?php
session_start();
include '../config.php';
include '../navbar.php'; 

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
    <title>My Transactions - MoneyCraft</title>
    <link rel="stylesheet" href="../styles.css">
    <link rel="stylesheet" href="navbar.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>My Transactions</h1>
            <div class="balance-container">
                <div class="balance"><h2>Income</h2><p id="income">RM0.00</p></div>
                <div class="balance"><h2>Expense</h2><p id="expense">RM0.00</p></div>
                <div class="balance"><h2>Total Balance</h2><p id="total-balance">RM0.00</p></div>
            </div>
        </header>
        <section class="transaction-history">
            <h3>Transaction History</h3>
            <button id="add-transaction">+ Add Transaction</button>
            <div id="transactions-list"></div>
        </section>

    </div>

    <div id="transaction-modal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <form id="transaction-form">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="tran_id" id="tran_id">
                <input type="text" name="title" id="tran_title" placeholder="Transaction Note" required>
                <select name="type" id="tran_type" required>
                    <option value="Income">Income</option>
                    <option value="Expense">Expense</option>
                </select>
                <select name="category" id="tran_category" required>
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
                <input type="number" name="amount" id="tran_amount" placeholder="Amount (RM)" required>
                <input type="date" name="date" id="tran_date" required>
                <button type="submit">Save Transaction</button>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const transactionsList = document.getElementById('transactions-list');
            const transactionModal = document.getElementById('transaction-modal');
            const closeModal = document.querySelector('.close');
            const addTransactionBtn = document.getElementById('add-transaction');
            const transactionForm = document.getElementById('transaction-form');
            const incomeDisplay = document.getElementById('income');
            const expenseDisplay = document.getElementById('expense');
            const totalBalanceDisplay = document.getElementById('total-balance');
            let submitted = false;

            async function fetchTransactions() {
                try {
                    const response = await fetch('tran_process.php', {
                        method: 'GET'
                    });
                    const data = await response.json();
                    renderTransactions(data);
                    calculateBalances(data);
                } catch (error) {
                    console.error("Error fetching transactions:", error);
                }
            }

            function renderTransactions(transactions) {
                transactionsList.innerHTML = '';

                transactions.forEach(tran => {
                    const tranDiv = document.createElement('div');
                    tranDiv.classList.add('transaction');
                    tranDiv.innerHTML = `
                        <p>${tran.TranTitle} - ${tran.TranType} - RM${parseFloat(tran.TranAmount).toFixed(2)} - ${tran.TranDate}</p>
                        <button onclick="deleteTransaction(${tran.TranID})">Delete</button>
                        <button onclick='editTransaction(${JSON.stringify(tran)})'>Edit</button>
                    `;
                    transactionsList.appendChild(tranDiv);
                });
            }

            function calculateBalances(transactions) {
                let totalIncome = 0;
                let totalExpense = 0;

                transactions.forEach(tran => {
                    if (tran.TranType === 'Income') {
                        totalIncome += parseFloat(tran.TranAmount);
                    } else if (tran.TranType === 'Expense') {
                        totalExpense += parseFloat(tran.TranAmount);
                    }
                });

                const totalBalance = totalIncome - totalExpense;

                incomeDisplay.textContent = `RM${totalIncome.toFixed(2)}`;
                expenseDisplay.textContent = `RM${totalExpense.toFixed(2)}`;
                totalBalanceDisplay.textContent = `RM${totalBalance.toFixed(2)}`;
            }

            addTransactionBtn.addEventListener('click', () => {
                transactionForm.reset();
                document.getElementById('tran_id').value = '';
                transactionModal.style.display = 'block';
            });

            closeModal.addEventListener('click', () => {
                transactionModal.style.display = 'none';
            });

            transactionForm.addEventListener('submit', async (e) => {
                e.preventDefault();

                if (submitted) return;
                submitted = true;

                const formData = new FormData(transactionForm);
                formData.append("action", "save");

                try {
                    const response = await fetch('tran_process.php', {
                        method: 'POST',
                        body: formData
                    });
                    const data = await response.json();
                    alert(data.success || data.error);
                    if (data.success) {
                        transactionModal.style.display = 'none';
                        transactionForm.reset();
                        fetchTransactions();
                    }
                } catch (error) {
                    console.error("Error submitting transaction:", error);
                } finally {
                    submitted = false;
                }
            });

            window.deleteTransaction = async (tranID) => {
                if (confirm('Are you sure you want to delete this transaction?')) {
                    const formData = new FormData();
                    formData.append('action', 'delete');
                    formData.append('tran_id', tranID);

                    try {
                        const response = await fetch('tran_process.php', {
                            method: 'POST',
                            body: formData
                        });
                        const data = await response.json();
                        alert(data.success || data.error);
                        fetchTransactions();
                    } catch (error) {
                        console.error("Error deleting transaction:", error);
                    }
                }
            };

            window.editTransaction = (tran) => {
                document.getElementById('tran_id').value = tran.TranID;
                document.getElementById('tran_title').value = tran.TranTitle;
                document.getElementById('tran_type').value = tran.TranType;
                document.getElementById('tran_category').value = tran.TranCat;
                document.getElementById('tran_amount').value = tran.TranAmount;
                document.getElementById('tran_date').value = tran.TranDate;
                transactionModal.style.display = 'block';
            };

            fetchTransactions();
        });
    </script>
</body>
</html>
