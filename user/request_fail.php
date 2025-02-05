<?php
session_start();
require_once '../db_connect.php';

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fail</title>
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
        .fail-message {
            font-size: 3rem;
            font-weight: bold;
            color: #dc3545;
        }
        .icon {
        font-size: 5rem;
        color: #6c757d;
        margin: 5px 0;
    }
    .icon img {
        width: 130px; /* ปรับขนาดตามที่ต้องการ */
        height: auto; /* รักษาอัตราส่วนของรูปภาพ */
    }
        .btn-container {
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="fail-message">ล้มเหลว</div>
        <p>ขออภัยในความไม่สะดวก</p>
        
        <div class="icon"><img src="icon/fail2.png" alt="Fail Icon"></div>
        <div class="btn-container">
        <a href="a_home.php" class="btn btn-primary" style="border-radius: 30px; width: 100px">ตกลง</a>
        </div>
    </div>
</body>
</html>
