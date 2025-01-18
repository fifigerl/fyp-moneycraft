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

// Check if the user is logged in, if not redirect to login page
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php");
    exit;
}

// Get the logged-in user's ID and username
$user_id = $_SESSION['id'];
$username = $_SESSION['username'];

// Log the action of viewing the bills page
logUserActivity($conn, $user_id, $username, "Viewed Bills Page", "View");

include '../navbar.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bills</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #F8F9FA;
            font-family: 'Inter', sans-serif;
        }

        .bill-container {
            max-width: 900px;
            margin: 20px auto;
            background-color: #FFFFFF;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        h1 {
            font-size: 36px;
            color: rgb(0, 35, 72);
            font-weight: 900;
            margin-bottom: 20px;
        }

        #add-bill-btn {
            background-color: #FFD000;
            color: #161925;
            font-weight: bold;
            border: none;
            border-radius: 10px;
            padding: 10px 20px;
            cursor: pointer;
            margin-bottom: 20px;
        }

        #add-bill-btn:hover {
            background-color: #FDF09D;
        }

        .bill-item {
            background-color: #FFF;
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .bill-details h4 {
            font-weight: bold;
            color: rgb(0, 35, 72);
        }

        .bill-details p {
            margin: 0;
            font-size: 14px;
            color: #6c757d;
        }

        .bill-actions button {
            background-color: #FFD000;
            color: #161925;
            font-weight: bold;
            border: none;
            border-radius: 5px;
            padding: 5px 10px;
            cursor: pointer;
            margin-right: 5px;
        }

        .bill-actions button:hover {
            background-color: #FDF09D;
        }

        .bill-actions .delete-button {
            background-color: #FF4D4D;
            color: #FFF;
        }

        .bill-actions .delete-button:hover {
            background-color: #FF0000;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1050;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background-color: #FFF;
            margin: 10% auto;
            padding: 20px;
            border-radius: 10px;
            width: 400px;
            position: relative;
        }

        .close {
            position: absolute;
            top: 10px;
            right: 10px;
            color: #000;
            font-size: 24px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: red;
        }

        .modal-content input,
        .modal-content select,
        .modal-content button {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 5px;
            border: 1px solid #ddd;
        }

        .modal-content button {
            background-color: #FFD000;
            color: #161925;
            font-weight: bold;
            border: none;
        }

        .modal-content button:hover {
            background-color: #FDF09D;
        }
    </style>
</head>
<body>
<div class="bill-container">
        <h1>My Bills</h1>
        <button id="add-bill-btn">+ Add Bill</button>
        <div id="bills-list"></div>
    </div>

    <div id="bill-modal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <form id="bill-form">
                <input type="hidden" name="action" value="create" id="form-action">
                <input type="hidden" name="reminder_id" id="reminder_id">
                <input type="text" name="title" id="bill-title" placeholder="Bill Title" required>
                <input type="date" name="due" id="bill-due" required>
                <select name="frequency" id="bill-frequency" required>
                    <option value="Monthly">Monthly</option>
                    <option value="Quarterly">Quarterly</option>
                    <option value="Yearly">Yearly</option>
                </select>
                <input type="number" name="amount" id="bill-amount" placeholder="Amount" required>
                <button type="submit">Save</button>
            </form>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const billsList = document.getElementById('bills-list');
        const billModal = document.getElementById('bill-modal');
        const closeModal = document.querySelector('.close');
        const addBillBtn = document.getElementById('add-bill-btn');
        const billForm = document.getElementById('bill-form');
        const formAction = document.getElementById('form-action');
        const reminderIdInput = document.getElementById('reminder_id');
        let bills = [];

        // Open Add Bill Modal
        addBillBtn.onclick = () => {
            billForm.reset();
            formAction.value = 'create'; // Set action to create for new bills
            reminderIdInput.value = ''; // Clear reminder ID
            billModal.style.display = 'block';
        };

        // Close Modal
        closeModal.onclick = () => {
            billModal.style.display = 'none';
        };

        // Handle Form Submission
        billForm.onsubmit = async (e) => {
            e.preventDefault();
            const formData = new FormData(billForm);
            formData.append('user_id', <?php echo $user_id; ?>);

            const response = await fetch('bills_process.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            alert(data.success || data.error);
            if (data.success) {
                billModal.style.display = 'none';
                fetchBills();
            }
        };

        async function fetchBills() {
            const response = await fetch('bills_process.php', {
                method: 'POST',
                body: new URLSearchParams({ action: 'read', user_id: <?php echo $user_id; ?> })
            });
            bills = await response.json();
            renderBills(bills);
        }

        // Render Bills List
        function renderBills(bills) {
            billsList.innerHTML = '';
            const today = new Date();

            bills.forEach(bill => {
                const billDueDate = new Date(bill.BillDue);
                const timeDiff = billDueDate - today; // Time difference in milliseconds
                const daysLeft = Math.ceil(timeDiff / (1000 * 60 * 60 * 24)); // Convert to days
                const isOverdue = timeDiff < 0;

                const billItem = document.createElement('div');
                billItem.className = 'bill-item';
                billItem.innerHTML = `
                    <div class="bill-details">
                        <h4>${bill.BillTitle}</h4>
                        <p>Amount: RM${bill.BillAmt}</p>
                        <p>Due: ${bill.BillDue}</p>
                        <p>Frequency: ${bill.BillFrequency}</p>
                        <p>
                            <span style="color: ${isOverdue ? 'red' : 'green'}; font-weight: bold;">
                                ${isOverdue ? `Overdue by ${Math.abs(daysLeft)} day(s)` : `${daysLeft} day(s) left`}
                            </span>
                        </p>
                    </div>
                    <div class="bill-actions">
                        <button onclick="editBill(${bill.ReminderID})">Edit</button>
                        <button class="delete-button" onclick="deleteBill(${bill.ReminderID})">Delete</button>
                    </div>
                `;
                billsList.appendChild(billItem);
            });
        }

        // Edit Bill
        window.editBill = (id) => {
            const bill = bills.find(b => b.ReminderID == id);
            if (bill) {
                reminderIdInput.value = bill.ReminderID;
                document.getElementById('bill-title').value = bill.BillTitle;
                document.getElementById('bill-due').value = bill.BillDue;
                document.getElementById('bill-frequency').value = bill.BillFrequency;
                document.getElementById('bill-amount').value = bill.BillAmt;
                formAction.value = 'update'; // Set action to update for editing
                billModal.style.display = 'block';
            }
        };

        // Delete Bill
        window.deleteBill = async (id) => {
            if (confirm('Are you sure you want to delete this bill?')) {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('reminder_id', id);

                const response = await fetch('bills_process.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                alert(data.success || data.error);
                if (data.success) {
                    fetchBills();
                }
            }
        };

        fetchBills();
    });
</script>

</body>
</html>