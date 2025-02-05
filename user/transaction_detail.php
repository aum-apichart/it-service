<?php
session_start();
require_once '../db_connect.php';

// ตรวจสอบ request_id จาก URL หรือ session
if (isset($_GET['id'])) {
    $request_id = $_GET['id'];
    $_SESSION['request_id'] = $request_id;
} elseif (isset($_SESSION['request_id'])) {
    $request_id = $_SESSION['request_id'];
} else {
    die('No service request specified.');
}

// ดึงข้อมูลจากฐานข้อมูล
$query = "
    SELECT sr.request_id, sr.description, sr.created_at, sr.status, sr.latitude, sr.longitude, 
           st.service_name, 
           u.first_name, u.last_name, u.phone, 
           sr.images 
    FROM service_requests sr
    JOIN service_types st ON sr.service_type_id = st.service_type_id
    JOIN users u ON sr.user_id = u.user_id
    WHERE sr.request_id = ?
";

$stmt = $conn->prepare($query);
$stmt->bind_param('i', $request_id);
$stmt->execute();
$result = $stmt->get_result();
$request = $result->fetch_assoc();

if (!$request) {
    die('Invalid service request.');
}

// ดึงข้อมูลการทำงานจากตาราง working
$queryWorking = "
    SELECT w.note, w.created_at, t.first_name, t.last_name 
    FROM working w
    JOIN service_requests sr ON w.request_id = sr.request_id
    JOIN technicians t ON sr.tech_id = t.tech_id
    WHERE w.request_id = ?
";

$stmtWorking = $conn->prepare($queryWorking);
$stmtWorking->bind_param('i', $request_id);
$stmtWorking->execute();
$resultWorking = $stmtWorking->get_result();
$workingData = $resultWorking->fetch_all(MYSQLI_ASSOC);

// ดึงข้อมูลรีวิวจากตาราง feedback
$queryFeedback = "
    SELECT rating, feedback_text, created_at 
    FROM feedback 
    WHERE request_id = ?
";
$stmtFeedback = $conn->prepare($queryFeedback);
$stmtFeedback->bind_param('i', $request_id);
$stmtFeedback->execute();
$resultFeedback = $stmtFeedback->get_result();
$feedbackData = $resultFeedback->fetch_all(MYSQLI_ASSOC);

// เก็บข้อมูลลง session หากจำเป็น
$_SESSION['request_data'] = $request;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Details</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            background-color: rgb(243, 244, 252);
            font-family: 'Kanit', sans-serif;
            padding-bottom: 60px;
        }

        .map-container {
            height: 300px;
            width: 100%;
            border-radius: 12px;
            overflow: hidden;
        }

        footer {
            background-color: #f8f9fa;
            text-align: center;
            padding: 10px 0;
            position: fixed;
            bottom: 0;
            width: 100vw;
            box-shadow: 0 -1px 5px rgba(0, 0, 0, 0.1);
        }

        .footer-icon {
            width: 38px;
            height: 38px;
            object-fit: cover;
        }
    </style>
    <script src="https://api.longdo.com/map/?key=8809a47dd3532ff420480af45e5a3e6f"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        let map;

        function initMap() {
            map = new longdo.Map({
                placeholder: document.getElementById('map')
            });
            if (<?= $request['latitude'] ?> && <?= $request['longitude'] ?>) {
                rerverseGeocoding(<?= $request['latitude'] ?>, <?= $request['longitude'] ?>);
            } else {
                $('#location-details').text('Invalid coordinates');
            }
        }

        function rerverseGeocoding(lat, lon) {
            $.ajax({
                url: "https://api.longdo.com/map/services/address",
                dataType: "json",
                type: "GET",
                data: {
                    key: "8809a47dd3532ff420480af45e5a3e6f",
                    lon: lon,
                    lat: lat
                },
                success: function(results) {
                    if (results) {
                        $('#location-details').text(results.road || results.district || results.province || 'Unknown');
                    } else {
                        $('#location-details').text('Unknown');
                    }
                    map.location({
                        lat: lat,
                        lon: lon
                    });
                    map.Overlays.add(new longdo.Marker({
                        lat: lat,
                        lon: lon
                    }));
                },
                error: function(response) {
                    console.error('Error fetching location details:', response);
                    $('#location-details').text('Error fetching location');
                }
            });
        }
    </script>
</head>

<body onload="initMap();">
    <nav class="navbar navbar-light bg-white shadow-sm">
        <div class="container-fluid">
            <a href="transaction.php" class="back-button">
                <i class="fas fa-chevron-left"></i>
            </a>
            <span class="navbar-brand mx-auto fw-bold">รายละเอียด</span>
        </div>
    </nav>
    <div class="container mt-4">
        <!-- รายละเอียดคำสั่ง -->
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center text-black">
                <span>รายละเอียดคำสั่ง</span>
                <span style="font-size: 0.9em; color: #555; display: inline-block; margin-left: 10px;">
                    <?php
                    $createdAt = strtotime($request['created_at']);
                    $monthThai = [
                        'มกราคม',
                        'กุมภาพันธ์',
                        'มีนาคม',
                        'เมษายน',
                        'พฤษภาคม',
                        'มิถุนายน',
                        'กรกฎาคม',
                        'สิงหาคม',
                        'กันยายน',
                        'ตุลาคม',
                        'พฤศจิกายน',
                        'ธันวาคม'
                    ];

                    echo date('j', $createdAt) . ' ' . $monthThai[date('n', $createdAt) - 1] . ' ' . date('y, H:i', $createdAt);
                    ?>
                </span>

            </div>
            <div class="card-body px-3">
                <p>
                    <strong>
                        <img src="icon/description2.png" alt="Service Icon" style="width: 20px; height: 20px; margin-right: 5px;">
                    </strong>
                    <?= $request['service_name'] ?>
                </p>
                <p><strong></strong> <?= $request['description'] ?></p>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header text-black">
                ปลายทาง
            </div>
            <div class="card-body px-3">
                <p>
                    <img src="icon/profile.png" alt="profile" style="width: 16px; height: 16px; margin-right: 5px;">
                    <strong></strong> <?= $request['first_name'] ?>
                    - <strong></strong> <?= $request['phone'] ?>
                </p>
                <p>
                    <img src="icon/location2.png" alt="Location Icon" style="width: 16px; height: 16px; margin-right: 5px;">
                    <strong></strong> <span id="location-details">Loading...</span>
                </p>
                <div id="map" class="map-container"></div>
            </div>
        </div>



        <!-- รายละเอียดการซ่อม -->
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center text-black">
                <span>รายละเอียดการซ่อม</span>
                <span style="font-size: 0.9em; color: #555; display: inline-block; margin-left: 10px;">
                    <?php
                    $createdAt = strtotime($request['created_at']);
                    $monthThai = [
                        'มกราคม',
                        'กุมภาพันธ์',
                        'มีนาคม',
                        'เมษายน',
                        'พฤษภาคม',
                        'มิถุนายน',
                        'กรกฎาคม',
                        'สิงหาคม',
                        'กันยายน',
                        'ตุลาคม',
                        'พฤศจิกายน',
                        'ธันวาคม'
                    ];

                    echo date('j', $createdAt) . ' ' . $monthThai[date('n', $createdAt) - 1] . ' ' . date('y, H:i', $createdAt);
                    ?>
                </span>

            </div>
            <div class="card-body px-3">
                <?php foreach ($workingData as $work): ?>
                    <p><strong>ช่าง:</strong> <?= $work['first_name'] . ' ' . $work['last_name'] ?></p>
                    <p><strong>หมายเหตุ:</strong> <?= $work['note'] ?></p>
                    <hr>
                <?php endforeach; ?>
            </div>
        </div>


        <!-- รีวิว -->
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center text-black">
                <span>รีวิว</span>
                <span style="font-size: 0.9em; color: #555; display: inline-block; margin-left: 10px;">
                    <?php
                    $createdAt = strtotime($request['created_at']);
                    $monthThai = [
                        'มกราคม',
                        'กุมภาพันธ์',
                        'มีนาคม',
                        'เมษายน',
                        'พฤษภาคม',
                        'มิถุนายน',
                        'กรกฎาคม',
                        'สิงหาคม',
                        'กันยายน',
                        'ตุลาคม',
                        'พฤศจิกายน',
                        'ธันวาคม'
                    ];

                    echo date('j', $createdAt) . ' ' . $monthThai[date('n', $createdAt) - 1] . ' ' . date('y, H:i', $createdAt);
                    ?>
                </span>

            </div>
            <div class="card-body px-3">
                <?php foreach ($feedbackData as $feedback): ?>
                    <p><strong>คะแนน:</strong> <?= $feedback['rating'] ?> / 5</p>
                    <p><strong>ความคิดเห็น:</strong> <?= $feedback['feedback_text'] ?></p>
                    <hr>
                <?php endforeach; ?>
            </div>
        </div>

    </div>
    <footer>
        <div class="d-flex justify-content-around">
            <a href="a_home.php">
                <img src="icon/home.png" alt="Home" class="footer-icon">
            </a>
            <a href="shopping_cart.php">
                <img src="icon/card.png" alt="Cart" class="footer-icon">
            </a>
            <a href="transaction.php">
                <img src="icon/transaction.png" alt="Order History" class="footer-icon">
            </a>
            <a href="contact_us.php">
                <img src="icon/more.png" alt="Contact" class="footer-icon">
            </a>
        </div>
    </footer>
</body>

</html>