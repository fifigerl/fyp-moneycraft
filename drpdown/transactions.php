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

<style>
    /* General Styles */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: Arial, sans-serif;
}

body {
    background-color: #FAFAF6;
    color: #333;
}

/* Container and Header */
.container {
    width: 100%;
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

header {
    text-align: center;
    margin-bottom: 30px;
}

header h1 {
    font-size: 2rem;
    color: #5F2D86;
}

/* Balance Container */
.balance-container {
    display: flex;
    justify-content: center;
    gap: 30px;
    margin-top: 20px;
}

.balance {
    background-color: #FEC925;
    padding: 20px;
    border-radius: 10px;
    width: 150px;
    text-align: center;
}

.balance h2 {
    font-size: 1rem;
    color: #333;
}

.balance p {
    font-size: 1.5rem;
    font-weight: bold;
    color: #333;
}

/* Transaction History */
.transaction-history {
    margin-top: 20px;
}

.transaction-history h3 {
    font-size: 1.5rem;
    color: #5F2D86;
    margin-bottom: 20px;
    text-align: center;
}

#add-transaction {
    background-color: #FEC925;
    color: #fff;
    border: none;
    padding: 10px 20px;
    border-radius: 5px;
    font-size: 1rem;
    cursor: pointer;
    margin-bottom: 20px;
}

#add-transaction:hover {
    background-color: #fcb915;
}

/* Transactions List */
#transactions-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.transaction-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background-color: #fff;
    padding: 15px;
    border-radius: 8px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    border-left: 5px solid #FEC925;
}

.transaction-item .icon {
    background-color: #FEC925;
    color: #fff;
    padding: 10px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
}

.transaction-details {
    flex: 1;
    margin-left: 20px;
}

.transaction-details h4 {
    font-size: 1rem;
    color: #333;
    margin-bottom: 5px;
}

.transaction-details p {
    font-size: 0.9rem;
    color: #666;
}

/* Modal */
.modal {
    display: none;
    position: fixed;
    z-index: 1;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
}

.modal-content {
    background-color: #fff;
    margin: 15% auto;
    padding: 20px;
    border-radius: 8px;
    width: 80%;
    max-width: 500px;
    position: relative;
}

.close {
    color: #aaa;
    font-size: 24px;
    font-weight: bold;
    position: absolute;
    top: 10px;
    right: 10px;
    cursor: pointer;
}

.close:hover {
    color: #000;
}

#transaction-form input, #transaction-form select, #transaction-form button {
    width: 100%;
    padding: 10px;
    margin-bottom: 10px;
    border: 1px solid #ddd;
    border-radius: 5px;
}

#transaction-form button {
    background-color: #5F2D86;
    color: #fff;
    cursor: pointer;
    font-size: 1rem;
}

#transaction-form button:hover {
    background-color: #4d2269;
}

</style>