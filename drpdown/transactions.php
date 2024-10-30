<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Transactions - MoneyCraft</title>
    <link rel="stylesheet" href="styles.css">
    <script src="script.js" defer></script>
</head>
<body>
    <div class="container">
        <header>
            <h1>My Transactions</h1>
            <div class="balance-container">
                <div class="balance">
                    <h2>Income</h2>
                    <p id="income">RM0.00</p>
                </div>
                <div class="balance">
                    <h2>Expense</h2>
                    <p id="expense">RM0.00</p>
                </div>
                <div class="balance">
                    <h2>Total Balance</h2>
                    <p id="total-balance">RM0.00</p>
                </div>
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
                <input type="text" name="title" id="title" placeholder="Transaction Title" required>
                <select name="type" id="type" required>
                    <option value="Income">Income</option>
                    <option value="Expense">Expense</option>
                </select>
                <input type="number" name="amount" id="amount" placeholder="Amount (RM)" required>
                <input type="date" name="date" id="date" required>
                <button type="submit">Save</button>
            </form>
        </div>
    </div>

    <script src="script.js"></script>
</body>
</html>
