<?php
session_start();
require_once 'includes/db_petclinic.php';

// Get Appointment/Consultation ID from GET or SESSION
$consultation_id = isset($_GET['cid']) ? intval($_GET['cid']) : (isset($_SESSION['consultation_id']) ? $_SESSION['consultation_id'] : 0);

if ($consultation_id <= 0) {
    die("Invalid Consultation ID.");
}

// Fetch Bill ID for this consultation
$bill_stmt = $conn->prepare("SELECT billId, netAmount FROM billnew WHERE consult_id = ?");
$bill_stmt->bind_param("i", $consultation_id);
$bill_stmt->execute();
$bill_data = $bill_stmt->get_result()->fetch_assoc();

if (!$bill_data) {
    die("No billing record found for this consultation.");
}

$bill_id = $bill_data['billId'];
$grandTotal = 0; // We will calculate manually to ensure consistency
$totalPaid = 0;
$totalDue = 0;

$bills = [
    'Medicines' => [],
    'Laboratory' => [],
    'Vaccination' => [],
    'Scanning' => []
];

// Fetch Medicines
$m_sql = "SELECT bm.id, m.MedicineName as item_name, bm.amount, bm.payment_status
          FROM billmedicine bm
          JOIN medicines m ON bm.medId = m.Mid
          WHERE bm.billId = ?";
$stmt = $conn->prepare($m_sql);
$stmt->bind_param("i", $bill_id);
$stmt->execute();
$m_res = $stmt->get_result();
while ($row = $m_res->fetch_assoc()) {
    $bills['Medicines'][] = $row;
}

// Fetch Laboratory
$l_sql = "SELECT bl.id, l.LabTest as item_name, bl.amount, bl.payment_status
          FROM billlaboratory bl
          JOIN laboratory l ON bl.testId = l.Lid
          WHERE bl.billId = ?";
$stmt = $conn->prepare($l_sql);
$stmt->bind_param("i", $bill_id);
$stmt->execute();
$l_res = $stmt->get_result();
while ($row = $l_res->fetch_assoc()) {
    $bills['Laboratory'][] = $row;
}

// Fetch Vaccination
$v_sql = "SELECT bv.id, v.VaccName as item_name, bv.amount, bv.payment_status
          FROM billvaccination bv
          JOIN vaccination v ON bv.vaccId = v.VId
          WHERE bv.billId = ?";
$stmt = $conn->prepare($v_sql);
$stmt->bind_param("i", $bill_id);
$stmt->execute();
$v_res = $stmt->get_result();
while ($row = $v_res->fetch_assoc()) {
    $bills['Vaccination'][] = $row;
}

// Fetch Scanning
$s_sql = "SELECT bs.id, s.ScanName as item_name, bs.amount, bs.payment_status
          FROM billscan bs
          JOIN scan s ON bs.scanId = s.sID
          WHERE bs.billId = ?";
$stmt = $conn->prepare($s_sql);
$stmt->bind_param("i", $bill_id);
$stmt->execute();
$s_res = $stmt->get_result();
while ($row = $s_res->fetch_assoc()) {
    $bills['Scanning'][] = $row;
}

// Calculate Totals
foreach ($bills as $category => $items) {
    foreach ($items as $item) {
        $grandTotal += $item['amount'];
        if ($item['payment_status'] == 1) {
            $totalPaid += $item['amount'];
        } else {
            $totalDue += $item['amount'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pet Clinic - Billing Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @media print {
            .no-print { display: none !important; }
            .card { border: none !important; }
            .unpaid-row { display: none !important; }
        }
    </style>
</head>
<body class="bg-light">

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
        <h1>Patient Billing Dashboard</h1>
        <div>
            <a href="doctor/dashboard.php" class="btn btn-outline-secondary me-2">Doctor Module</a>
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fas fa-print"></i> Print Receipt (Paid Only)
            </button>
        </div>
    </div>

    <div class="row mb-4 no-print">
        <div class="col-md-4">
            <div class="card text-white bg-dark mb-3">
                <div class="card-body">
                    <h5 class="card-title">Grand Total</h5>
                    <p class="card-text h2">$<?php echo number_format($grandTotal, 2); ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-success mb-3">
                <div class="card-body">
                    <h5 class="card-title">Total Paid</h5>
                    <p class="card-text h2">$<?php echo number_format($totalPaid, 2); ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-danger mb-3">
                <div class="card-body">
                    <h5 class="card-title">Total Due</h5>
                    <p class="card-text h2">$<?php echo number_format($totalDue, 2); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Print Header -->
    <div class="d-none d-print-block mb-4">
        <h2>The Cochin Pet Shop - Medical Receipt</h2>
        <p>Consultation ID: #<?php echo $consultation_id; ?> | Bill ID: #<?php echo $bill_id; ?></p>
        <p>Date: <?php echo date('Y-m-d H:i:s'); ?></p>
        <p><strong>Total Amount Paid: $<?php echo number_format($totalPaid, 2); ?></strong></p>
        <hr>
    </div>

    <?php foreach ($bills as $category => $items): ?>
        <?php if (count($items) > 0): ?>
            <div class="card mb-4 shadow-sm">
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
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                                <tr class="<?php echo ($item['payment_status'] == 0) ? 'unpaid-row' : 'paid-row'; ?>">
                                    <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                                    <td>$<?php echo number_format($item['amount'], 2); ?></td>
                                    <td class="text-end no-print">
                                        <?php if ($item['payment_status'] == 0): ?>
                                            <a href="pay_pet.php?type=<?php echo strtolower($category); ?>&id=<?php echo $item['id']; ?>&cid=<?php echo $consultation_id; ?>" class="btn btn-danger btn-sm">
                                                Pay Now
                                            </a>
                                        <?php else: ?>
                                            <button class="btn btn-success btn-sm" disabled>
                                                <i class="fas fa-check"></i> Paid
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>

    <?php if ($grandTotal == 0): ?>
        <div class="alert alert-info">No charges found for this session.</div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
