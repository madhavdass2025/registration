<?php
require_once '../includes/db_petclinic.php';

$q = isset($_GET['q']) ? $_GET['q'] : '';

$sql = "SELECT Mid as id, MedicineName as text, SalePrice as price FROM medicines WHERE MedicineName LIKE ? LIMIT 20";
$stmt = $conn->prepare($sql);
$search = "%$q%";
$stmt->bind_param("s", $search);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data);
?>
