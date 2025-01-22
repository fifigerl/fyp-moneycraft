<?php
session_start();
require_once '../config.php';

// Include the logging function
function logUserActivity($conn, $userId, $username, $action, $type = null) {
    $stmt = $conn->prepare("INSERT INTO UserActivities (UserID, Username, Action, Type) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $userId, $username, $action, $type);
    $stmt->execute();
    $stmt->close();
}

// Check if the user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php");
    exit;
}

// Get the logged-in user's ID and username
$user_id = $_SESSION['id'];
$username = $_SESSION['username'];

// Log the action of viewing the transactions page
logUserActivity($conn, $user_id, $username, "Viewed Transactions Page", "View");

// Fetch cash flow data for the trend chart
$sql = "SELECT 
            DATE_FORMAT(TranDate, '%Y-%m') AS month,
            SUM(CASE WHEN TranType = 'Income' THEN TranAmount ELSE 0 END) AS total_income,
            SUM(CASE WHEN TranType = 'Expense' THEN TranAmount ELSE 0 END) AS total_expenses
        FROM Transactions
        WHERE UserID = ?
        GROUP BY month
        ORDER BY month";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$trend_labels = [];
$income_trend = [];
$expense_trend = [];

while ($row = $result->fetch_assoc()) {
    $trend_labels[] = $row['month'];
    $income_trend[] = $row['total_income'] ?? 0;
    $expense_trend[] = $row['total_expenses'] ?? 0;
}




include '../navbar.php'; 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Transactions - MoneyCraft</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/tesseract.js@6.0.0/dist/tesseract.min.js"></script>

    <!-- Inline Styles -->
    <style>
        body {
            background-color: #F8F9FA;
            color: #161925;
            font-family: 'Inter', sans-serif;
        }

        h1.tran-header {
    font-weight: 900;
    font-size: 48px; /* Increase the font size */
    letter-spacing: 2px; /* Add letter spacing for a wider appearance */
    color: rgb(0, 35, 72); /* Retain the existing color */
    margin-bottom: 20px;
    text-align: left; /* Optional: Center-align the text */
}


        .tran-container {
            padding: 20px;
        }

        .tran-balance-container {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 30px;
        }

        .tran-balance-card {
            background-color: #FFFFFF;
            border-radius: 20px;
            padding: 15px;
            flex: 1;
            
            text-align: center;
        }

        .tran-balance-card h2 {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 10px;
            color: #161925;
        }

        .tran-balance-card p {
            font-size: 16px;
            font-weight: bold;
            color: #FFD000;
        }

        .tran-history-section {
            margin-top: 20px;
        }

        .tran-history-title {
            font-size: 20px;
            font-weight: bold;
            color: #161925;
            margin-bottom: 10px;
        }

        .tran-add-button {
            background-color: #FFD000;
            color: #161925;
            font-weight: bold;
            border-radius: 15px;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
        }

        .tran-add-button:hover {
            background-color: #FDF09D;
        }

        .tran-list {
            margin-top: 20px;
        }

        .tran-item {
            background-color: #FFFFFF;
            border-radius: 20px;
            padding: 15px;
            margin-bottom: 10px;
         
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .tran-item p {
            margin: 0;
            font-size: 16px;
            color: #161925;
        }

        .tran-item button {
            background-color: #FFD000;
            color: #161925;
            font-weight: bold;
            border: none;
            padding: 5px 10px;
            border-radius: 10px;
            cursor: pointer;
        }

        .tran-item button:hover {
            background-color: #FDF09D;
        }

        .tran-modal {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: #FFFFFF;
            padding: 20px;
            border-radius: 20px;
           
            z-index: 1000;
            width: 90%;
            max-width: 400px;
        }

        .tran-modal.active {
            display: block;
        }

        .tran-modal form input,
        .tran-modal form select,
        .tran-modal form button {
            width: 100%;
            margin-bottom: 10px;
            padding: 10px;
            border-radius: 10px;
            border: 1px solid #ddd;
            font-size: 14px;
        }

        .tran-modal form button {
            background-color: #FFD000;
            color: #161925;
            font-weight: bold;
            border: none;
        }

        .tran-modal form button:hover {
            background-color: #FDF09D;
        }

        .tran-modal .close {
            position: absolute;
            top: 10px;
            right: 15px;
            cursor: pointer;
            color: #161925;
            font-size: 18px;
        }

        .tran-card-container {
    display: flex;
    flex-direction: column;
    gap: 15px;
    margin-top: 20px;
}

.tran-card {
    background-color: #FFFFFF;
    border-radius: 20px;
    padding: 15px;
    
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.tran-card .tran-info {
    flex: 1;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
}

.tran-card .tran-info p {
    margin: 0;
    font-size: 16px;
    color: #161925;
}

.tran-card .tran-info .amount {
    font-weight: bold;
}

.tran-card .tran-info .amount.income {
    color: #00B74A; /* Green for Income */
}

.tran-card .tran-info .amount.expense {
    color: #FF0000; /* Red for Expense */
}

.tran-card .tran-actions button {
    background-color: #FFD000;
    color: #161925;
    font-weight: bold;
    border: none;
    padding: 5px 10px;
    border-radius: 10px;
    cursor: pointer;
    margin-left: 5px;
}

.tran-card .tran-actions button:hover {
    background-color: #FDF09D;
}

/* Three-dot menu icon and actions */
.tran-actions {
    display: flex;
    gap: 10px;
    margin-top: 10px;
}

.tran-actions button {
    background-color: #FFD000;
    color: #161925;
    font-weight: bold;
    border: none;
    padding: 5px 10px;
    border-radius: 10px;
    cursor: pointer;
}

.tran-actions button:hover {
    background-color: #FDF09D;
}

.fa-ellipsis-vertical {
    color: #161925;
    font-size: 18px;
    margin-left: 10px;
}
.tran-filter {
    display: flex;
    align-items: center;
    gap: 10px;
}

#filter-date {
    padding: 5px 10px;
    border-radius: 10px;
    border: 1px solid #ddd;
    font-size: 14px;
}
.tran-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.tran-info p {
    margin: 0;
}

.tran-info .date {
    flex: 1;
    text-align: left;
}

.tran-info .title {
    flex: 3;
    text-align: left;
}

.tran-info .type {
    flex: 2;
    text-align: center;
}

.tran-info .amount {
    flex: 1;
    text-align: right;
}
.tran-balance-card p {
    font-size: 16px;
    font-weight: bold;
}

.tran-balance-card p#income {
    color: #00B74A; /* Green for Income */
    text-align: center;
}

.tran-balance-card p#expense {
    color: #FF0000; /* Red for Expense */
    text-align: center;
}

.tran-balance-card p#total-balance {
    color: #161925; /* Neutral color for Total Balance */
    text-align: center;
}


    </style>
</head>
<body>
    <div class="tran-container">
        <h1 class="tran-header">My Transactions</h1>

        <div class="tran-cashflow-card" style="background-color: #FFFFFF; border-radius: 20px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); margin-bottom: 30px; padding: 20px;">
    <h3 style="font-size: 20px; font-weight: bold; color: #161925; margin-bottom: 20px;">
    <i class="fa-solid fa-chart-line" style="color: #FFD000; margin-right: 10px;"></i> Cash Flow Trends
    </h3>
    <div id="cashFlowTrendChart" style="height: 350px;"></div>
</div>



        <!-- Balance Section -->
        <div class="tran-balance-container">
            <div class="tran-balance-card">
            <h2>
            <i class="fa-solid fa-wallet" style="color: #FFD000; margin-right: 10px;"></i> Income
        </h2>
        <p id="income">+ RM0.00</p>

            </div>
            <div class="tran-balance-card">
            <h2>
            <i class="fa-solid fa-money-bill-wave" style="color: #FFD000; margin-right: 10px;"></i> Expense
        </h2>
        <p id="expense">- RM0.00</p>
            </div>
            <div class="tran-balance-card">
            <h2>
            <i class="fa-solid fa-balance-scale" style="color: #FFD000; margin-right: 10px;"></i> Total Balance
        </h2>
                <p id="total-balance">RM0.00</p>
            </div>
        </div>
        

        <!-- Transaction History Section -->
        <section class="tran-history-section">
        <h3 class="tran-history-title">
    <i class="fas fa-exchange-alt" style="color: #FFD000; margin-right: 10px;"></i>
    Transaction History
</h3>


<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
    <button id="add-transaction" class="tran-add-button">+ Add New Transaction</button>
    <button id="scan-receipt" class="tran-add-button" style="background-color: #c5c5c5;">Scan Receipt</button>

    <!-- Filter by Date -->
    <div class="tran-filter">
        <label for="filter-date" style="font-weight: bold; margin-right: 10px;">Filter by Date:</label>
        <input type="date" id="filter-date" style="padding: 5px; border-radius: 10px; border: 1px solid #ddd;">
    </div>
</div>
<div style="margin-bottom: 15px;">
       <!-- Transaction Header -->
<div style="display: flex; justify-content: space-between; font-weight: bold; background-color: #F0F0F0; padding: 10px; border-radius: 10px; margin-bottom: 15px;">
    <p style="flex: 1.1; text-align: left; margin: 0;">Date</p>
    <p style="flex: 2.9; text-align: left; margin: 0;">Title</p>
    <p style="flex: 2.5; text-align: center; margin: 0;">Type</p>
    <p style="flex: 1; text-align: right; margin: 0;">Amount</p>
</div>

<!-- Transaction List -->
<div id="transactions-list" class="tran-card-container">
    <!-- Transactions will be dynamically rendered here -->
</div>


<div id="transactions-list" class="tran-card-container">
    <!-- Transactions will be dynamically rendered here -->
</div>



</section>


        </section>
    </div>

    <!-- Transaction Modal -->
    <div id="transaction-modal" class="tran-modal">
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


                    <!-- Other options -->
                </optgroup>
                <optgroup label="Income">
                    <option value="Allowance">Allowance</option>
                    <option value="Part-time Job">Part-time Job</option>
                    <option value="Passive Income">Passive Income</option>
                    <option value="Scholarships and Grants">Scholarships and Grants</option>
                    <option value="Student Loans">Student Loans</option>
                    <option value="Internships">Internships</option>
                    <option value="Freelancing">Freelancing</option>
                    <option value="Content Cretion">Content Creation</option>
                    <option value="Small Business">Small Business</option>
                    <option value="Miscellaneous Income">Miscellaneous Income</option>
                    <!-- Other options -->
                </optgroup>
            </select>
            <input type="number" name="amount" id="tran_amount" placeholder="Amount (RM)" required>
            <input type="date" name="date" id="tran_date" required>
            <button type="submit">Save Transaction</button>
        </form>
    </div>

   <div id="scan-receipt-modal" class="tran-modal">
    <span class="close">&times;</span>
    <div style="position: relative; text-align: center;">
        <video id="camera-stream" autoplay playsinline style="width: 100%; border-radius: 15px;"></video>
        <canvas id="receipt-canvas" style="display: none;"></canvas>
        <!-- Overlay -->
        <div id="camera-overlay"></div>
        <button id="capture-receipt" class="tran-add-button" style="margin-top: 10px;">Capture Receipt</button>
    </div>
</div>


    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Prepare data for the chart
        const cashFlowLabels = <?php echo json_encode($cash_flow_labels); ?>;
        const incomeData = <?php echo json_encode($income_data); ?>;
        const expenseData = <?php echo json_encode($expense_data); ?>;

        // Configure the ApexChart
        const cashFlowOptions = {
            chart: {
                type: 'line',
                height: 350
            },
            series: [
                {
                    name: 'Income',
                    data: incomeData
                },
                {
                    name: 'Expenses',
                    data: expenseData
                }
            ],
            xaxis: {
                categories: cashFlowLabels,
                title: {
                    text: 'Month',
                    style: {
                        fontWeight: 'bold'
                    }
                }
            },
            yaxis: {
                title: {
                    text: 'Amount (RM)',
                    style: {
                        fontWeight: 'bold'
                    }
                }
            },
            colors: ['#00FF00', '#FF0000'], // Green for Income, Red for Expenses
            markers: {
                size: 5
            },
            stroke: {
                width: 2
            },
            tooltip: {
                shared: true,
                intersect: false
            }
        };

        // Render the chart
        const cashFlowChart = new ApexCharts(document.querySelector("#cashFlowChart"), cashFlowOptions);
        cashFlowChart.render();
    });
</script>

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

            async function fetchTransactions(dateFilter = null) {
    try {
        const url = dateFilter 
            ? `tran_process.php?date=${encodeURIComponent(dateFilter)}` 
            : 'tran_process.php';

        const response = await fetch(url, { method: 'GET' });
        const data = await response.json();
        renderTransactions(data);
        calculateBalances(data);
    } catch (error) {
        console.error("Error fetching transactions:", error);
    }
}



            function renderTransactions(transactions) {
    const transactionsList = document.getElementById('transactions-list');
    transactionsList.innerHTML = ''; // Clear the list before rendering

    transactions.forEach(tran => {
        // Create a transaction card
        const tranCard = document.createElement('div');
        tranCard.classList.add('tran-card');

        // Transaction information
        const tranInfo = document.createElement('div');
        tranInfo.classList.add('tran-info');

        const date = document.createElement('p');
date.textContent = tran.TranDate;
date.className = 'date';

const title = document.createElement('p');
title.textContent = tran.TranTitle;
title.className = 'title';

const type = document.createElement('p');
type.textContent = tran.TranType;
type.className = 'type';

const amount = document.createElement('p');
if (tran.TranType === 'Income') {
    amount.textContent = `+ RM${parseFloat(tran.TranAmount).toFixed(2)}`;
    amount.classList.add('amount', 'income');
} else if (tran.TranType === 'Expense') {
    amount.textContent = `- RM${parseFloat(tran.TranAmount).toFixed(2)}`;
    amount.classList.add('amount', 'expense');
}

        tranInfo.append(date, title, type, amount);

        // Action buttons (hidden by default)
        const tranActions = document.createElement('div');
        tranActions.classList.add('tran-actions');
        tranActions.style.display = 'none';

        const editButton = document.createElement('button');
        editButton.textContent = 'Edit';
        editButton.onclick = () => editTransaction(tran);

        const deleteButton = document.createElement('button');
        deleteButton.textContent = 'Delete';
        deleteButton.onclick = () => deleteTransaction(tran.TranID);

        tranActions.append(editButton, deleteButton);

        // Three-dot menu icon
        const actionMenu = document.createElement('i');
        actionMenu.className = 'fa-solid fa-ellipsis-vertical';
        actionMenu.style.cursor = 'pointer';
        actionMenu.onclick = () => {
            // Toggle visibility of action buttons
            tranActions.style.display = tranActions.style.display === 'none' ? 'flex' : 'none';
        };

        // Combine transaction info, menu icon, and action buttons
        tranCard.append(tranInfo, actionMenu, tranActions);

        // Add the card to the transactions list
        transactionsList.appendChild(tranCard);
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

                incomeDisplay.textContent = `+ RM${totalIncome.toFixed(2)}`;
                expenseDisplay.textContent = `- RM${totalExpense.toFixed(2)}`;
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
                        document.getElementById('filter-date').addEventListener('change', (event) => {
    const selectedDate = event.target.value;
    fetchTransactions(selectedDate); // Fetch filtered transactions
});



                        // Log the action of adding a transaction
                        logUserActivity(<?php echo $user_id; ?>, "<?php echo $username; ?>", "Added a transaction", "Transaction");
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

                        // Log the action of deleting a transaction
                        logUserActivity(<?php echo $user_id; ?>, "<?php echo $username; ?>", "Deleted a transaction", "Transaction");
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

                // Log the action of editing a transaction
                logUserActivity(<?php echo $user_id; ?>, "<?php echo $username; ?>", "Edited a transaction", "Transaction");
            };

            fetchTransactions();
        });
    </script>

<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Prepare data for the trend chart
        const trendLabels = <?php echo json_encode($trend_labels); ?>;
        const incomeTrendData = <?php echo json_encode($income_trend); ?>;
        const expenseTrendData = <?php echo json_encode($expense_trend); ?>;

        // Configure the ApexChart for zigzag line visualization
        const trendChartOptions = {
            chart: {
                type: 'area', // Spline area chart
                height: 350,
                toolbar: {
                    show: false
                }
            },
            series: [
                {
                    name: 'Income',
                    data: incomeTrendData
                },
                {
                    name: 'Expenses',
                    data: expenseTrendData
                }
            ],
            xaxis: {
                categories: trendLabels,
                title: {
                    text: 'Month',
                    style: {
                        fontWeight: 'bold'
                    }
                }
            },
            yaxis: {
                title: {
                    text: 'Amount (RM)',
                    style: {
                        fontWeight: 'bold'
                    }
                },
                labels: {
                    formatter: (val) => `RM${val.toFixed(2)}`
                }
            },
            colors: ['#00FF00', '#FF0000'], // Green for Income, Red for Expenses
            stroke: {
                width: 2 // Keep the lines thinner for a sharper zigzag appearance
            },
            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.4,
                    opacityTo: 0.1,
                    stops: [0, 90, 100]
                }
            },
            tooltip: {
                shared: true,
                intersect: false,
                y: {
                    formatter: (val) => `RM${val.toFixed(2)}`
                }
            },
            legend: {
                position: 'top',
                horizontalAlign: 'center'
            }
        };

        // Render the chart
        const cashFlowTrendChart = new ApexCharts(document.querySelector("#cashFlowTrendChart"), trendChartOptions);
        cashFlowTrendChart.render();
    });


    document.addEventListener('DOMContentLoaded', () => {
            const scanReceiptBtn = document.getElementById('scan-receipt');
            const scanReceiptModal = document.getElementById('scan-receipt-modal');
            const closeScanReceipt = scanReceiptModal.querySelector('.close');
            const video = document.getElementById('camera-stream');
            const canvas = document.getElementById('receipt-canvas');
            const captureReceiptBtn = document.getElementById('capture-receipt');

            // Open camera and display modal
            scanReceiptBtn.addEventListener('click', async () => {
                try {
                    const stream = await navigator.mediaDevices.getUserMedia({ video: true });
                    video.srcObject = stream;
                    scanReceiptModal.style.display = 'block';
                } catch (error) {
                    alert('Camera access denied or unavailable.');
                }
            });

            // Close modal and stop camera
            closeScanReceipt.addEventListener('click', () => {
                const stream = video.srcObject;
                if (stream) {
                    stream.getTracks().forEach(track => track.stop());
                }
                scanReceiptModal.style.display = 'none';
            });

            // Capture receipt and process it
            captureReceiptBtn.addEventListener('click', () => {
                const ctx = canvas.getContext('2d');
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

// Convert image to grayscale
const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
for (let i = 0; i < imageData.data.length; i += 4) {
    const brightness = Math.max(imageData.data[i], imageData.data[i + 1], imageData.data[i + 2]);
    const avg = (brightness + imageData.data[i] + imageData.data[i + 1] + imageData.data[i + 2]) / 4;
    imageData.data[i] = avg;
    imageData.data[i + 1] = avg;
    imageData.data[i + 2] = avg;
}
ctx.putImageData(imageData, 0, 0);

// Resize the image for faster OCR
const resizedCanvas = document.createElement('canvas');
resizedCanvas.width = canvas.width / 2; // Reduce to 50%
resizedCanvas.height = canvas.height / 2;
resizedCanvas.getContext('2d').drawImage(canvas, 0, 0, resizedCanvas.width, resizedCanvas.height);

// Define a region of interest (ROI)
const roiHeight = resizedCanvas.height * 0.6; // Adjust ROI to 60% of the height
const roiY = resizedCanvas.height - roiHeight;
const croppedCanvas = document.createElement('canvas');
croppedCanvas.width = resizedCanvas.width;
croppedCanvas.height = roiHeight;
croppedCanvas.getContext('2d').drawImage(resizedCanvas, 0, roiY, resizedCanvas.width, roiHeight, 0, 0, resizedCanvas.width, roiHeight);

                // Use Tesseract.js to extract text
                Tesseract.recognize(croppedCanvas.toDataURL(), 'eng', {
    tessedit_char_whitelist: '0123456789.$', // Focus on numbers and currency symbols
    preserve_interword_spaces: '1', // Retain spaces between words for better matching
    psm: 11, // Assume a uniform block of text
})
.then(({ data: { text } }) => {

    console.log('OCR Output:', text); // Log the raw OCR text for debugging

                    // Extract amount
                    const totalMatch = text.match(/(?:total|amount due|amt|sum)[:\s]?\$?(\d+(\.\d{1,2})?)/i);
                    if (totalMatch) {
                        const amount = parseFloat(totalMatch[1]);
                        // Save extracted data to database
                        const saveData = new FormData();
                        saveData.append('action', 'save');
                        saveData.append('title', 'Scanned Receipt');
                        saveData.append('type', 'Expense');
                        saveData.append('category', 'Uncategorized');
                        saveData.append('amount', amount);
                        saveData.append('date', new Date().toISOString().split('T')[0]);

                        fetch('tran_process.php', { method: 'POST', body: saveData })
                            .then(response => response.json())
                            .then(result => {
                                if (result.success) {
                                    alert(result.success);
                                    location.reload(); // Refresh the page
                                } else {
                                    alert(result.error || 'Failed to save transaction.');
                                }
                            })
                            .catch(err => alert('Error saving transaction.'));
                    } else {
    alert('Unable to extract amount. Retrying with larger ROI...');
    // Retry with a larger ROI
    const retryROIHeight = resizedCanvas.height * 0.8; // Expand ROI to 80% of height
    const retryCanvas = document.createElement('canvas');
    retryCanvas.width = resizedCanvas.width;
    retryCanvas.height = retryROIHeight;
    retryCanvas.getContext('2d').drawImage(resizedCanvas, 0, resizedCanvas.height - retryROIHeight, resizedCanvas.width, retryROIHeight, 0, 0, resizedCanvas.width, retryROIHeight);

    // Retry OCR
    Tesseract.recognize(retryCanvas.toDataURL(), 'eng', {
        tessedit_char_whitelist: '0123456789.$',
        preserve_interword_spaces: '1',
        psm: 11,
    })
    .then(({ data: { text: retryText } }) => {
        console.log('Retry OCR Output:', retryText); // Log retry OCR result
        const retryMatch = retryText.match(/(?:total|amount|due|amt|sum|balance|paid)[:\s]*\$?(\d+(\.\d{1,2})?)/i);
        if (retryMatch) {
            const retryAmount = parseFloat(retryMatch[1]);
            alert(`Retry extracted: RM${retryAmount.toFixed(2)}`);
            // Save transaction logic...
        } else {
            alert('Retry failed. Ensure the receipt is clear and well-lit.');
        }
    })
    .catch((err) => {
        alert('Error during retry OCR: ' + err.message);
    });
}

                }).catch(err => alert('Error processing receipt.'));
            });
        });



</script>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const scanReceiptBtn = document.getElementById('scan-receipt');
        const processReceiptBtn = document.getElementById('process-receipt');
        const scanReceiptModal = document.getElementById('scan-receipt-modal');
        const video = document.getElementById('camera-stream');

        // Open modal and start camera
        scanReceiptBtn.addEventListener('click', async () => {
    try {
        // Request access to the back camera
        const stream = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: { exact: "environment" } } // Use back camera
        });
        video.srcObject = stream;
        scanReceiptModal.style.display = 'block';
    } catch (error) {
        console.error("Error accessing camera:", error);

        // Fallback to default camera if back camera is unavailable
        if (error.name === "OverconstrainedError" || error.name === "NotAllowedError") {
            alert("Back camera not available. Using default camera instead.");
            try {
                const stream = await navigator.mediaDevices.getUserMedia({
                    video: true // Default camera
                });
                video.srcObject = stream;
                scanReceiptModal.style.display = 'block';
            } catch (fallbackError) {
                alert(`Error accessing camera: ${fallbackError.message}`);
            }
        } else {
            alert(`Error: ${error.message}`);
        }
    }
});

        // Close modal
        document.querySelector('.close').addEventListener('click', () => {
            const stream = video.srcObject;
            const tracks = stream.getTracks();
            tracks.forEach(track => track.stop());
            scanReceiptModal.style.display = 'none';
        });

        // Process receipt
        processReceiptBtn.addEventListener('click', async () => {
            const response = await fetch('scan_receipt_process.php', { method: 'POST' });
            const result = await response.json();

            if (result.success) {
                alert(`Amount extracted: ${result.amount}`);
                document.getElementById('tran_amount').value = result.amount;
                scanReceiptModal.style.display = 'none';
            } else {
                alert(result.error);
            }
        });
    });
</script>



</body>
</html>