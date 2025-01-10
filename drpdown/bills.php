<?php
// Start the session
session_start();

// Include config file
require_once '../config.php';

// Check if the user is logged in, if not redirect to login page
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php");
    exit;
}

// Get the logged-in user's ID
$user_id = $_SESSION['id']; // Use session user ID

include '../navbar.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Bills</title>
    <link rel="stylesheet" href="../styles.css">
</head>
<body>
    <div class="container">
        <h1>My Bills</h1>
        <button id="add-bill-btn">+ Add Bill</button>
        <div id="bills-list"></div>
    </div>

    <div id="bill-modal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <form id="bill-form">
                <input type="hidden" name="action" value="create">
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
            let bills = [];

            // Open Add Bill Modal
            addBillBtn.onclick = () => {
                billForm.reset();
                document.getElementById('reminder_id').value = '';
                billForm.querySelector('input[name="action"]').value = 'create';
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
                fetchBills();
                billModal.style.display = 'none';
            };

            // Fetch Bills from the Server
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
                    const isOverdue = billDueDate < today;
                    const billItem = document.createElement('div');
                    billItem.className = 'bill-item';
                    billItem.innerHTML = `
                        <h4>${bill.BillTitle}</h4>
                        <p>Amount: RM${bill.BillAmt}</p>
                        <p>Due: ${bill.BillDue} ${isOverdue ? '<span style="color: red;">(Overdue)</span>' : ''}</p>
                        <p>Frequency: ${bill.BillFrequency}</p>
                        <input type="checkbox" ${bill.Paid ? 'checked' : ''} onclick="togglePaid(${bill.ReminderID})"> Paid
                        <button onclick="editBill(${bill.ReminderID})">Edit</button>
                        <button onclick="deleteBill(${bill.ReminderID})">Delete</button>
                    `;
                    billsList.appendChild(billItem);
                });
            }

            // Edit Bill
            window.editBill = (id) => {
                console.log('Editing Bill ID:', id);
                const bill = bills.find(b => b.ReminderID == id); // Loose equality to handle string ID
                if (bill) {
                    console.log('Bill found:', bill);
                    document.getElementById('reminder_id').value = bill.ReminderID;
                    document.getElementById('bill-title').value = bill.BillTitle;
                    document.getElementById('bill-due').value = bill.BillDue;
                    document.getElementById('bill-frequency').value = bill.BillFrequency;
                    document.getElementById('bill-amount').value = bill.BillAmt;
                    billForm.querySelector('input[name="action"]').value = 'update';
                    billModal.style.display = 'block';
                } else {
                    console.error('Bill not found for ID:', id);
                    alert('Unable to find the bill. Please refresh the page.');
                }
            };

            // Delete Bill
            window.deleteBill = async (id) => {
                if (confirm('Are you sure you want to delete this bill reminder?')) {
                    const response = await fetch('bills_process.php', {
                        method: 'POST',
                        body: new URLSearchParams({ action: 'delete', reminder_id: id })
                    });
                    const data = await response.json();
                    alert(data.success || data.error);
                    fetchBills();
                }
            };

            // Toggle Paid Status
            window.togglePaid = async (id) => {
                const bill = bills.find(b => b.ReminderID == id);
                if (bill) {
                    const response = await fetch('bills_process.php', {
                        method: 'POST',
                        body: new URLSearchParams({ action: 'togglePaid', reminder_id: id, paid: !bill.Paid })
                    });
                    const data = await response.json();
                    alert(data.success || data.error);
                    fetchBills();
                }
            };

            // Initial Fetch
            fetchBills();
        });
    </script>
</body>
</html>
