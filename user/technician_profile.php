<?php
session_start();
require_once '../db_connect.php';

// รับค่า `tech_id` จาก URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Error: Technician ID is missing.");
}

$tech_id = intval($_GET['id']); // แปลงเป็น integer เพื่อความปลอดภัย

// ดึงข้อมูลช่างเทคนิค
$technician_query = $conn->prepare("SELECT first_name, last_name, exp, education, level FROM technicians WHERE tech_id = ?");
$technician_query->bind_param("i", $tech_id);
$technician_query->execute();
$technician_result = $technician_query->get_result();
$technician = $technician_result->fetch_assoc();

if (!$technician) {
    die("Error: Technician not found.");
}

// ดึงข้อมูลคะแนนเฉลี่ย
$rating_query = $conn->prepare(
    "SELECT AVG(f.rating) AS average_rating
     FROM feedback f
     JOIN service_requests sr ON f.request_id = sr.request_id
     WHERE sr.tech_id = ?"
);
$rating_query->bind_param("i", $tech_id);
$rating_query->execute();
$rating_result = $rating_query->get_result();
$rating_data = $rating_result->fetch_assoc();
$average_rating = $rating_data['average_rating'] ?? 0;

// คำนวณจำนวนดาวและสี
$star_color = 'gray';
$star_count = 0;
if ($average_rating > 4.6) {
    $star_count = 5;
    $star_color = 'yellow';
} elseif ($average_rating > 3.6) {
    $star_count = 4;
    $star_color = 'yellow';
} elseif ($average_rating > 2.6) {
    $star_count = 3;
    $star_color = 'yellow';
} elseif ($average_rating > 1.6) {
    $star_count = 2;
    $star_color = 'yellow';
} elseif ($average_rating > 0) {
    $star_count = 1;
    $star_color = 'yellow';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Technician Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/brands.min.css" rel="stylesheet">
    <style>
        * {
            font-family: 'Kanit', sans-serif;
        }

        body {
            background-color: #f8f9fa;
            font-family: 'Kanit', sans-serif;
            padding-top: 80px;
        }

        .profile-box {
            background-color: #ffffff;
            border-radius: 16px;
            padding: 20px;
            max-width: 500px;
            margin: auto;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .profile-header {
            text-align: center;
        }

        .technician-name {
            font-size: 1.3rem;
            font-weight: 600;
            color: #333333;
        }

        .profile-image {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 15px;
        }

        .stars i {
            font-size: 1.2rem;
            color: #ffc107;
        }

        .feedback-box {
            background-color: #f1f1f1;
            border-radius: 8px;
            padding: 15px;
            margin-top: 20px;
            display: none;
        }

        .feedback-header {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .divider {
            border-top: 1px solid #ddd;
            margin: 20px 0;
        }

        .btn-feedback {
            background-color: #0d6efd;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 8px 16px;
            cursor: pointer;
        }

        .btn-feedback:hover {
            background-color: #0056b3;
        }

        .feedback-text {
            display: block;
            margin-top: 10px;
            padding: 5px;
        }

        .experience-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            background-color: rgb(149, 243, 99);
            color: #333333;
            font-weight: 600;
            padding: 5px 10px;
            border-radius: 10px;
            font-size: 0.9rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .profile-box {
            position: relative;
        }

        .technician-education {
            font-size: 1rem;
            margin-top: 5px;
            color: #6c757d;
        }

        .level {
            font-size: 14px;
            font-weight: bold;
            color: #007bff;
            /* หรือสีที่คุณต้องการ */
            margin-top: 25px;
            /* เพิ่มระยะห่างจาก experience-badge */
        }

        .bookmark-icon {
            position: absolute;
            top: 10px;
            right: 10px;
            cursor: pointer;
        }
    </style>
    <script>
        function toggleFeedbackBox() {
            const feedbackBox = document.getElementById('feedback-box');
            feedbackBox.style.display = feedbackBox.style.display === 'block' ? 'none' : 'block';
        }
    </script>
</head>

<body>
<nav class="navbar navbar-light bg-white shadow-sm" style="position: fixed; top: 0; left: 0; right: 0; z-index: 1030;">
    <div class="container-fluid p-2">
        <a href="javascript:history.back()" class="text-decoration-none text-dark">
            <i class="fas fa-chevron-left"></i>
        </a>
        <span class="navbar-brand mx-auto fw-bold">ย้อนกลับ</span>
    </div>
</nav>

    <div class="profile-box">
        <p class="experience-badge"><?= htmlspecialchars($technician['exp']) ?></p>

        <div class="profile-header">
            <img src="icon/profile.png" alt="Profile" class="profile-image">
            <div class="technician-name"><?= htmlspecialchars($technician['first_name'] . ' ' . $technician['last_name']) ?></div>
            <div class="technician-education text-muted"><?= htmlspecialchars($technician['education']) ?></div>

            <div class="stars">
                <?php for ($i = 0; $i < 5; $i++): ?>
                    <i class="bi <?= $i < $star_count ? 'bi-star-fill' : 'bi-star' ?>"></i>
                <?php endfor; ?>
            </div>

            <!-- เพิ่มไอคอน bookmark -->
            <div class="bookmark-icon" onclick="toggleBookmark(<?= $tech_id ?>)">
                <i id="bookmark-icon" class="bi bi-bookmark" style="font-size: 24px; color: gray;"></i>
            </div>
        </div>
        <br>
        <button class="btn-feedback" onclick="toggleFeedbackBox()">
            <i class="bi bi-chat-dots"> </i> รีวิวเเละคะเเนน
        </button>

        <!-- Feedback Box -->
        <div id="feedback-box" class="feedback-box">
            <div class="feedback-header">รีวิว</div>
            <?php
            // Query สำหรับดึงรีวิว
            $feedback_query = $conn->prepare(
                "SELECT rating, feedback_text FROM feedback f 
             JOIN service_requests sr ON f.request_id = sr.request_id
             WHERE sr.tech_id = ?"
            );
            $feedback_query->bind_param("i", $tech_id);
            $feedback_query->execute();
            $feedback_result = $feedback_query->get_result();

            while ($feedback = $feedback_result->fetch_assoc()):
            ?>
                <div class="stars">
                    <?php for ($i = 0; $i < 5; $i++): ?>
                        <i class="bi <?= $i < $feedback['rating'] ? 'bi-star-fill' : 'bi-star' ?>"></i>
                    <?php endfor; ?>
                </div>
                <p class="text-muted feedback-text"><?= htmlspecialchars($feedback['feedback_text']) ?></p>
                <hr>
            <?php endwhile; ?>
        </div>
    </div>

    <div class="divider"></div>






    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>

<script>
    function toggleBookmark(tech_id) {
    var icon = document.getElementById("bookmark-icon");
    var currentColor = icon.style.color;

    // Toggle สีทองเมื่อคลิก
    if (currentColor === 'gold') {
        icon.style.color = 'gray'; // ถ้าเป็นทองเปลี่ยนกลับเป็นสีเทา
        updateBookmark(tech_id, 0); // บันทึกข้อมูลเมื่อไม่เป็น bookmark
    } else {
        icon.style.color = 'gold'; // เปลี่ยนเป็นสีทอง
        updateBookmark(tech_id, 1); // บันทึกข้อมูลเมื่อเป็น bookmark
    }
}

// ฟังก์ชันสำหรับบันทึกข้อมูลลงฐานข้อมูล
function updateBookmark(tech_id, status) {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'update_bookmark.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function() {
        if (xhr.readyState == 4 && xhr.status == 200) {
            console.log('Bookmark status updated.');
        }
    };
    xhr.send('tech_id=' + tech_id + '&status=' + status);
}

</script>