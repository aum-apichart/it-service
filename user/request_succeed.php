<?php
// filepath: /c:/xampp/htdocs/test/user/request_succeed.php
session_start();
require_once '../db_connect.php';

// ตรวจสอบสถานะการเชื่อมต่อ
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ตรวจสอบว่ามีการส่งฟอร์ม
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ตรวจสอบว่า session สำหรับ request_id และ user_id มีอยู่หรือไม่
    if (isset($_SESSION['request_id'], $_SESSION['user_id'])) {
        $request_id = intval($_SESSION['request_id']);
        $user_id = intval($_SESSION['user_id']);
        $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
        $feedback_text = isset($_POST['feedback']) ? trim($_POST['feedback']) : '';

        // ตรวจสอบค่าของ rating ว่าอยู่ในช่วงที่กำหนด (1-5)
        if ($rating < 1 || $rating > 5) {
            die('Invalid rating value.');
        }

        // เตรียมคำสั่ง SQL สำหรับบันทึกข้อมูลลงในฐานข้อมูล
        $sql = "INSERT INTO feedback (request_id, user_id, rating, feedback_text) VALUES (?, ?, ?, ?)";
        if ($stmt = $conn->prepare($sql)) {
            // ผูกค่าพารามิเตอร์กับคำสั่ง SQL
            $stmt->bind_param('iiis', $request_id, $user_id, $rating, $feedback_text);

            // ดำเนินการคำสั่ง SQL
            if ($stmt->execute()) {
                echo "<script>alert('ขอบคุณสำหรับคำแนะนำและการให้คะแนน!'); window.location.href = 'a_home.php';</script>";
            } else {
                echo "<script>alert('เกิดข้อผิดพลาดในการบันทึกข้อมูล: " . $stmt->error . "');</script>";
            }

            // ปิดคำสั่ง SQL
            $stmt->close();
        } else {
            echo "<script>alert('เกิดข้อผิดพลาดในการเตรียมคำสั่ง SQL: " . $conn->error . "');</script>";
        }
    } else {
        echo "<script>alert('Session data for request_id or user_id is missing.'); window.location.href = 'a_home.php';</script>";
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Success</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
         * {
           
            font-family: 'Kanit', sans-serif;
         }
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #f8f9fa;
            margin: 0;
            font-family: 'Arial', sans-serif;
        }

        .container {
            text-align: center;
        }

        .success-message {
            font-size: 3rem;
            font-weight: bold;
            color: #28a745;
        }

        .stars {
            display: flex;
            justify-content: center;
            margin: 20px 0;
        }

        .star {
            font-size: 2rem;
            color: #ddd;
            cursor: pointer;
            transition: color 0.3s;
        }

        .star.hovered,
        .star.selected {
            color: gold;
        }

        .custom-textarea {
            width: 280px;
            /* กำหนดความกว้าง */
            height: 110px;
            /* กำหนดความสูง */
            border: 1px solid #d1d5db;
            /* สีของขอบ */
            border-radius: 15px;
            /* ทำขอบมน */
            padding: 8px;
            /* เพิ่มระยะห่างภายใน */
            outline: none;
            /* ลบขอบที่แสดงเมื่อโฟกัส */
            font-family: Arial, sans-serif;
            /* ตั้งค่าฟอนต์ */
            font-size: 14px;
            /* ขนาดตัวอักษร */
            transition: box-shadow 0.2s ease, border-color 0.2s ease;
            /* เพิ่มเอฟเฟกต์การเปลี่ยนแปลง */
        }

        .custom-textarea:focus {
            border-color: #3b82f6;
            /* เปลี่ยนสีขอบเมื่อโฟกัส */
            box-shadow: 0 0 4px rgba(59, 130, 246, 0.5);
            /* เพิ่มเงาสีฟ้าเมื่อโฟกัส */
        }

        .custom-button {
            width: 280px;

            background-color: #007bff;
            /* สีพื้นหลัง */
            color: #ffffff;
            /* สีตัวอักษร */
            border: none;
            /* ไม่มีขอบ */
            border-radius: 8px;
            /* ขอบมน */
            padding: 10px 20px;
            /* เพิ่มระยะห่างภายใน */
            font-size: 20px;
            /* ขนาดตัวอักษร */
            cursor: pointer;
            /* เปลี่ยนเคอร์เซอร์เป็นมือ */
            transition: background-color 0.3s ease, transform 0.2s ease;
            /* เพิ่มเอฟเฟกต์เมื่อโฮเวอร์ */
        }

        .custom-button:hover {
            background-color: #0056b3;
            /* เปลี่ยนสีพื้นหลังเมื่อโฮเวอร์ */
            transform: scale(1.05);
            /* ขยายขนาดเล็กน้อยเมื่อโฮเวอร์ */
        }

        .custom-button:active {
            background-color: #004085;
            /* สีพื้นหลังเมื่อคลิก */
            transform: scale(0.95);
            /* ย่อขนาดเล็กน้อยเมื่อคลิก */
        }
    </style>
</head>

<body>
    <div class="container">
        <img src="icon/accept.png" alt="" class="icon-accept w-25 h-25 mb-4">
        <div class="success-message">เสร็จสิ้น</div>
        <div>ให้คะเเนนกับบริการนี้</div>
        <div class="mt-4">
            <div class="stars">
                <span class="star" data-value="1">&#9733;</span>
                <span class="star" data-value="2">&#9733;</span>
                <span class="star" data-value="3">&#9733;</span>
                <span class="star" data-value="4">&#9733;</span>
                <span class="star" data-value="5">&#9733;</span>
            </div>
            <form class="mt-3" method="post" action="">
                <input type="hidden" name="rating" id="rating" value="0">
                <div class="mb-3">
                    <textarea
                        class="custom-textarea"
                        name="feedback"
                        placeholder="รีวิวและคำแนะนำ">
                    </textarea>
                </div>


                <button type="submit" class="custom-button">ยืนยัน</button>
            </form>
        </div>
    </div>

    <script>
    const stars = document.querySelectorAll('.star');
    const ratingInput = document.getElementById('rating');

    stars.forEach((star, index) => {
        star.addEventListener('mouseover', () => {
            for (let i = 0; i <= index; i++) {
                stars[i].classList.add('hovered');
            }
        });

        star.addEventListener('mouseout', () => {
            stars.forEach(s => s.classList.remove('hovered'));
        });

        star.addEventListener('click', () => {
            stars.forEach(s => s.classList.remove('selected'));
            for (let i = 0; i <= index; i++) {
                stars[i].classList.add('selected');
            }
            ratingInput.value = index + 1; // อัปเดตค่า rating
        });
    });

    // ตรวจสอบค่าก่อนส่งฟอร์ม
    const form = document.querySelector('form');
    form.addEventListener('submit', (event) => {
        if (ratingInput.value === "0") {
            event.preventDefault();
            alert('คุณยังไม่ได้ให้คะแนน!');
        } else if (ratingInput.value !== "") {
            event.preventDefault(); // หยุดการส่งฟอร์มชั่วคราว
            const popup = document.createElement('div');
            popup.textContent = 'ขอบคุณสำหรับรีวิว!';
            popup.style.position = 'fixed';
            popup.style.top = '0'; /* ตำแหน่งด้านบน */
            popup.style.left = '50%';
            popup.style.transform = 'translateX(-50%)'; /* จัดกึ่งกลางแนวนอน */
            popup.style.backgroundColor = '#28a745';
            popup.style.color = 'white';
            popup.style.padding = '15px 30px';
            popup.style.borderRadius = '0 0 8px 8px'; /* ทำขอบมนเฉพาะด้านล่าง */
            popup.style.boxShadow = '0 4px 8px rgba(0, 0, 0, 0.2)';
            popup.style.zIndex = '9999';
            document.body.appendChild(popup);

            // แสดง popup เป็นเวลา 2 วินาที
            setTimeout(() => {
                popup.remove(); // ลบ popup
                window.location.href = 'a_home.php'; // ลิงค์ไปที่หน้าใหม่
            }, 2000);
        }
    });
</script>


</body>

</html>