<?php
session_start();
require_once '../db_connect.php';

// ตรวจสอบว่าผู้ใช้ล็อกอินหรือไม่
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// ดึงข้อมูลผู้ใช้จากฐานข้อมูล
$sql = "SELECT user_id, first_name, last_name, email, phone, address, user_role, coin FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    echo "ไม่พบข้อมูลผู้ใช้";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Account Settings</title>
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <style>
    body {
      background-color: #f8f9fa;
    }
    .container {
      max-width: 800px;
    }
    .card {
      border-radius: 15px;
      border: none;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
    .card-header {
      background-color: #007bff;
      color: white;
      border-radius: 15px 15px 0 0;
      font-weight: bold;
    }
    .form-control {
      border-radius: 10px;
    }
    .btn-primary {
      border-radius: 10px;
    }
    .coin-display {
      background-color: #e9ecef;
      padding: 10px;
      border-radius: 10px;
      text-align: center;
      font-size: 1.2em;
    }
    footer {
    background-color: #f8f9fa;
    text-align: center;
    padding: 10px 0;
    position: fixed;
    bottom: 0;
    left: 0; /* ทำให้ชิดซ้ายสุด */
    width: 100vw; /* กว้างเต็มจอ */
    box-shadow: 0 -1px 5px rgba(0, 0, 0, 0.1);
    margin: 0; /* ลบ margin */
}
.footer-icon {
    width: 38px;
    height: 38px;
    object-fit: cover;
    margin-right: 0; /* ลบการเว้นระยะเพิ่มเติม */
}


  </style>
</head>
<body>
<nav class="navbar navbar-light bg-white shadow-sm">
    <div class="container-fluid">
        <a href="a_home.php" class="back-button">
            <i class="fas fa-chevron-left"></i>
        </a>
        <span class="navbar-brand mx-auto fw-bold">ประวัติการใช้บริการ</span>
    </div>
</nav>


  <div class="container mt-5">
    <div class="card">
      <div class="card-header">Account Settings</div>
      <div class="card-body">
        <form action="update_account.php" method="POST">
          <div class="mb-3">
            <label for="first_name" class="form-label">First Name</label>
            <input type="text" class="form-control" id="first_name" name="first_name" value="<?= htmlspecialchars($user['first_name']) ?>" required>
          </div>
          <div class="mb-3">
            <label for="last_name" class="form-label">Last Name</label>
            <input type="text" class="form-control" id="last_name" name="last_name" value="<?= htmlspecialchars($user['last_name']) ?>" required>
          </div>
          <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
          </div>
          <div class="mb-3">
            <label for="phone" class="form-label">Phone</label>
            <input type="text" class="form-control" id="phone" name="phone" value="<?= htmlspecialchars($user['phone']) ?>" required>
          </div>
          <div class="mb-3">
            <label for="address" class="form-label">Address</label>
            <textarea class="form-control" id="address" name="address" rows="3"><?= htmlspecialchars($user['address']) ?></textarea>
          </div>
          <div class="mb-3">
            <label for="user_role" class="form-label">Role</label>
            <select class="form-control" id="user_role" name="user_role" disabled>
              <option <?= $user['user_role'] == 'user' ? 'selected' : '' ?>>user</option>
              <option <?= $user['user_role'] == 'technician' ? 'selected' : '' ?>>technician</option>
              <option <?= $user['user_role'] == 'admin' ? 'selected' : '' ?>>admin</option>
            </select>
          </div>
          <div class="mb-4">
            <div class="coin-display">
              <strong>Coins:</strong> <?= htmlspecialchars($user['coin']) ?>
            </div>
          </div>
          <button type="submit" class="btn btn-primary w-100">Save Changes</button>
        </form>
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
