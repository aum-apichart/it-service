<?php
session_start();
require_once '../db_connect.php'; 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เติม Coin</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto mt-10">
        <div class="max-w-md mx-auto bg-white p-6 rounded-lg shadow-md">
            <h1 class="text-2xl font-bold mb-4 text-center">เติม Coin</h1>
            <?php
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $userId = intval($_POST['user_id']);
                $amount = intval($_POST['amount']);

                // เชื่อมต่อฐานข้อมูล
                $conn = new mysqli("localhost", "root", "", "itservice");
                if ($conn->connect_error) {
                    die("เชื่อมต่อฐานข้อมูลล้มเหลว: " . $conn->connect_error);
                }

                // อัปเดต Coin
                $stmt = $conn->prepare("UPDATE users SET coin = coin + ? WHERE user_id = ?");
                $stmt->bind_param("ii", $amount, $userId);

                if ($stmt->execute()) {
                    echo "<p class='text-green-500'>เติม Coin สำเร็จ!</p>";
                } else {
                    echo "<p class='text-red-500'>เกิดข้อผิดพลาด: " . $stmt->error . "</p>";
                }

                $stmt->close();
                $conn->close();
            }
            ?>

            <form action="" method="POST">
                <div class="mb-4">
                    <label for="user_id" class="block text-sm font-medium">รหัสผู้ใช้ (User ID)</label>
                    <input type="number" id="user_id" name="user_id" class="w-full border-gray-300 rounded-lg shadow-sm" required>
                </div>
                <div class="mb-4">
                    <label for="amount" class="block text-sm font-medium">จำนวน Coin</label>
                    <input type="number" id="amount" name="amount" class="w-full border-gray-300 rounded-lg shadow-sm" required>
                </div>
                <button type="submit" class="w-full bg-blue-500 text-white py-2 px-4 rounded-lg hover:bg-blue-600">
                    เติม Coin
                </button>
            </form>
        </div>
    </div>
</body>
</html>
