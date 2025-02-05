<?php
session_start();
require_once '../db_connect.php';

// ตรวจสอบว่าได้รับค่าจาก POST
if (isset($_POST['tech_id']) && isset($_POST['status'])) {
    $tech_id = intval($_POST['tech_id']);
    $status = intval($_POST['status']); // 1 = bookmark, 0 = unbookmark

    // ตรวจสอบว่า user ได้ล็อกอินแล้วหรือไม่
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];

        // Update ฟิลด์ bookmark ในตาราง user
        $update_query = $conn->prepare(
            "UPDATE users SET bookmark = ? WHERE user_id = ?"
        );
        $update_query->bind_param("ii", $tech_id, $user_id);
        if ($update_query->execute()) {
            echo "Bookmark updated successfully!";
        } else {
            echo "Error updating bookmark.";
        }
    } else {
        echo "User not logged in.";
    }
} else {
    echo "Invalid data.";
}
?>
