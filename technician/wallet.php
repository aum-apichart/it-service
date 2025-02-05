<?php
session_start();
require_once '../db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['tech_id'])) {
    header("Location: dashboard.php");
    exit();
}

$tech_id = $_SESSION['tech_id'];

// Get current date for calculations
$today = date('Y-m-d');
$week_start = date('Y-m-d', strtotime('monday this week'));
$month_start = date('Y-m-01');

// Prepare SQL to get daily, weekly, and monthly income for technician
$sql = "SELECT 
    (SELECT COALESCE(SUM(amount), 0) 
     FROM income_history 
     WHERE user_type = 'technician' 
     AND user_identifier = ? 
     AND DATE(income_date) = ?) as today_income,
    
    (SELECT COALESCE(SUM(amount), 0) 
     FROM income_history 
     WHERE user_type = 'technician' 
     AND user_identifier = ? 
     AND DATE(income_date) >= ?) as week_income,
    
    (SELECT COALESCE(SUM(amount), 0) 
     FROM income_history 
     WHERE user_type = 'technician' 
     AND user_identifier = ? 
     AND DATE(income_date) >= ?) as month_income,
    
    (SELECT COALESCE(SUM(amount), 0) 
     FROM income_history 
     WHERE user_type = 'technician' 
     AND user_identifier = ?) as total_income";

try {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "sssssss",
        $tech_id,
        $today,
        $tech_id,
        $week_start,
        $tech_id,
        $month_start,
        $tech_id
    );

    $stmt->execute();
    $result = $stmt->get_result();
    $income_data = $result->fetch_assoc();

    $total_income = $income_data['total_income'] ?? 0;
    $today_income = $income_data['today_income'] ?? 0;
    $week_income = $income_data['week_income'] ?? 0;
    $month_income = $income_data['month_income'] ?? 0;

    $stmt->close();
} catch (Exception $e) {
    // Log error and set default values
    error_log("Error fetching income data: " . $e->getMessage());
    $total_income = 0;
    $today_income = 0;
    $week_income = 0;
    $month_income = 0;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#8257e6">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <title>กระเป๋า</title>
    <style>
        * {
            font-family: 'Kanit', sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
        }

        body {
            margin: 0;
            padding: 0;
            background: #f5f5f5;
            min-height: 100vh;
            line-height: 1.5;
        }

        .container {
            max-width: 100%;
            margin: 0 auto;
            padding: 16px;
        }

        @media (min-width: 768px) {
            .container {
                max-width: 400px;
                padding: 24px;
            }
        }

        .header {
            background: linear-gradient(135deg, #8257e6 0%, #4527a0 100%);
            border-radius: 16px;
            padding: 24px;
            color: white;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .title {
            font-size: 15px;
            margin-bottom: 8px;
            font-weight: 600;
            letter-spacing: 0.3px;
        }

        .balance {
            font-size: 40px;
            font-weight: 700;
            margin-bottom: 24px;
            letter-spacing: -0.5px;
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-top: 20px;
        }

        @media (max-width: 360px) {
            .stats-container {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .title {
                font-size: 18px;
            }

            .balance {
                font-size: 32px;
            }
        }

        .stat-box {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-radius: 12px;
            padding: 13px;
            margin: 1px;
            transition: transform 0.2s ease;
            touch-action: manipulation;
        }

        .stat-box:active {
            transform: scale(0.98);
        }

        .stat-label {
            font-size: 16px;
            margin-bottom: 8px;
            opacity: 0.9;
            font-weight: 500;
        }

        .stat-value {
            color:rgb(0, 255, 8);
            font-size: 20px;
            font-weight: 700;
            text-shadow: none;
        }

        .overview-section {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
           
        }

        .overview-title {
            font-size: 20px;
            margin-bottom: 16px;
            font-weight: 600;
            color: #333;
        }

        .overview-content {
            background: #000;
            border-radius: 12px;
            height: 200px;
            width: 100%;
            overflow: hidden;
        }

        /* Mobile Optimizations */
        @media (max-width: 767px) {
            body {
                overflow-y: scroll;
                -webkit-overflow-scrolling: touch;
            }

            .header {
                position: relative;
                z-index: 1;
            }

            .stat-box {
                user-select: none;
                -webkit-user-select: none;
            }

            .stat-label {
                font-size: 14px;
            }

            .stat-value {
                font-size: 18px;
            }
        }

        .overview-content {
            background: white !important;
            height: auto !important;
            padding: 16px;
            min-height: 300px;
        }

        @media (max-width: 767px) {
            .overview-content {
                min-height: 250px;
            }
        }

        /* Loading State */
        .loading {
            opacity: 0.7;
            pointer-events: none;
        }


        /* iOS Safe Areas */
        @supports (-webkit-overflow-scrolling: touch) {
            body {
                padding-top: env(safe-area-inset-top);
                padding-bottom: env(safe-area-inset-bottom);
            }
        }

        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            height: var(--footer-height, 95px);
            background-color: #ffffff;
            border-top: 2px solid #ddd;
            box-shadow: 0 -1px 5px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-around;
            align-items: center;
            padding: 20px 0;

        }

        :root {
            --footer-height: 95px;
        }

        .footer-item {
            text-align: center;
            font-size: 14px;
            color: #007bff;
        }

        .footer-item img {
            width: 35px;
            height: 35px;
            display: block;
            margin: 0 auto;
        }

        .footer-item span {
            display: block;
            margin-top: 5px;
        }

        .footer-item img[src="icon/start.png"] {
            width: 80px;
            height: 80px;
            margin-top: -47px;
        }

        .footer-item a {
            text-decoration: none;
        }

        .footer-item a:hover {
            text-decoration: none;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <div class="title">รายได้ทั้งหมด</div>
            <div class="balance">฿ <?php echo number_format($total_income, 2); ?></div>
            <div class="stats-container">
                <div class="stat-box">
                    <div class="stat-label">วันนี้</div>
                    <div class="stat-value">+<?php echo number_format($today_income, 2); ?></div>
                </div>
                <div class="stat-box">
                    <div class="stat-label">สัปดาห์นี้</div>
                    <div class="stat-value">+<?php echo number_format($week_income, 2); ?></div>
                </div>
                <div class="stat-box">
                    <div class="stat-label">เดือนนี้</div>
                    <div class="stat-value">+<?php echo number_format($month_income, 2); ?></div>
                </div>
            </div>
        </div>

        <div class="overview-section">
            <div class="overview-title">ภาพรวม</div>
            <div class="overview-content" style="background: white; height: auto; padding: 16px;">
                <canvas id="incomeChart"></canvas>
            </div>
        </div>
    </div>

    <footer class="footer">
            <div class="footer-item">
                <a href="dashboard.php">
                    <img src="icon/home.png" alt="Home">
                    <span>หน้าหลัก</span>
                </a>
            </div>
            <div class="footer-item">
                <a href="wallet.php">
                    <img src="icon/wallet2.png" alt="Wallet">
                    <span>กระเป๋า</span>
                </a>
            </div>
            <div class="footer-item">
                <a href="work.php">
                    <img src="icon/start.png" alt="Start">
                    <span>เริ่มงาน</span>
                </a>
            </div>
            <div class="footer-item">
                <a href="under.php">
                    <img src="icon/history.png" alt="History">
                    <span>ประวัติ</span>
                </a>
            </div>
            <div class="footer-item">
                <a href="under.php">
                    <img src="icon/help.png" alt="Help">
                    <span>ช่วยเหลือ</span>
                </a>
            </div>
        </footer>
</body>

</html>
<script>
    const ctx = document.getElementById('incomeChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['รายวัน', 'รายสัปดาห์', 'รายเดือน'],
            datasets: [{
                label: 'รายได้ (บาท)',
                data: [
                    <?php echo $today_income; ?>,
                    <?php echo $week_income; ?>,
                    <?php echo $month_income; ?>
                ],
                backgroundColor: [
                    'rgba(130, 87, 230, 0.8)',
                    'rgba(102, 67, 198, 0.8)',
                    'rgba(69, 39, 160, 0.8)'
                ],
                borderColor: [
                    'rgba(130, 87, 230, 1)',
                    'rgba(102, 67, 198, 1)',
                    'rgba(69, 39, 160, 1)'
                ],
                borderWidth: 1,
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '฿' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });
</script>