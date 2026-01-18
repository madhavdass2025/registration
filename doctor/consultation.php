<?php
session_start();
require_once '../includes/db_petclinic.php';

$consult_id = isset($_GET['cid']) ? intval($_GET['cid']) : 0;
$reg_no = isset($_GET['reg']) ? $_GET['reg'] : '';

if (!$consult_id || !$reg_no) {
    die("Invalid Consultation or Registration ID.");
}

// Update status to In-Progress if it was Scheduled
$upd_status = $conn->prepare("UPDATE consultation SET status = 'In-Progress' WHERE consult_id = ? AND status = 'Scheduled'");
$upd_status->bind_param("i", $consult_id);
$upd_status->execute();

// Fetch Pet and Owner Details
$stmt = $conn->prepare("SELECT * FROM registration WHERE RegNo = ?");
$stmt->bind_param("s", $reg_no);
$stmt->execute();
$pet = $stmt->get_result()->fetch_assoc();

// Fetch weight history for Chart.js
$weight_history = [];
$w_stmt = $conn->prepare("SELECT weight, created_at FROM vitals WHERE RegNo = ? ORDER BY created_at ASC");
$w_stmt->bind_param("s", $reg_no);
$w_stmt->execute();
$w_res = $w_stmt->get_result();
while ($w_row = $w_res->fetch_assoc()) {
    $weight_history[] = $w_row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultation - <?php echo htmlspecialchars($pet['PetName']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .timeline { border-left: 3px solid #0d6efd; padding-left: 20px; position: relative; }
        .timeline-item { margin-bottom: 20px; position: relative; }
        .timeline-item::before {
            content: ''; background: #0d6efd; border-radius: 50%; height: 12px; width: 12px;
            position: absolute; left: -27.5px; top: 5px;
        }
    </style>
</head>
<body class="bg-light">

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1>Consultation: <?php echo htmlspecialchars($pet['PetName']); ?></h1>
            <p class="text-muted">Owner: <?php echo htmlspecialchars($pet['OwnerName']); ?> | RegNo: <?php echo $reg_no; ?></p>
        </div>
        <a href="dashboard.php" class="btn btn-outline-secondary">Back to Dashboard</a>
    </div>

    <ul class="nav nav-tabs mb-4" id="consultationTabs" role="tablist">
        <li class="nav-item">
            <button class="nav-link active" id="history-tab" data-bs-toggle="tab" data-bs-target="#history" type="button">History</button>
        </li>
        <li class="nav-item">
            <button class="nav-link" id="vitals-tab" data-bs-toggle="tab" data-bs-target="#vitals" type="button">Vitals</button>
        </li>
        <li class="nav-item">
            <button class="nav-link" id="diagnosis-tab" data-bs-toggle="tab" data-bs-target="#diagnosis" type="button">Diagnosis</button>
        </li>
        <li class="nav-item">
            <button class="nav-link" id="prescriptions-tab" data-bs-toggle="tab" data-bs-target="#prescriptions" type="button">Prescriptions</button>
        </li>
        <li class="nav-item">
            <button class="nav-link" id="orders-tab" data-bs-toggle="tab" data-bs-target="#orders" type="button">Orders</button>
        </li>
    </ul>

    <form id="consultationForm">
        <input type="hidden" name="consult_id" value="<?php echo $consult_id; ?>">
        <input type="hidden" name="reg_no" value="<?php echo $reg_no; ?>">

        <div class="tab-content" id="consultationTabsContent">
            <!-- Tab 1: History -->
            <div class="tab-pane fade show active" id="history" role="tabpanel">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Visit Timeline</h5>
                        <div class="timeline">
                            <?php
                            $h_sql = "SELECT v.created_at, v.temperature, v.heart_rate, d.clinical_notes, d.treatment_plan,
                                      GROUP_CONCAT(m.MedicineName SEPARATOR ', ') as meds
                                      FROM vitals v
                                      LEFT JOIN diagnosis d ON v.consult_id = d.consult_id
                                      LEFT JOIN consultation_prescriptions cp ON v.consult_id = cp.consult_id
                                      LEFT JOIN medicines m ON cp.med_id = m.Mid
                                      WHERE v.RegNo = ?
                                      GROUP BY v.consult_id
                                      ORDER BY v.created_at DESC";
                            $h_stmt = $conn->prepare($h_sql);
                            $h_stmt->bind_param("s", $reg_no);
                            $h_stmt->execute();
                            $h_res = $h_stmt->get_result();
                            while ($h_row = $h_res->fetch_assoc()): ?>
                                <div class="timeline-item">
                                    <h6 class="fw-bold"><?php echo date('d M Y, H:i', strtotime($h_row['created_at'])); ?></h6>
                                    <p class="mb-1"><strong>Vitals:</strong> Temp: <?php echo $h_row['temperature'] ?? 'N/A'; ?>°C, HR: <?php echo $h_row['heart_rate'] ?? 'N/A'; ?> bpm</p>
                                    <p class="mb-1"><strong>Notes:</strong> <?php echo htmlspecialchars($h_row['clinical_notes'] ?? 'No notes'); ?></p>
                                    <p class="mb-1"><strong>Meds:</strong> <?php echo htmlspecialchars($h_row['meds'] ?? 'None'); ?></p>
                                    <p class="text-muted small"><strong>Treatment:</strong> <?php echo htmlspecialchars($h_row['treatment_plan'] ?? 'N/A'); ?></p>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab 2: Vitals -->
            <div class="tab-pane fade" id="vitals" role="tabpanel">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card shadow-sm mb-4">
                            <div class="card-body">
                                <h5 class="card-title mb-3">Input Physical Exam Data</h5>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Temperature (°C) <i class="fas fa-thermometer-half text-danger"></i> 🌡️</label>
                                        <input type="number" step="0.1" name="temperature" class="form-control">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Heart Rate (bpm) <i class="fas fa-heartbeat text-danger"></i> 💓</label>
                                        <input type="number" name="heart_rate" class="form-control">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Respiratory Rate <i class="fas fa-wind text-info"></i></label>
                                        <input type="number" name="respiratory_rate" class="form-control">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Weight (kg) <i class="fas fa-weight text-secondary"></i> ⚖️</label>
                                        <input type="number" step="0.01" name="weight" id="current_weight" class="form-control">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title mb-3">Weight History</h5>
                                <canvas id="weightChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab 3: Diagnosis -->
            <div class="tab-pane fade" id="diagnosis" role="tabpanel">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Clinical Notes</label>
                            <textarea name="clinical_notes" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Differential Diagnosis</label>
                            <textarea name="differential_diagnosis" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Treatment Plan</label>
                            <textarea name="treatment_plan" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Next Review Date</label>
                            <input type="date" name="next_review_date" class="form-control">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab 4: Prescriptions -->
            <div class="tab-pane fade" id="prescriptions" role="tabpanel">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <table class="table" id="prescriptionTable">
                            <thead>
                                <tr>
                                    <th>Medicine Name</th>
                                    <th>Dosage</th>
                                    <th>Frequency</th>
                                    <th>Duration</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Dynamic Rows Go Here -->
                            </tbody>
                        </table>
                        <button type="button" class="btn btn-success btn-sm" onclick="addPrescriptionRow()">
                            <i class="fas fa-plus"></i> Add Medicine
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tab 5: Orders -->
            <div class="tab-pane fade" id="orders" role="tabpanel">
                <div class="row">
                    <div class="col-md-4">
                        <div class="card shadow-sm">
                            <div class="card-header">Lab Tests</div>
                            <div class="card-body scrollable-list" style="max-height: 300px; overflow-y: auto;">
                                <?php
                                $lab_res = $conn->query("SELECT Lid, LabTest FROM laboratory ORDER BY LabTest");
                                while($l = $lab_res->fetch_assoc()): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="lab_tests[]" value="<?php echo $l['Lid']; ?>" id="lab_<?php echo $l['Lid']; ?>">
                                        <label class="form-check-label" for="lab_<?php echo $l['Lid']; ?>"><?php echo $l['LabTest']; ?></label>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card shadow-sm">
                            <div class="card-header">Vaccinations</div>
                            <div class="card-body scrollable-list" style="max-height: 300px; overflow-y: auto;">
                                <?php
                                $vacc_res = $conn->query("SELECT VId, VaccName FROM vaccination ORDER BY VaccName");
                                while($v = $vacc_res->fetch_assoc()): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="vaccinations[]" value="<?php echo $v['VId']; ?>" id="vacc_<?php echo $v['VId']; ?>">
                                        <label class="form-check-label" for="vacc_<?php echo $v['VId']; ?>"><?php echo $v['VaccName']; ?></label>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card shadow-sm">
                            <div class="card-header">Scans/Imaging</div>
                            <div class="card-body scrollable-list" style="max-height: 300px; overflow-y: auto;">
                                <?php
                                $scan_res = $conn->query("SELECT sID, ScanName FROM scan ORDER BY ScanName");
                                while($s = $scan_res->fetch_assoc()): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="scans[]" value="<?php echo $s['sID']; ?>" id="scan_<?php echo $s['sID']; ?>">
                                        <label class="form-check-label" for="scan_<?php echo $s['sID']; ?>"><?php echo $s['ScanName']; ?></label>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-4 text-end">
            <button type="submit" class="btn btn-primary btn-lg px-5">
                <i class="fas fa-save"></i> Save Consultation
            </button>
        </div>
    </form>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    // Weight History Chart
    const ctx = document.getElementById('weightChart').getContext('2d');
    const weightData = <?php echo json_encode($weight_history); ?>;
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: weightData.map(d => new Date(d.created_at).toLocaleDateString()),
            datasets: [{
                label: 'Weight (kg)',
                data: weightData.map(d => d.weight),
                borderColor: '#0d6efd',
                tension: 0.1
            }]
        }
    });

    // Dynamic Prescription Rows
    function addPrescriptionRow() {
        const tbody = document.querySelector('#prescriptionTable tbody');
        const rowId = Date.now();
        const row = `
            <tr id="row_${rowId}">
                <td style="width: 40%">
                    <select name="med_id[]" class="form-control medicine-select" required></select>
                    <input type="hidden" name="med_price[]" class="med-price">
                </td>
                <td><input type="text" name="dosage[]" class="form-control" placeholder="e.g. 5ml"></td>
                <td><input type="text" name="frequency[]" class="form-control" placeholder="e.g. BID"></td>
                <td><input type="text" name="duration[]" class="form-control" placeholder="e.g. 5 days"></td>
                <td>
                    <button type="button" class="btn btn-danger btn-sm" onclick="removeRow(${rowId})">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
        $(tbody).append(row);
        initMedicineSelect($(`#row_${rowId} .medicine-select`));
    }

    function removeRow(id) {
        $(`#row_${id}`).remove();
    }

    function initMedicineSelect(element) {
        element.select2({
            placeholder: 'Search Medicine...',
            ajax: {
                url: '../api/get_medicines.php',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return { q: params.term };
                },
                processResults: function (data) {
                    return { results: data };
                },
                cache: true
            }
        }).on('select2:select', function (e) {
            const data = e.params.data;
            $(this).closest('td').find('.med-price').val(data.price);
        });
    }

    // Form Submission
    $('#consultationForm').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: '../api/save_consultation.php',
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                const res = JSON.parse(response);
                if(res.status === 'success') {
                    Swal.fire('Saved!', 'Consultation saved and billing updated.', 'success')
                    .then(() => window.location.href = 'dashboard.php');
                } else {
                    Swal.fire('Error', res.message, 'error');
                }
            }
        });
    });
</script>
</body>
</html>
