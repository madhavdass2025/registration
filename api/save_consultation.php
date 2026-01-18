<?php
require_once '../includes/db_petclinic.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

$conn->begin_transaction();

try {
    $consult_id = intval($_POST['consult_id']);
    $reg_no = $_POST['reg_no'];

    // 1. Save Vitals
    $stmt = $conn->prepare("INSERT INTO vitals (consult_id, RegNo, temperature, heart_rate, respiratory_rate, weight) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isdddd", $consult_id, $reg_no, $_POST['temperature'], $_POST['heart_rate'], $_POST['respiratory_rate'], $_POST['weight']);
    $stmt->execute();

    // 2. Save Diagnosis
    $stmt = $conn->prepare("INSERT INTO diagnosis (consult_id, RegNo, clinical_notes, differential_diagnosis, treatment_plan, next_review_date) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssss", $consult_id, $reg_no, $_POST['clinical_notes'], $_POST['differential_diagnosis'], $_POST['treatment_plan'], $_POST['next_review_date']);
    $stmt->execute();

    // 3. Handle Billing - Get or Create Bill ID
    // Assuming billnew exists and links to consult_id or we find it by RegNo for today
    $bill_stmt = $conn->prepare("SELECT billId FROM billnew WHERE consult_id = ?");
    $bill_stmt->bind_param("i", $consult_id);
    $bill_stmt->execute();
    $bill_res = $bill_stmt->get_result();

    if ($bill_res->num_rows > 0) {
        $bill_id = $bill_res->fetch_assoc()['billId'];
    } else {
        // Create new bill entry
        $ins_bill = $conn->prepare("INSERT INTO billnew (consult_id, RegNo, netAmount, date) VALUES (?, ?, 0, NOW())");
        $ins_bill->bind_param("is", $consult_id, $reg_no);
        $ins_bill->execute();
        $bill_id = $conn->insert_id;
    }

    $total_bill_increase = 0;

    // 4. Save Prescriptions & Bill Medicines
    if (isset($_POST['med_id'])) {
        foreach ($_POST['med_id'] as $key => $med_id) {
            $med_id = intval($med_id);
            $dosage = $_POST['dosage'][$key];
            $freq = $_POST['frequency'][$key];
            $dur = $_POST['duration'][$key];

            $stmt = $conn->prepare("INSERT INTO consultation_prescriptions (consult_id, med_id, dosage, frequency, duration) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iisss", $consult_id, $med_id, $dosage, $freq, $dur);
            $stmt->execute();

            // Automatic Billing for Medicine
            $p_stmt = $conn->prepare("SELECT SalePrice FROM medicines WHERE Mid = ?");
            $p_stmt->bind_param("i", $med_id);
            $p_stmt->execute();
            $price = $p_stmt->get_result()->fetch_assoc()['SalePrice'] ?? 0;

            $b_med = $conn->prepare("INSERT INTO billmedicine (billId, medId, amount) VALUES (?, ?, ?)");
            $b_med->bind_param("iid", $bill_id, $med_id, $price);
            $b_med->execute();
            $total_bill_increase += $price;
        }
    }

    // 5. Save Lab Orders & Bill Lab
    if (isset($_POST['lab_tests'])) {
        foreach ($_POST['lab_tests'] as $test_id) {
            $test_id = intval($test_id);
            $stmt = $conn->prepare("INSERT INTO consultation_lab_orders (consult_id, test_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $consult_id, $test_id);
            $stmt->execute();

            $p_stmt = $conn->prepare("SELECT Amount FROM laboratory WHERE Lid = ?");
            $p_stmt->bind_param("i", $test_id);
            $p_stmt->execute();
            $price = $p_stmt->get_result()->fetch_assoc()['Amount'] ?? 0;

            $b_lab = $conn->prepare("INSERT INTO billlaboratory (billId, testId, amount) VALUES (?, ?, ?)");
            $b_lab->bind_param("iid", $bill_id, $test_id, $price);
            $b_lab->execute();
            $total_bill_increase += $price;
        }
    }

    // 6. Save Vaccinations & Bill Vacc
    if (isset($_POST['vaccinations'])) {
        foreach ($_POST['vaccinations'] as $vacc_id) {
            $vacc_id = intval($vacc_id);
            $stmt = $conn->prepare("INSERT INTO consultation_vaccination_orders (consult_id, vacc_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $consult_id, $vacc_id);
            $stmt->execute();

            $p_stmt = $conn->prepare("SELECT Amount FROM vaccination WHERE VId = ?");
            $p_stmt->bind_param("i", $vacc_id);
            $p_stmt->execute();
            $price = $p_stmt->get_result()->fetch_assoc()['Amount'] ?? 0;

            $b_vacc = $conn->prepare("INSERT INTO billvaccination (billId, vaccId, amount) VALUES (?, ?, ?)");
            $b_vacc->bind_param("iid", $bill_id, $vacc_id, $price);
            $b_vacc->execute();
            $total_bill_increase += $price;
        }
    }

    // 7. Save Imaging & Bill Scan
    if (isset($_POST['scans'])) {
        foreach ($_POST['scans'] as $scan_id) {
            $scan_id = intval($scan_id);
            $stmt = $conn->prepare("INSERT INTO consultation_imaging_orders (consult_id, scan_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $consult_id, $scan_id);
            $stmt->execute();

            $p_stmt = $conn->prepare("SELECT Amount FROM scan WHERE sID = ?");
            $p_stmt->bind_param("i", $scan_id);
            $p_stmt->execute();
            $price = $p_stmt->get_result()->fetch_assoc()['Amount'] ?? 0;

            $b_scan = $conn->prepare("INSERT INTO billscan (billId, scanId, amount) VALUES (?, ?, ?)");
            $b_scan->bind_param("iid", $bill_id, $scan_id, $price);
            $b_scan->execute();
            $total_bill_increase += $price;
        }
    }

    // 8. Update Total Calculation in billnew
    $upd_bill = $conn->prepare("UPDATE billnew SET netAmount = netAmount + ? WHERE billId = ?");
    $upd_bill->bind_param("di", $total_bill_increase, $bill_id);
    $upd_bill->execute();

    // 9. Update Consultation Status
    $upd_status = $conn->prepare("UPDATE consultation SET status = 'Completed' WHERE consult_id = ?");
    $upd_status->bind_param("i", $consult_id);
    $upd_status->execute();

    $conn->commit();
    echo json_encode(['status' => 'success']);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
