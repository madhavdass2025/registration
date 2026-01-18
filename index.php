<?php
session_start();
require_once 'includes/db.php';

// Get Appointment/Consultation ID from GET or SESSION
$consultation_id = isset($_GET['cid']) ? intval($_GET['cid']) : (isset($_SESSION['consultation_id']) ? $_SESSION['consultation_id'] : 0);

if ($consultation_id <= 0) {
    die("Invalid Consultation ID.");
}

// Fetch all billing rows for the given Consultation ID
$stmt = $conn->prepare("SELECT * FROM patient_bills WHERE consultation_id = ? ORDER BY category, created_at");
$stmt->bind_param("i", $consultation_id);
$stmt->execute();
$result = $stmt->get_result();

$bills = [];
$grandTotal = 0;
$totalDue = 0;

while ($row = $result->fetch_assoc()) {
    $bills[$row['category']][] = $row;
    $grandTotal += $row['amount'];
    if ($row['payment_status'] == 0) {
        $totalDue += $row['amount'];
    }
}

$categories = ['Registration', 'Consultation', 'Medicines', 'Laboratory', 'Scanning', 'Vaccination', 'Injection'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical Billing Dashboard</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @media print {
            .no-print {
                display: none !important;
            }
            .card {
                border: none !important;
            }
            .btn {
                display: none !important;
            }
            .paid-badge {
                display: inline-block !important;
            }
        }
        .paid-badge {
            display: none;
            color: green;
            font-weight: bold;
        }
    </style>
</head>
<body class="bg-light">

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
        <h1>Patient Checkout Dashboard</h1>
        <div>
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fas fa-print"></i> Print Receipt (Paid Items Only)
            </button>
        </div>
    </div>

    <div class="row mb-4 no-print">
        <div class="col-md-6">
            <div class="card text-white bg-dark mb-3">
                <div class="card-body">
                    <h5 class="card-title">Grand Total</h5>
                    <p class="card-text h2">$<?php echo number_format($grandTotal, 2); ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card text-white bg-danger mb-3">
                <div class="card-body">
                    <h5 class="card-title">Total Due</h5>
                    <p class="card-text h2">$<?php echo number_format($totalDue, 2); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Print Header (only visible on print) -->
    <div class="d-none d-print-block mb-4">
        <h2>Medical Receipt</h2>
        <p>Consultation ID: #<?php echo $consultation_id; ?></p>
        <p>Date: <?php echo date('Y-m-d H:i:s'); ?></p>
        <hr>
    </div>

    <?php foreach ($categories as $category): ?>
        <?php if (isset($bills[$category]) && count($bills[$category]) > 0): ?>
            <div class="card mb-4 shadow-sm section-to-print">
                <div class="card-header bg-white">
                    <h5 class="mb-0 text-primary"><?php echo $category; ?></h5>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Item Name</th>
                                <th>Amount</th>
                                <th class="text-end no-print">Action</th>
                                <th class="text-end d-none d-print-table-cell">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bills[$category] as $bill): ?>
                                <?php
                                    // Logic for print: only show paid items if we wanted to strictly follow "Print Receipt triggers a browser print for only the 'Paid' items"
                                    // The prompt says: "triggers a browser print for only the 'Paid' items"
                                    // Let's use CSS to hide unpaid items during print.
                                    $rowClass = ($bill['payment_status'] == 0) ? 'unpaid-row' : 'paid-row';
                                ?>
                                <tr class="<?php echo $rowClass; ?>">
                                    <td><?php echo htmlspecialchars($bill['item_name']); ?></td>
                                    <td>$<?php echo number_format($bill['amount'], 2); ?></td>
                                    <td class="text-end no-print">
                                        <?php if ($bill['payment_status'] == 0): ?>
                                            <a href="pay.php?id=<?php echo $bill['id']; ?>&cid=<?php echo $consultation_id; ?>" class="btn btn-danger btn-sm">
                                                Pay Now
                                            </a>
                                        <?php else: ?>
                                            <button class="btn btn-success btn-sm" disabled>
                                                <i class="fas fa-check"></i> Paid
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end d-none d-print-table-cell">
                                        <?php echo ($bill['payment_status'] == 1) ? 'Paid' : 'Unpaid'; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>

    <?php if (empty($bills)): ?>
        <div class="alert alert-info">
            No billing records found for this consultation.
        </div>
    <?php endif; ?>
</div>

<style>
    @media print {
        .unpaid-row {
            display: none !important;
        }
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
