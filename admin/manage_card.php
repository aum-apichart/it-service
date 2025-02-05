<?php
require_once '../db_connect.php'; // เชื่อมต่อฐานข้อมูล

// เพิ่ม/แก้ไขข้อมูล
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $title = $_POST['title'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $image_url = $_POST['image_url'];
    $link_url = $_POST['link_url'];

    if ($id) {
        // แก้ไขข้อมูล
        $sql = "UPDATE card SET title = ?, description = ?, price = ?, image_url = ?, link_url = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssdssi", $title, $description, $price, $image_url, $link_url, $id);
    } else {
        // เพิ่มข้อมูลใหม่
        $sql = "INSERT INTO card (title, description, price, image_url, link_url) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssdss", $title, $description, $price, $image_url, $link_url);
    }

    $stmt->execute();
    header("Location: manage_card.php");
    exit();
}

// ลบข้อมูล
if ($_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = $_GET['id'];
    $sql = "DELETE FROM card WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: manage_card.php");
    exit();
}

// ดึงข้อมูลทั้งหมด
$sql = "SELECT * FROM card";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Cards</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h1 class="mb-4">Manage Cards</h1>

    <!-- ฟอร์มเพิ่ม/แก้ไขข้อมูล -->
    <form method="POST" class="mb-4">
        <input type="hidden" name="id" id="card-id">
        <div class="mb-3">
            <label for="title" class="form-label">Title</label>
            <input type="text" name="title" id="title" class="form-control" required>
        </div>
        <div class="mb-3">
            <label for="description" class="form-label">Description</label>
            <textarea name="description" id="description" class="form-control"></textarea>
        </div>
        <div class="mb-3">
            <label for="price" class="form-label">Price</label>
            <input type="number" name="price" id="price" class="form-control" step="0.01" required>
        </div>
        <div class="mb-3">
            <label for="image_url" class="form-label">Image URL</label>
            <input type="text" name="image_url" id="image_url" class="form-control" required>
        </div>
        <div class="mb-3">
            <label for="link_url" class="form-label">Link URL</label>
            <input type="text" name="link_url" id="link_url" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary">Save</button>
    </form>

    <!-- แสดงข้อมูล -->
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Title</th>
                <th>Description</th>
                <th>Price</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['title']) ?></td>
                <td><?= htmlspecialchars($row['description']) ?></td>
                <td><?= $row['price'] ?></td>
                <td>
                    <a href="?action=edit&id=<?= $row['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                    <a href="?action=delete&id=<?= $row['id'] ?>" class="btn btn-sm btn-danger">Delete</a>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>
</body>
</html>
