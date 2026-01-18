<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'jeoczvkk_thecochinpetshop';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
