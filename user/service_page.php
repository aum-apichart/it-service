<?php
session_start();
require_once '../db_connect.php';

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ดึงข้อมูลจากตาราง service_types
$sql = "SELECT * FROM service_types";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IT Service</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* CSS เดิมของคุณจะถูกรักษาไว้ */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Kanit', sans-serif;
        }

        body {
            background-color: #f5f5f5;
            min-height: 100vh;
            padding-bottom: 70px;
        }

        .navbar {
            position: fixed;
            top: 0px;
            width: 100%;

            background: white;

            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            display: flex;
            align-items: center;
        }

        .back-btn {
            border: none;
            background: none;
            font-size: 1.5rem;
            color: #666;
            cursor: pointer;
            padding: 0.5rem;
        }

        .nav-title {
            flex-grow: 1;
            text-align: center;
            color: #2563eb;
            font-size: 1.2rem;
            font-weight: 500;
        }

        .container {
            max-width: 600px;
            margin: 80px auto 0;
            padding: 1rem;
        }

        .service-box {
            background: white;
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1rem;
            margin-top: 1 rem;
            border: 2px solid transparent;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .service-box.selected {
            border-color: #2563eb;
            background: #f0f7ff;
        }

        .service-icon {
            width: 40px;
            height: 40px;
            margin-right: 1rem;
            color: #2563eb;
        }

        .description-box {
            background: white;
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 0.1rem;
            width: 100%;
            min-height: 100px;
            border: 1px solid #ddd;
            resize: none;
        }

        .service-info {
            flex-grow: 1;

        }

        .service-name {
            font-weight: 500;
            color: #333;
        }

        .service-price {
            color: #666;
            margin-top: 0.25rem;
        }

        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            background: white;
            padding: 1rem;
            box-shadow: 0 -2px 4px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .total-price {
            font-size: 1.1rem;
            /* ขนาดตัวอักษรพื้นฐานสำหรับข้อความ */
            color: black;
            /* สีของข้อความปกติ */
        }

        #total {
            color: #f77f4f;
            /* สีส้ม */
            font-size: 1.5rem;
            /* ขนาดใหญ่ */
            font-weight: bold;
            /* ตัวหนา */
        }

        .next-btn {
            background: #2563eb;
            color: white;
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
        }

        .next-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        .image-upload-section {

            margin-top: 5px;
            text-align: center;
        }

        .image-upload-label {
            display: inline-block;
            background-color: #d9d9d9;
            color: #333;
            padding: 10px 20px;
            border-radius: 10px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
        }

        .image-upload-label:hover {
            background-color: #b0b0b0;
        }

        .image-upload-input {
            display: none;
        }

        .image-preview-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }

        .image-preview {
            width: 100px;
            height: 100px;
            border: 1px solid #ccc;
            border-radius: 5px;
            overflow: hidden;
            position: relative;
        }

        .image-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .remove-image {
            position: absolute;
            top: 5px;
            right: 5px;
            background-color: rgba(0, 0, 0, 0.5);
            color: #fff;
            border: none;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
        }


        .image-upload-header {
            margin: 10px 0;
            text-align: center;
            cursor: pointer;
        }

        .upload-label {
            display: inline-block;
            padding: 10px 15px;
            background-color: #007BFF;
            color: white;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
        }

        .uploaded-images-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }

        .uploaded-image-wrapper {
            position: relative;
            display: inline-block;
        }

        .uploaded-image-wrapper img {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid #ccc;
        }

        .remove-image-btn {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: red;
            color: white;
            border: none;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 14px;
            cursor: pointer;
        }

        #locationButton {
            background-color: #f77f4f;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background-color 0.3s, transform 0.2s;
        }

        /* ปรับตำแหน่งไอคอน */
        #locationButton img {
            width: 20px;
            height: 20px;
            margin-right: 8px;
            /* กำหนดระยะห่างระหว่างไอคอนกับข้อความ */
        }

        /* เอฟเฟ็กต์เมื่อเลื่อนเมาส์ */
        #locationButton:hover {
            background-color: #f96133;
            transform: scale(1.05);
            /* ขยายขนาดเล็กน้อย */
        }

        #locationButton:active {
            transform: scale(1);
            /* เมื่อคลิกจะคืนขนาดปกติ */
        }

        .service-distance {
            background-color: #f9f9f9;
            border-radius: 8px;
            border: 1px solid #ddd;
            padding: 20px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            margin: 20px 0;
            display: flex;
            flex-direction: column;
            gap: 15px;
            /* เว้นระยะห่างระหว่างแต่ละองค์ประกอบ */
        }

        /* ปรับสไตล์สำหรับ label */
        .service-distance label {
            font-size: 16px;
            font-weight: 600;
            color: #333;
        }

        #distance-value {
            font-size: 24px;
            /* ขนาดตัวอักษรใหญ่ขึ้น */
            font-weight: bold;
            /* ทำให้ตัวหนา */
            color: #FF6347;
            /* สีส้มแดง */
            margin-left: 5px;
            /* เพิ่มระยะห่างเล็กน้อยจากคำว่า "ระยะทางบริการ" */
        }

        .original-price {
            color: #888;
            /* สีเทา */
            text-decoration: line-through;
            /* ขีดเส้นทับ */
            margin-right: 8px;
            /* เพิ่มระยะห่างจากราคาใหม่ */
        }

        /* ตกแต่งราคาหลัก */
        .base-price {
            font-size: 21px;
            /* ขนาดใหญ่ขึ้น */
            font-weight: bold;
            /* ทำให้ตัวหนา */
            color: #f77f4f;
            /* สีเขียว */
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-light bg-white shadow-sm">
        <div class="container-fluid">
            <a href="a_home.php" class="back-button">
                <i class="fas fa-chevron-left"></i>
            </a>
            <span class="navbar-brand mx-auto fw-bold">เลือกบริการ</span>
        </div>
    </nav>

    <div class="container">
        <div class="service-boxes">
            <?php
            if ($result->num_rows > 0) {
                // แสดงข้อมูลของบริการทั้งหมด
                while ($row = $result->fetch_assoc()) {
                    $service_name = $row['service_name'];
                    $base_price = $row['base_price'];
                    $service_id = $row['service_type_id'];
                    $normal_price = $row['normal_price']; // สมมุติว่า id คือ primary key ของตาราง
            ?>
                    <div class="service-box" data-price="<?= $base_price ?>" data-id="<?= $service_id ?>">
                        <i class="fas fa-tools service-icon"></i>
                        <div class="service-info">
                            <div class="service-name"><?= $service_name ?></div>
                            <div class="service-price">
                                <span class="original-price">฿<?= number_format($normal_price, 2) ?></span>
                                <span class="base-price">฿<?= number_format($base_price, 2) ?></span>
                            </div>
                        </div>
                    </div>
            <?php
                }
            } else {
                echo "ไม่มีข้อมูลบริการ";
            }
            ?>
        </div>

        <textarea class="description-box" placeholder="รายละเอียดปัญหา"></textarea>
        <div class="image-upload-section">
            <div class="image-upload-header">
                <label for="imageUpload" class="upload-label">
                    <i class="fas fa-plus-circle"></i> เพิ่มรูปภาพ

                </label>
                <input type="file" id="imageUpload" accept="image/*" multiple style="display: none;">
            </div>
            <div class="uploaded-images-container"></div>
        </div>





        <div class="service-distance">
            <label>ระยะทางบริการ <span id="distance-value">5 </span> กม. </label>
            <input type="range" class="distance-slider" min="5" max="20" value="5">
            <button id="locationButton">
                <img src="icon/location.png" alt="Location Icon"> ปักหมุดตำแหน่ง
            </button>
        </div>
        <div id="map" style="width: 100%; height: 400px;"></div>
    </div>


    <footer class="footer">
        <div class="total-price">
            <span id="total">0</span>
        </div>
        <button class="next-btn" disabled>ถัดไป</button>
    </footer>

    <script src="https://api.longdo.com/map/?key=b9fe7ccc5152cdbda6073ed67dded084"></script>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const locationButton = document.getElementById("locationButton");
            const serviceBoxes = document.querySelectorAll(".service-box");
            const distanceSlider = document.querySelector(".distance-slider");
            const distanceValue = document.getElementById("distance-value");
            const totalPriceElement = document.getElementById("total");
            const nextButton = document.querySelector(".next-btn");
            const descriptionBox = document.querySelector(".description-box");
            const imageUpload = document.getElementById("imageUpload");
            const uploadedImagesContainer = document.querySelector(".uploaded-images-container");

            let selectedService = null;
            let selectedPrice = 0;
            let distance = parseFloat(distanceSlider.value);
            let map = new longdo.Map({
                placeholder: document.getElementById("map")
            });
            let currentMarker = null;
            let selectedImages = [];
            let imageFiles = [];
            let latitude = null;
            let longitude = null;

            imageUpload.addEventListener('change', (event) => {
                const files = Array.from(event.target.files);

                if (imageFiles.length + files.length > 15) {
                    alert("สามารถเพิ่มรูปภาพได้ไม่เกิน 15 รูป");
                    return;
                }

                files.forEach(file => {
                    if (imageFiles.length >= 15) return;

                    imageFiles.push(file);
                    const reader = new FileReader();

                    reader.onload = function(e) {
                        const imageWrapper = document.createElement('div');
                        imageWrapper.classList.add('uploaded-image-wrapper');

                        const img = document.createElement('img');
                        img.src = e.target.result;

                        const removeBtn = document.createElement('button');
                        removeBtn.classList.add('remove-image-btn');
                        removeBtn.innerHTML = '&times;';
                        removeBtn.addEventListener('click', () => {
                            const index = imageFiles.indexOf(file);
                            if (index > -1) {
                                imageFiles.splice(index, 1);
                                uploadedImagesContainer.removeChild(imageWrapper);
                            }
                        });

                        imageWrapper.appendChild(img);
                        imageWrapper.appendChild(removeBtn);
                        uploadedImagesContainer.appendChild(imageWrapper);
                    };

                    reader.readAsDataURL(file);
                });

                imageUpload.value = "";
            });

            serviceBoxes.forEach(box => {
                box.addEventListener("click", () => {
                    serviceBoxes.forEach(b => b.classList.remove("selected"));
                    box.classList.add("selected");

                    selectedService = box.getAttribute("data-id");
                    selectedPrice = parseFloat(box.getAttribute("data-price"));

                    calculateTotal();
                });
            });

            distanceSlider.addEventListener("input", () => {
                distance = parseFloat(distanceSlider.value);
                distanceValue.textContent = distance;
                calculateTotal();
            });

            locationButton.addEventListener("click", () => {
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition((position) => {
                        latitude = position.coords.latitude;
                        longitude = position.coords.longitude;

                        if (currentMarker) map.Overlays.remove(currentMarker);
                        currentMarker = new longdo.Marker({
                            lon: longitude,
                            lat: latitude
                        });
                        map.Overlays.add(currentMarker);
                        map.location({
                            lon: longitude,
                            lat: latitude
                        }, true);
                        alert(`ตำแหน่งของคุณคือ Latitude: ${latitude}, Longitude: ${longitude}`);
                    }, () => alert("ไม่สามารถดึงตำแหน่งได้"));
                }
            });

            nextButton.addEventListener('click', () => {
                if (!latitude || !longitude) {
                    alert("กรุณาปักหมุดตำแหน่งก่อนดำเนินการต่อ");
                    return;
                }

                const description = descriptionBox.value;
                const formData = new FormData();
                formData.append("service_type_id", selectedService);
                formData.append("description", description);
                formData.append("distance", distance);
                formData.append("total_price", totalPriceElement.textContent);

                imageFiles.forEach((file, index) => {
                    formData.append(`images[]`, file);
                });

                formData.append("latitude", latitude);
                formData.append("longitude", longitude);

                fetch("service_submit.php", {
                        method: "POST",
                        body: formData
                    })
                    .then(response => response.text())
                    .then(data => {
                        alert(data);
                        window.location.href = "confirm.php";
                    })
                    .catch(error => console.error("Error:", error));
            });

            function calculateTotal() {
                if (selectedService) {
                    const totalPrice = selectedPrice + (distance * 10);
                    totalPriceElement.textContent = totalPrice.toFixed(2);
                    nextButton.disabled = false;
                } else {
                    totalPriceElement.textContent = "0";
                    nextButton.disabled = true;
                }
            }
        });
    </script>

</body>

</html>