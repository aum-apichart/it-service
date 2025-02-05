<?php
// Database connection
require_once '../db_connect.php'; // Replace with your actual DB connection file

// Reset Work Status Logic
if (isset($_POST['reset_work_status'])) {
    $tech_id = $_POST['tech_id'];

    $stmt = $conn->prepare("UPDATE technicians SET work_status = 'Unavailable' WHERE tech_id = ?");
    $stmt->bind_param("i", $tech_id);
    if ($stmt->execute()) {
        $message = "Work status reset to 'Available' successfully!";
    } else {
        $message = "Failed to reset work status.";
    }
}

// Fetch all technicians data
$result = $conn->query("SELECT * FROM technicians");
$technicians = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Technicians List</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .reset-btn {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 5px;
            cursor: pointer;
        }

        .reset-btn:hover {
            background-color: #218838;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-center mb-4">Technicians List</h1>

        <?php if (isset($message)): ?>
            <div class="alert alert-info text-center">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>Technician ID</th>
                    <th>First Name</th>
                    <th>Last Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Address</th>
                    <th>Status</th>
                    <th>Work Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($technicians as $tech): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($tech['tech_id']); ?></td>
                        <td><?php echo htmlspecialchars($tech['first_name']); ?></td>
                        <td><?php echo htmlspecialchars($tech['last_name']); ?></td>
                        <td><?php echo htmlspecialchars($tech['email']); ?></td>
                        <td><?php echo htmlspecialchars($tech['phone']); ?></td>
                        <td><?php echo htmlspecialchars($tech['address']); ?></td>
                        <td><?php echo htmlspecialchars($tech['status']); ?></td>
                        <td><?php echo htmlspecialchars($tech['work_status']); ?></td>
                        <td>
                            <form method="POST" action="">
                                <input type="hidden" name="tech_id" value="<?php echo $tech['tech_id']; ?>">
                                <button type="submit" name="reset_work_status" class="reset-btn">Reset Work Status</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
