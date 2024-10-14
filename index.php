<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MoneyCraft Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #F9F9F5;
        }
        header {
            background-color: #F4E05E;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        header nav a {
            margin: 0 20px;
            text-decoration: none;
            color: #333;
            font-weight: bold;
        }
        header img {
            height: 50px;
        }
        .dashboard {
            display: flex;
            justify-content: space-between;
            padding: 20px;
        }
        .left-section {
            width: 65%;
        }
        .right-section {
            width: 30%;
            text-align: center;
        }
        .transaction-history, .overview, .savings-goals {
            background-color: #FFFBEA;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 20px;
        }
        .transaction-history h3, .overview h3 {
            color: #AA8959;
        }
        .transaction-item {
            display: flex;
            justify-content: space-between;
            background-color: #F8E1A3;
            padding: 10px;
            border-radius: 10px;
            margin-bottom: 10px;
        }
        .video-section img {
            border-radius: 15px;
        }
        .btn {
            padding: 10px;
            background-color: #FFD869;
            border: none;
            border-radius: 10px;
            cursor: pointer;
        }
        .circle {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 150px;
            height: 150px;
            border-radius: 50%;
            border: 10px solid #FFD869;
            font-size: 24px;
            margin: 0 auto;
        }
    </style>
</head>
<body>

<header>
    <div>
        <img src="moneycraft-logo.png" alt="MoneyCraft Logo">
    </div>
    <nav>
        <a href="#">Home</a>
        <a href="#">Financial Activity</a>
        <a href="#">Learning Center</a>
        <a href="#">Account</a>
    </nav>
</header>

<section class="dashboard">
    <div class="left-section">
        <!-- Overview Section -->
        <div class="overview">
            <div class="circle">
                RM500
                <br>
                <small>Savings on Goals</small>
            </div>
            <div class="summary">
                <p>Bills due 1st June (Rent): RM150</p>
                <p>Food last week: -RM100</p>
            </div>
        </div>

        <!-- Transaction History Section -->
        <div class="transaction-history">
            <h3>Transaction History (April)</h3>
            <div class="transaction-item">
                <div>Salary</div>
                <div>RM1500</div>
            </div>
            <div class="transaction-item">
                <div>Groceries</div>
                <div>-RM174</div>
            </div>
            <div class="transaction-item">
                <div>Rent</div>
                <div>-RM150</div>
            </div>
        </div>
    </div>

    <div class="right-section">
        <!-- Video Section -->
        <div class="video-section">
            <img src="financial-literacy-video-thumbnail.png" alt="Financial Literacy Video" width="100%">
            <p><small>Don't forget to watch the latest financial literacy video!</small></p>
        </div>
    </div>
</section>

</body>
</html>
