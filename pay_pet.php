<?php
require_once 'includes/db_petclinic.php';

$type = $_GET['type'] ?? '';
$id = intval($_GET['id'] ?? 0);
$cid = intval($_GET['cid'] ?? 0);

if (!$type || !$id || !$cid) {
    die("Invalid request.");
}

$table = "";
switch ($type) {
    case 'medicines': $table = "billmedicine"; break;
    case 'laboratory': $table = "billlaboratory"; break;
    case 'vaccination': $table = "billvaccination"; break;
    case 'scanning': $table = "billscan"; break;
    default: die("Invalid type.");
}

$stmt = $conn->prepare("UPDATE $table SET payment_status = 1 WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    header("Location: index.php?cid=" . $cid);
    exit();
} else {
    echo "Error: " . $conn->error;
}
?>
