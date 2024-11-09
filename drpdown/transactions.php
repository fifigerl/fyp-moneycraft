<?php
include '../config.php';
$userId = 1;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Transactions - MoneyCraft</title>
    <link rel="stylesheet" href="../styles.css">
    <script src="script.js" defer></script>
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
                    <option value="Food">Food</option>
                    <option value="Transport">Transport</option>
                    <option value="Shopping">Shopping</option>
                    <option value="Entertainment">Entertainment</option>
                </select>
                <input type="number" name="amount" id="tran_amount" placeholder="Amount (RM)" required>
                <input type="date" name="date" id="tran_date" required>
                <button type="submit">Save Transaction</button>
            </form>
        </div>
    </div>
</body>
</html>
