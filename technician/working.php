<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['tech_id'])) {
    die("Unauthorized access.");
}

$tech_id = intval($_SESSION['tech_id']);

// Query to get the latest request ID
$query = $conn->prepare("SELECT request_id FROM service_requests WHERE tech_id = ? ORDER BY created_at DESC LIMIT 1");
$query->bind_param("i", $tech_id);
$query->execute();
$result = $query->get_result();

if ($result->num_rows === 0) {
    die("No active request found for this technician.");
}

$data = $result->fetch_assoc();
$request_id = $data['request_id'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>บันทึกการทำงาน</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            font-family: 'Kanit', sans-serif;
        }
        body {
            background-color: #f8f9fa;
        }
        .custom-container {
            max-width: 500px;
            margin: 2rem auto;
            padding: 2rem;
            background-color: #f8f9fa;
            border-radius: 1rem;
        }
        .header-title {
    text-align: center; /* จัดตำแหน่งข้อความให้อยู่ตรงกลาง */
    margin: 0 auto; /* จัดตำแหน่ง div ให้อยู่ตรงกลาง */
    margin-bottom:30px;

    font-size: 24px; /* ขนาดตัวอักษร */
    font-weight: bold; /* ความหนาของตัวอักษร */
    padding: 10px; /* ระยะห่างภายใน div */
    background-color: #007bff;
    border-radius: 25px;
    width: 70%;
}
        .form-control {
            border: 2px solid #E8F0FE;
            border-radius: 0.75rem;
            padding: 1rem;
        }
        textarea.form-control {
            min-height: 200px;
        }
        .image-upload-btn {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem;
            border: 2px solid #E8F0FE;
            border-radius: 0.75rem;
            cursor: pointer;
            margin-bottom: 1rem;
        }
        .image-upload-btn img {
            width: 32px;
            height: 32px;
        }
        .submit-btn {
            background-color: #FF8C00;
            color: white;
            width: 100%;
            padding: 0.75rem;
            border: none;
            border-radius: 50px;
            font-size: 1.25rem;
            margin-top: 1rem;
        }
        .image-preview {
            position: relative;
            display: inline-block;
            margin: 5px;
        }
        .image-preview img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 0.5rem;
        }
        .remove-icon {
            position: absolute;
            top: -8px;
            right: -8px;
            background: red;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            text-align: center;
            line-height: 20px;
            cursor: pointer;
        }
        #images {
            display: none;
        }
    </style>
</head>
<body>
    <div class="custom-container">
        <div class="header-title text-white">บันทึกการทำงาน</div>
        
        <form id="working-form" enctype="multipart/form-data">
            <input type="hidden" name="request_id" value="<?php echo $request_id; ?>">
            
            <div class="mb-4">
                <label for="note" class="form-label">รายละเอียด</label>
                <textarea class="form-control" id="note" name="note" placeholder="รายละเอียดการทำงาน..."></textarea>
            </div>

            <label class="image-upload-btn" for="images">
                <img src="icon/gallery.png" alt="Upload">
                <span>เพิ่มรูปภาพ</span>
            </label>
            <input type="file" class="form-control" id="images" name="images[]" accept="image/*" multiple>
            
            <div id="image-previews" class="d-flex flex-wrap"></div>

            <button type="submit" class="submit-btn">บันทึก</button>
        </form>
    </div>

    <script>
        document.getElementById('images').addEventListener('change', function(event) {
            const imageContainer = document.getElementById('image-previews');
            imageContainer.innerHTML = '';

            Array.from(event.target.files).forEach((file, index) => {
                const reader = new FileReader();

                reader.onload = function(e) {
                    const div = document.createElement('div');
                    div.classList.add('image-preview');

                    const img = document.createElement('img');
                    img.src = e.target.result;
                    div.appendChild(img);

                    const removeIcon = document.createElement('span');
                    removeIcon.textContent = '×';
                    removeIcon.classList.add('remove-icon');
                    removeIcon.onclick = function() {
                        div.remove();
                    };
                    div.appendChild(removeIcon);

                    imageContainer.appendChild(div);
                };

                reader.readAsDataURL(file);
            });
        });

        document.getElementById('working-form').addEventListener('submit', function(event) {
            event.preventDefault();
            const formData = new FormData(this);

            fetch('save_working.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                alert(data);
                window.location.href = 'dashboard.php';
            })
            .catch(error => console.error(error));
        });
    </script>

<script>
        // Disable refresh using F5 or Ctrl+R
        document.addEventListener('keydown', function(event) {
            if ((event.key === 'F5') || (event.ctrlKey && event.key === 'r')) {
                event.preventDefault(); // ป้องกันการรีเฟรช
                alert('ไม่สามารถรีเฟรชหน้าเว็บนี้ได้');
            }
        });

        // Disable refresh through browser's reload or navigation
        window.addEventListener('beforeunload', function(event) {
            event.preventDefault();
            // บางเบราว์เซอร์อาจต้องการ returnValue สำหรับข้อความแจ้งเตือน
            event.returnValue = '';
        });

        // Disable back button (history)
        window.history.pushState(null, '', window.location.href);
        window.onpopstate = function() {
            window.history.pushState(null, '', window.location.href);
        };
    </script>
</body>
</html>