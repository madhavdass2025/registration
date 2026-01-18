<?php
require_once 'includes/db.php';

if (isset($_GET['id']) && isset($_GET['cid'])) {
    $bill_id = intval($_GET['id']);
    $consultation_id = intval($_GET['cid']);

    // Prepared statement to update payment status
    $stmt = $conn->prepare("UPDATE patient_bills SET payment_status = 1 WHERE id = ?");
    $stmt->bind_param("i", $bill_id);

    if ($stmt->execute()) {
        // Redirect back to dashboard
        header("Location: index.php?cid=" . $consultation_id);
        exit();
    } else {
        echo "Error updating record: " . $conn->error;
    }
} else {
    echo "Invalid request.";
}
?>
