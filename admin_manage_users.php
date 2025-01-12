<?php
// Start the session
session_start();

// Include config file
require_once 'config.php';

// Check if admin is logged in
// Add your admin authentication logic here

// Fetch user data from the database
$sql = "SELECT UserID, Username, UserEmail, createdAt, status FROM Users";
$result = $conn->query($sql);

$users = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
} else {
    echo "Error fetching users: " . $conn->error;
}

// Fetch the count of active users
$active_users_sql = "SELECT COUNT(*) as active_users FROM Users WHERE status = 'active'";
$active_users_result = $conn->query($active_users_sql);
if ($active_users_result) {
    $active_users_count = $active_users_result->fetch_assoc()['active_users'];
} else {
    echo "Error fetching active users: " . $conn->error;
    $active_users_count = 0; // Default value in case of error
}


// Fetch user activity data
$activity_data_sql = "SELECT MONTH(Timestamp) as month, COUNT(*) as activity_count 
                      FROM UserActivities 
                      GROUP BY MONTH(Timestamp)";
$activity_data_result = $conn->query($activity_data_sql);

$activity_data = [];
if ($activity_data_result) {
    while ($row = $activity_data_result->fetch_assoc()) {
        $activity_data[(int)$row['month']] = $row['activity_count'];
    }
} else {
    echo "Error fetching activity data: " . $conn->error;
}

// Prepare data for the chart
$months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
$activity_counts = [];
foreach ($months as $index => $month) {
    $activity_counts[] = $activity_data[$index + 1] ?? 0; // Default to 0 if no data
}

// Fetch recent activity logs from the database
$activity_sql = "SELECT Username, Action, Timestamp, Type FROM UserActivities ORDER BY Timestamp DESC LIMIT 10";
$activity_result = $conn->query($activity_sql);

$activity_logs = [];
if ($activity_result) {
    while ($row = $activity_result->fetch_assoc()) {
        $activity_logs[] = $row;
    }
} else {
    echo "Error fetching activity logs: " . $conn->error;
}

// Fetch total transactions for each user
$transactions_sql = "SELECT UserID, COUNT(*) as total_transactions FROM Transactions GROUP BY UserID";
$transactions_result = $conn->query($transactions_sql);

$transactions = [];
$total_transactions_all_users = 0;
if ($transactions_result) {
    while ($row = $transactions_result->fetch_assoc()) {
        $transactions[$row['UserID']] = $row['total_transactions'];
        $total_transactions_all_users += $row['total_transactions'];
    }
} else {
    echo "Error fetching transactions: " . $conn->error;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users</title>
    <link rel="stylesheet" href="css/navbar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.1/chart.min.css">
   <style>
     /*admin_manage_users.php styles*/


     body {
            font-family: 'Arial', sans-serif;
            background-color: #f9f9f9;
            padding: 20px;
        }

        .admin-container {
            max-width: 1200px;
            margin: auto;
            padding: 20px;
        }

        .admin-dashboard-header {
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .admin-dashboard-header h2 {
            color: #4a148c;
            font-size: 2em;
        }

        .admin-dark-mode-toggle {
            cursor: pointer;
            font-size: 1.5em;
        }

        .admin-stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .admin-stat-card {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            position: relative;
            transition: transform 0.3s ease;
        }

        .admin-stat-card:hover {
            transform: translateY(-5px);
        }

        .admin-stat-card h3 {
            color: #4a148c;
            margin-bottom: 10px;
        }

        .admin-stat-card i {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 1.5em;
            color: #4a148c;
        }

        .admin-users-table {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin-bottom: 30px;
        }

        .admin-table {
            width: 100%;
            border-collapse: collapse;
        }

        .admin-th, .admin-td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .admin-th {
            background-color: #4a148c;
            color: white;
        }

        .admin-tr:hover {
            background-color: #f5f5f5;
        }

        .admin-status {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.9em;
        }

        .admin-status-active {
            background-color: #e8f5e9;
            color: #2e7d32;
        }

        .admin-status-inactive {
            background-color: #ffebee;
            color: #c62828;
        }

        .admin-activity-logs {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .admin-activity-item {
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }

        .admin-activity-item:last-child {
            border-bottom: none;
        }

        .admin-activity-time {
            color: #666;
            font-size: 0.9em;
        }

        .admin-action-buttons {
            display: flex;
            gap: 10px;
        }

        .admin-btn {
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9em;
        }

        .admin-btn-view {
            background-color: #4a148c;
            color: white;
        }

        .admin-btn-suspend {
            background-color: #f44336;
            color: white;
        }

        .admin-search-bar {
            margin-bottom: 20px;
        }

        .admin-search-bar input {
            padding: 10px;
            width: 300px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .admin-chart-container {
            margin-bottom: 30px;
        }
    </style>
   
</head>
<body>
    <?php include 'admin_navbar.php'; ?>
    
    <div class="admin-container">
        <div class="admin-dashboard-header">
            <h2>User Management Dashboard</h2>
            <i class="fas fa-moon admin-dark-mode-toggle" onclick="toggleDarkMode()"></i>
        </div>

        <div class="admin-stats-container">
            <div class="admin-stat-card">
                <h3>Total Users</h3>
                <i class="fas fa-users"></i>
                <p><?php echo count($users); ?></p>
            </div>
            <div class="admin-stat-card">
                <h3>Active Users</h3>
                <i class="fas fa-user-check"></i>
                <p><?php echo $active_users_count; ?></p>
            </div>
            <div class="admin-stat-card">
                <h3>Total Transactions</h3>
                <i class="fas fa-exchange-alt"></i>
                <p><?php echo $total_transactions_all_users; ?></p>
            </div>
        </div>

        <div class="admin-chart-container">
            <canvas id="userActivityChart"></canvas>
        </div>

        <div class="admin-search-bar">
            <input type="text" id="userSearch" placeholder="Search users..." onkeyup="searchUsers()">
        </div>

        <div class="admin-users-table">
            <table class="admin-table">
                <thead>
                    <tr class="admin-tr">
                        <th class="admin-th">Username</th>
                        <th class="admin-th">Email</th>
                        <th class="admin-th">Join Date</th>
                        <th class="admin-th">Status</th>
                        <th class="admin-th">Total Transactions</th>
                        <th class="admin-th">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr class="admin-tr">
                        <td class="admin-td"><?php echo htmlspecialchars($user['Username']); ?></td>
                        <td class="admin-td"><?php echo htmlspecialchars($user['UserEmail']); ?></td>
                        <td class="admin-td"><?php echo date('j M Y', strtotime($user['createdAt'])); ?></td>
                        <td class="admin-td"><?php echo htmlspecialchars($user['status']); ?></td>
                        <td class="admin-td"><?php echo $transactions[$user['UserID']] ?? 0; ?></td>
                        <td class="admin-td admin-action-buttons">
                            <button class="admin-btn admin-btn-view" onclick="viewUser(<?php echo $user['UserID']; ?>)">
                                <i class="fas fa-eye"></i> View
                            </button>
                            <?php if ($user['status'] === 'active'): ?>
                                <button class="admin-btn admin-btn-suspend" onclick="toggleUserStatus(<?php echo $user['UserID']; ?>, 'suspend')">
                                    <i class="fas fa-ban"></i> Suspend
                                </button>
                            <?php else: ?>
                                <button class="admin-btn admin-btn-activate" onclick="toggleUserStatus(<?php echo $user['UserID']; ?>, 'activate')">
                                    <i class="fas fa-check"></i> Activate
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="admin-activity-logs">
            <h3>Recent Activity Logs</h3>
            <div id="activity-log-container">
                <?php foreach ($activity_logs as $log): ?>
                <div class="admin-activity-item">
                    <p>
                        <strong><?php echo htmlspecialchars($log['Username']); ?></strong>
                        <?php echo htmlspecialchars($log['Action']); ?>
                    </p>
                    <span class="admin-activity-time">
                        <?php echo date('j M Y H:i', strtotime($log['Timestamp'])); ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.1/chart.min.js"></script>
    <script>
        function viewUser(userId) {
            // Fetch and display user details in a modal or separate page
            alert('Viewing user details for ID: ' + userId);
            // Implement AJAX call to fetch user details if needed
        }

        function toggleUserStatus(userId, action) {
            if (confirm(`Are you sure you want to ${action} this user?`)) {
                fetch('update_user_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `user_id=${userId}&action=${action}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(`User has been ${action}ed successfully.`);
                        location.reload();
                    } else {
                        alert(`Error: ${data.error}`);
                    }
                });
            }
        }

        function searchUsers() {
            const input = document.getElementById('userSearch');
            const filter = input.value.toLowerCase();
            const table = document.querySelector('.admin-users-table .admin-table');
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

        function toggleDarkMode() {
            document.body.classList.toggle('dark-mode');
        }

        const ctx = document.getElementById('userActivityChart').getContext('2d');
        const userActivityChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($months); ?>,
                datasets: [{
                    label: 'User Activity',
                    data: <?php echo json_encode($activity_counts); ?>,
                    borderColor: '#4a148c',
                    fill: false
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: true
                    }
                }
            }
        });

        // Real-time update for activity logs
        setInterval(async () => {
            const response = await fetch('fetch_activity_logs.php');
            const newLogs = await response.json();
            const activityLogContainer = document.getElementById('activity-log-container');
            activityLogContainer.innerHTML = '';
            newLogs.forEach(log => {
                const logItem = document.createElement('div');
                logItem.className = 'admin-activity-item';
                logItem.innerHTML = `
                    <p><strong>${log.Username}</strong> ${log.Action}</p>
                    <span class="admin-activity-time">${new Date(log.Timestamp).toLocaleString()}</span>
                `;
                activityLogContainer.appendChild(logItem);
            });
        }, 5000); // Update every 5 seconds
    </script>
</body>
</html> 