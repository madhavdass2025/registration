<?php
session_start();
require_once '../includes/db_petclinic.php';

// Assuming doctor_id is stored in session after login
$doctor_id = isset($_SESSION['doctor_id']) ? $_SESSION['doctor_id'] : 1;

$today = date('Y-m-d');
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

$sql = "SELECT c.*, r.PetName, r.OwnerName, r.RegNo
        FROM consultation c
        JOIN registration r ON c.RegNo = r.RegNo
        WHERE c.doctor_id = ? AND DATE(c.consultation_date) = ?";

if ($status_filter) {
    $sql .= " AND c.status = ?";
}

$stmt = $conn->prepare($sql);
if ($status_filter) {
    $stmt->bind_param("iss", $doctor_id, $today, $status_filter);
} else {
    $stmt->bind_param("is", $doctor_id, $today);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard - Today's Schedule</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-primary mb-4">
    <div class="container">
        <span class="navbar-brand mb-0 h1">Pet Clinic - Doctor Module</span>
    </div>
</nav>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Today's Schedule (<?php echo date('d M Y'); ?>)</h2>
        <div class="btn-group">
            <a href="dashboard.php" class="btn btn-outline-secondary <?php echo $status_filter == '' ? 'active' : ''; ?>">All</a>
            <a href="dashboard.php?status=Scheduled" class="btn btn-outline-secondary <?php echo $status_filter == 'Scheduled' ? 'active' : ''; ?>">Scheduled</a>
            <a href="dashboard.php?status=In-Progress" class="btn btn-outline-secondary <?php echo $status_filter == 'In-Progress' ? 'active' : ''; ?>">In-Progress</a>
            <a href="dashboard.php?status=Completed" class="btn btn-outline-secondary <?php echo $status_filter == 'Completed' ? 'active' : ''; ?>">Completed</a>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Consult ID</th>
                        <th>Reg No</th>
                        <th>Pet Name</th>
                        <th>Owner Name</th>
                        <th>Status</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $row['consult_id']; ?></td>
                                <td><?php echo $row['RegNo']; ?></td>
                                <td><?php echo htmlspecialchars($row['PetName']); ?></td>
                                <td><?php echo htmlspecialchars($row['OwnerName']); ?></td>
                                <td>
                                    <span class="badge <?php
                                        echo $row['status'] == 'Completed' ? 'bg-success' : ($row['status'] == 'In-Progress' ? 'bg-warning' : 'bg-info');
                                    ?>">
                                        <?php echo $row['status']; ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <a href="consultation.php?cid=<?php echo $row['consult_id']; ?>&reg=<?php echo $row['RegNo']; ?>" class="btn btn-primary btn-sm">
                                        <i class="fas fa-stethoscope"></i> Start Consultation
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-4">No appointments found for today.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
