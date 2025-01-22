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

// Fetch user activity data grouped by day
$activity_data_sql = "SELECT DATE(Timestamp) AS day, COUNT(*) AS activity_count 
                      FROM UserActivities 
                      GROUP BY day 
                      ORDER BY day ASC";
$activity_data_result = $conn->query($activity_data_sql);

$activity_labels = []; // Dates
$activity_counts = []; // Activity counts

if ($activity_data_result) {
    while ($row = $activity_data_result->fetch_assoc()) {
        $activity_labels[] = $row['day'];
        $activity_counts[] = $row['activity_count'];
    }
} else {
    echo "Error fetching user activity data: " . $conn->error;
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

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.1/chart.min.css">
    <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/admin_styles.css">

</head>


<body>
    <?php include 'admin_navbar.php'; ?>
    <div class="admin-container">
        <h1>User Management Dashboard</h1>

        <!-- Stats Section -->
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

        </div>

        <div class="chart-container">
    <h3 class="chart-title">
        <i class="fas fa-calendar-day" style="margin-right: 10px; color: #FFD000;"></i>
        User Activity by Day
    </h3>
    <div id="userActivityChart" style="height: 350px;"></div>
</div>



        <!-- Users Table -->
        <h2>Manage Users</h2>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Join Date</th>
                    <th>Status</th>
                    <th>Total Transactions</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td><?php echo htmlspecialchars($user['Username']); ?></td>
                    <td><?php echo htmlspecialchars($user['UserEmail']); ?></td>
                    <td><?php echo date('j M Y', strtotime($user['createdAt'])); ?></td>
                    <td><?php echo htmlspecialchars($user['status']); ?></td>
                    <td><?php echo $transactions[$user['UserID']] ?? 0; ?></td>
                    <td>
    <a href="view_user_details.php?user_id=<?php echo $user['UserID']; ?>" class="btn btn-primary">View</a>
    <?php if ($user['status'] === 'active'): ?>
        <button class="btn btn-danger" onclick="toggleUserStatus(<?php echo $user['UserID']; ?>, 'suspend')">Suspend</button>
    <?php else: ?>
        <button class="btn btn-success" onclick="toggleUserStatus(<?php echo $user['UserID']; ?>, 'activate')">Activate</button>
    <?php endif; ?>
</td>

                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Activity Logs -->
        <div class="admin-activity-logs">
            <h4>Recent Activity Logs</h4>
            <?php foreach ($activity_logs as $log): ?>
            <div class="activity-item">
                <p>
                    <strong><?php echo htmlspecialchars($log['Username']); ?></strong> <?php echo htmlspecialchars($log['Action']); ?>
                </p>
                <span><?php echo date('j M Y H:i', strtotime($log['Timestamp'])); ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
    function viewUser(userId) {
    window.location.href = `view_user_details.php?user_id=${userId}`;
}

function toggleUserStatus(userId, action) {
    if (confirm(`Are you sure you want to ${action} this user?`)) {
        fetch('update_user_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `user_id=${userId}&action=${action}`,
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(`User has been ${action === 'activate' ? 'activated' : 'suspended'} successfully.`);
                location.reload();
            } else {
                alert(`Error: ${data.error}`);
            }
        });
    }
}

    </script>
</body>


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
                alert(data.message);
                location.reload();
            } else {
                alert(`Error: ${data.error}`);
            }
        })
        .catch(error => console.error('Error:', error));
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

<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    // User Activity Chart Configuration
    const activityOptions = {
        chart: {
            type: 'line',
            height: 350,
            toolbar: {
                show: false
            }
        },
        series: [{
            name: 'User Activity',
            data: <?php echo json_encode($activity_counts); ?> // Activity counts
        }],
        xaxis: {
            categories: <?php echo json_encode($activity_labels); ?>, // Dates
            title: {
                text: 'Date'
            },
            labels: {
                rotate: -45, // Rotate labels for better visibility
                formatter: function(value) {
                    return new Date(value).toLocaleDateString(); // Format as readable date
                }
            }
        },
        yaxis: {
            title: {
                text: 'Activity Count'
            }
        },
        colors: ['#FFD000'],
        markers: {
            size: 5
        },
        stroke: {
            width: 2,
            curve: 'smooth'
        },
        tooltip: {
            shared: true,
            intersect: false
        },
        grid: {
            borderColor: '#f1f1f1'
        }
    };

    // Render the chart
    const activityChart = new ApexCharts(document.querySelector("#userActivityChart"), activityOptions);
    activityChart.render();
</script>


</body>
</html> 