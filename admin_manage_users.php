<?php
// Start the session
session_start();

// Include config file
require_once 'config.php';

// Check if admin is logged in
// Add your admin authentication logic here

// Fetch user data from the database
$sql = "SELECT UserID, Username, UserEmail, createdAt FROM Users";
$result = $conn->query($sql);

$users = [];
if ($result) {
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
    }
} else {
    echo "Error: " . $conn->error;
}

// Fake recent activity logs for demonstration
$activity_logs = [
    [
        'Username' => 'john_doe',
        'Action' => 'Created new budget: Monthly Expenses',
        'Timestamp' => '2024-03-15 15:45:00',
        'Type' => 'Budget'
    ],
    [
        'Username' => 'sarah_smith',
        'Action' => 'Added transaction: Groceries -$50',
        'Timestamp' => '2024-03-14 10:20:00',
        'Type' => 'Transaction'
    ],
    [
        'Username' => 'john_doe',
        'Action' => 'Updated profile information',
        'Timestamp' => '2024-03-14 09:30:00',
        'Type' => 'Profile'
    ],
    [
        'Username' => 'mike_wilson',
        'Action' => 'Viewed Financial Literacy 101 video',
        'Timestamp' => '2024-03-13 17:00:00',
        'Type' => 'Content'
    ]
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users</title>
    <link rel="stylesheet" href="css/navbar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f9f9f9;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: auto;
            padding: 20px;
        }

        .dashboard-header {
            margin-bottom: 30px;
        }

        .dashboard-header h2 {
            color: #4a148c;
            font-size: 2em;
            margin-bottom: 10px;
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .stat-card h3 {
            color: #4a148c;
            margin-bottom: 10px;
        }

        .users-table {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin-bottom: 30px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #4a148c;
            color: white;
        }

        tr:hover {
            background-color: #f5f5f5;
        }

        .status {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.9em;
        }

        .status-active {
            background-color: #e8f5e9;
            color: #2e7d32;
        }

        .status-inactive {
            background-color: #ffebee;
            color: #c62828;
        }

        .activity-logs {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .activity-item {
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-time {
            color: #666;
            font-size: 0.9em;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9em;
        }

        .btn-view {
            background-color: #4a148c;
            color: white;
        }

        .btn-suspend {
            background-color: #f44336;
            color: white;
        }

        .search-bar {
            margin-bottom: 20px;
        }

        .search-bar input {
            padding: 10px;
            width: 300px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <?php include 'admin_navbar.php'; ?>
    
    <div class="container">
        <div class="dashboard-header">
            <h2>User Management Dashboard</h2>
        </div>

        <div class="stats-container">
            <div class="stat-card">
                <h3>Total Users</h3>
                <p><?php echo count($users); ?></p>
            </div>
            <div class="stat-card">
                <h3>Active Users</h3>
               
            </div>
            <div class="stat-card">
                <h3>Total Transactions</h3>
                <p><?php echo array_sum(array_column($users, 'TotalTransactions')); ?></p>
            </div>
        </div>

        <div class="search-bar">
            <input type="text" id="userSearch" placeholder="Search users..." onkeyup="searchUsers()">
        </div>

        <div class="users-table">
            <table>
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Join Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['Username']); ?></td>
                        <td><?php echo htmlspecialchars($user['UserEmail']); ?></td>
                        <td><?php echo date('j M Y', strtotime($user['createdAt'])); ?></td>
                        <td class="action-buttons">
                            <button class="btn btn-view" onclick="viewUser(<?php echo $user['UserID']; ?>)">
                                <i class="fas fa-eye"></i> View
                            </button>
                            <button class="btn btn-suspend" onclick="toggleUserStatus(<?php echo $user['UserID']; ?>)">
                                <i class="fas fa-ban"></i> Suspend
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="activity-logs">
            <h3>Recent Activity Logs</h3>
            <?php foreach ($activity_logs as $log): ?>
            <div class="activity-item">
                <p>
                    <strong><?php echo htmlspecialchars($log['Username']); ?></strong>
                    <?php echo htmlspecialchars($log['Action']); ?>
                </p>
                <span class="activity-time">
                    <?php echo date('j M Y H:i', strtotime($log['Timestamp'])); ?>
                </span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
        function viewUser(userId) {
            // Add functionality to view user details
            alert('Viewing user details for ID: ' + userId);
        }

        function toggleUserStatus(userId) {
            // Add functionality to toggle user status
            alert('Toggling status for user ID: ' + userId);
        }

        function searchUsers() {
            const input = document.getElementById('userSearch');
            const filter = input.value.toLowerCase();
            const table = document.querySelector('.users-table table');
            const rows = table.getElementsByTagName('tr');

            for (let i = 1; i < rows.length; i++) {
                const username = rows[i].getElementsByTagName('td')[0];
                const email = rows[i].getElementsByTagName('td')[1];
                if (username || email) {
                    const textUsername = username.textContent || username.innerText;
                    const textEmail = email.textContent || email.innerText;
                    if (textUsername.toLowerCase().indexOf(filter) > -1 || 
                        textEmail.toLowerCase().indexOf(filter) > -1) {
                        rows[i].style.display = '';
                    } else {
                        rows[i].style.display = 'none';
                    }
                }
            }
        }
    </script>
</body>
</html> 