<?php include '../config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Budgets</title>
    <link rel="stylesheet" href="../styles.css">
    <script src="script.js" defer></script>
</head>
<body>
    <div class="container">
        <h1>Budgets</h1>
        <button id="add-budget-btn">+ Add Budget</button>
        <div id="budgets-list"></div>
        <h2>Budget vs Spending</h2>
        <div id="budget-summary"></div>
    </div>

    <div id="budget-modal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <form id="budget-form">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="budget_id" id="budget_id">
                <input type="text" name="title" id="budget-title" placeholder="Budget Title" required>
                <input type="text" name="category" id="budget-category" placeholder="Category">
                <input type="number" name="amount" id="budget-amount" placeholder="Amount" required>
                <input type="date" name="start_date" id="budget-start" required>
                <input type="date" name="end_date" id="budget-end" required>
                <button type="submit">Save</button>
            </form>
        </div>
    </div>
</body>
</html>
